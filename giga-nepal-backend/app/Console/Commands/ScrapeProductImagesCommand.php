<?php

namespace App\Console\Commands;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Scrapes product images from official/LCSC websites by MPN lookup.
 * Rate-limited, resumable, writes to product_images table.
 *
 * # ponytail: sequential single-threaded scraper; if throughput matters, add queue jobs per MPN
 */
class ScrapeProductImagesCommand extends Command
{
    protected $signature = 'catalog:scrape-images-by-mpn
                            {--batch-size=50 : Products per batch}
                            {--delay=2 : Seconds between requests}
                            {--limit= : Max products to process (default: all)}';

    protected $description = 'Scrape product images by MPN from official websites';

    private const CHECKPOINT_TABLE = 'image_scrape_checkpoints';

    public function handle(): int
    {
        $this->ensureCheckpointTable();

        $batchSize = (int) $this->option('batch-size');
        $delay = (int) $this->option('delay');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $products = $this->productsWithoutImages($batchSize, $limit);
        $total = $limit ? min($products->count(), $limit) : $products->count();

        if ($total === 0) {
            $this->info('All products have images.');
            return 0;
        }

        $this->info("Processing up to {$total} products...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $found = 0;

        foreach ($products as $product) {
            $mpn = trim((string) $product->mpn);
            $mfr = trim((string) ($product->manufacturer_name ?? ''));

            if ($mpn === '') {
                $bar->advance();
                continue;
            }

            $imageUrl = $this->findImage($mpn, $mfr);

            if ($imageUrl) {
                $saved = $this->saveImage($product, $imageUrl);
                if ($saved) {
                    $found++;
                }
            }

            $this->markProcessed($mpn);
            $processed++;
            $bar->advance();

            if ($processed >= $total) {
                break;
            }

            // Rate limit: random delay between requests
            usleep(($delay * 1000000) + random_int(0, 2000000));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done: {$found} images saved out of {$processed} processed.");

        return 0;
    }

    private function productsWithoutImages(int $batchSize, ?int $limit)
    {
        $query = Product::query()
            ->whereDoesntHave('images', fn ($q) => $q->where('is_active', true))
            ->whereNotNull('mpn')
            ->where('mpn', '!=', '');

        // Skip already-processed MPNs
        if (DB::getSchemaBuilder()->hasTable(self::CHECKPOINT_TABLE)) {
            $query->whereNotIn('mpn', function ($sub) {
                $sub->select('mpn')->from(self::CHECKPOINT_TABLE);
            });
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->select('id', 'mpn', 'manufacturer_name')->get();
    }

    /**
     * Attempt to find product image from available sources.
     */
    private function findImage(string $mpn, string $mfr): ?string
    {
        // Source 1: LCSC/JLCPCB component search
        $url = $this->scrapeLcsc($mpn);
        if ($url) {
            return $url;
        }

        // Source 2: Google Images search (last resort, may trigger CAPTCHA)
        if ($mfr !== '') {
            $url = $this->scrapeGoogleImages($mpn, $mfr);
        }

        return $url;
    }

    /**
     * Search LCSC for product image by MPN.
     */
    private function scrapeLcsc(string $mpn): ?string
    {
        try {
            $searchUrl = 'https://wmsc.lcsc.com/wmsc/search/global?keyword=' . urlencode($mpn);
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; NeoGigaBot/1.0; +https://neogiga.com)',
                'Accept' => 'application/json',
            ])->timeout(15)->get($searchUrl);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            $products = $data['result'] ?? [];

            if (empty($products)) {
                return null;
            }

            // Take first match with matching MPN or closest
            foreach ($products as $item) {
                $itemMpn = $item['productCode'] ?? $item['mpn'] ?? '';
                if (strcasecmp(trim($itemMpn), $mpn) === 0) {
                    $imagePath = $item['productImages'] ?? $item['productImage'] ?? null;
                    if (is_array($imagePath) && ! empty($imagePath)) {
                        $imagePath = $imagePath[0];
                    }
                    if ($imagePath && is_string($imagePath)) {
                        return str_starts_with($imagePath, 'http')
                            ? $imagePath
                            : 'https:' . $imagePath;
                    }
                }
            }

            // Fallback: first result's image
            $first = $products[0];
            $imagePath = $first['productImages'] ?? $first['productImage'] ?? null;
            if (is_array($imagePath) && ! empty($imagePath)) {
                $imagePath = $imagePath[0];
            }

            return ($imagePath && is_string($imagePath))
                ? (str_starts_with($imagePath, 'http') ? $imagePath : 'https:' . $imagePath)
                : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Scrape Google Images as last resort.
     * ⚠️ May trigger CAPTCHAs; use sparingly.
     */
    private function scrapeGoogleImages(string $mpn, string $mfr): ?string
    {
        try {
            $query = urlencode("{$mfr} {$mpn} datasheet");
            $url = "https://www.google.com/search?tbm=isch&q={$query}";

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'Accept' => 'text/html',
            ])->timeout(15)->get($url);

            if (! $response->successful()) {
                return null;
            }

            // Extract first image URL from Google Images results
            preg_match_all('/"ou":"(https?:\/\/[^"]+\.(?:jpg|jpeg|png|webp|svg))"/i', $response->body(), $matches);

            if (! empty($matches[1])) {
                // Filter out tiny/broken images, take first reasonable one
                foreach ($matches[1] as $imageUrl) {
                    if (! str_contains($imageUrl, 'favicon') && ! str_contains($imageUrl, 'icon')) {
                        return $imageUrl;
                    }
                }
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Download and save image to product_images table.
     */
    private function saveImage(Product $product, string $imageUrl): bool
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; NeoGigaBot/1.0)',
            ])->timeout(30)->get($imageUrl);

            if (! $response->successful()) {
                return false;
            }

            $body = $response->body();
            if (strlen($body) < 512) {
                return false; // Too small, likely not a real image
            }

            $ext = $this->guessExtension($imageUrl, $response->header('Content-Type'));
            $filename = 'products/scraped/' . $product->id . '_' . Str::random(8) . '.' . $ext;

            Storage::disk('public')->put($filename, $body);

            ProductImage::create([
                'product_id' => $product->id,
                'file_path' => $filename,
                'original_url' => $imageUrl,
                'source_url' => $imageUrl,
                'is_active' => true,
                'is_primary' => true,
                'sort_order' => 0,
            ]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function guessExtension(string $url, ?string $contentType): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'])) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        return match ($contentType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'jpg',
        };
    }

    private function markProcessed(string $mpn): void
    {
        try {
            DB::table(self::CHECKPOINT_TABLE)->insertOrIgnore([
                'mpn' => $mpn,
                'processed_at' => now(),
            ]);
        } catch (\Throwable) {
            // Non-critical
        }
    }

    private function ensureCheckpointTable(): void
    {
        if (! DB::getSchemaBuilder()->hasTable(self::CHECKPOINT_TABLE)) {
            DB::getSchemaBuilder()->create(self::CHECKPOINT_TABLE, function ($table) {
                $table->string('mpn', 200)->primary();
                $table->timestamp('processed_at');
            });
        }
    }
}
