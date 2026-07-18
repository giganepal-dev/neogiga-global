<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadProductImages extends Command
{
    protected $signature = 'neogiga:download-images {--source=} {--limit=} {--product-id=}';
    protected $description = 'Download product images from source URLs for imported catalog products';

    private int $downloaded = 0;
    private int $skipped = 0;
    private int $failed = 0;

    public function handle(): int
    {
        $source = $this->option('source');
        $limit = (int) ($this->option('limit') ?: 100);
        $productId = $this->option('product-id');

        $query = DB::table('products')
            ->whereNotNull('source_url')
            ->where('source_url', '!=', '')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('product_images')
                  ->whereColumn('product_images.product_id', 'products.id');
            });

        if ($source) {
            $query->where('source_name', $source);
        }
        if ($productId) {
            $query->where('id', $productId);
        }

        $products = $query->limit($limit)->get();

        $this->info("Downloading images for {$products->count()} products...");
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $bar->advance();
            try {
                $this->processProduct($product);
            } catch (\Throwable $e) {
                $this->failed++;
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Downloaded: $this->downloaded | Skipped: $this->skipped | Failed: $this->failed");
        return 0;
    }

    private function processProduct($product): void
    {
        $imageUrls = [];

        // Get primary image URL from different fields
        if (!empty($product->source_url)) {
            // Try to extract image URL from the source URL patterns
            $imageUrls[] = $this->extractImageUrl($product);
        }

        if (empty($imageUrls)) {
            $this->skipped++;
            return;
        }

        foreach ($imageUrls as $i => $url) {
            $url = trim($url);
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) continue;

            try {
                $response = Http::timeout(30)->withHeaders([
                    'User-Agent' => 'NeoGiga/1.0 Catalog Imager',
                ])->get($url);

                if ($response->successful()) {
                    $ext = $this->guessExtension($response->header('Content-Type'), $url);
                    $filename = 'products/' . Str::slug(Str::limit($product->name, 50)) . '-' . $product->id . ($i > 0 ? '-' . $i : '') . '.' . $ext;

                    Storage::disk('public')->put($filename, $response->body());

                    DB::table('product_images')->updateOrInsert(
                        ['product_id' => $product->id, 'source_url' => $url],
                        [
                            'file_path' => $filename,
                            'file_name' => basename($filename),
                            'mime_type' => $response->header('Content-Type') ?: 'image/jpeg',
                            'file_size' => strlen($response->body()),
                            'is_primary' => $i === 0,
                            'is_active' => true,
                            'source_url' => $url,
                            'source_name' => $product->source_name,
                            'sort_order' => $i,
                            'alt_text' => $product->name . ' product image',
                            'created_at' => now(), 'updated_at' => now(),
                        ]
                    );

                    $this->downloaded++;
                } else {
                    $this->failed++;
                }
            } catch (\Throwable) {
                $this->failed++;
            }
        }
    }

    private function extractImageUrl($product): ?string
    {
        // Adafruit pattern: cdn-shop.adafruit.com
        if (str_contains($product->source_url, 'adafruit.com/product/')) {
            $pid = basename($product->source_url);
            return "https://cdn-shop.adafruit.com/970x728/{$pid}-01.jpg";
        }

        // SparkFun pattern: sparkfun.com/products/
        if (str_contains($product->source_url, 'sparkfun.com')) {
            $sku = $product->sku;
            // Try to construct SparkFun CDN URL
            return "https://cdn.sparkfun.com/assets/parts/" . str_pad(substr($sku, -4), 4, '0', STR_PAD_LEFT) . "/" . $sku . "-01.jpg";
        }

        return null;
    }

    private function guessExtension(?string $mime, string $url): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])) return $ext;

        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            default => 'jpg',
        };
    }
}
