<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class EnrichManufacturerImages extends Command
{
    protected $signature = 'neogiga:enrich-images {--manufacturer=} {--limit=10} {--dry-run}';
    protected $description = 'Acquire official manufacturer product images';

    private array $adapters = [
        'Texas Instruments' => ['domain' => 'ti.com', 'method' => 'tiProductPage'],
        'Analog Devices' => ['domain' => 'analog.com', 'method' => 'adiProductPage'],
    ];

    public function handle(): int
    {
        $mfr = $this->option('manufacturer');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $query = DB::table('products')
            ->whereNotNull('mpn')
            ->where('mpn', '~', '[0-9]')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('product_images')
                  ->whereColumn('product_images.product_id', 'products.id')
                  ->where('file_path', 'not like', '%placeholder%');
            });

        if ($mfr) {
            $query->where('manufacturer_name', $mfr);
        } else {
            $query->whereIn('manufacturer_name', array_keys($this->adapters));
        }

        $products = $query->orderBy('id')->limit($limit)->get();

        $this->info("Processing {$products->count()} products" . ($dryRun ? ' (DRY RUN)' : ''));

        $found = 0; $downloaded = 0; $failed = 0;
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $bar->advance();
            $adapter = $this->adapters[$product->manufacturer_name] ?? null;
            if (! $adapter) continue;

            try {
                $imageUrl = $this->{$adapter['method']}($product);
                if (! $imageUrl) { $failed++; continue; }
                $found++;

                if ($dryRun) {
                    $this->line("  [DRY RUN] {$product->name}: $imageUrl");
                    continue;
                }

                if ($this->downloadAndAttach($product, $imageUrl, $adapter['domain'])) {
                    $downloaded++;
                } else {
                    $failed++;
                }
            } catch (\Throwable) { $failed++; }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Found: $found | Downloaded: $downloaded | Failed: $failed");

        return 0;
    }

    private function tiProductPage($product): ?string
    {
        // Extract family from MPN (e.g., LM5088MHX-2/NOPB → LM5088)
        $mpn = $product->mpn;
        $family = preg_replace('/[A-Z]+-?\d*\/?.*$/', '', $mpn); // crude but works for TI
        if (strlen($family) < 3) $family = explode('-', str_replace('/', '-', $mpn))[0];

        $encodedMpn = str_replace('/', '%2F', $mpn);
        $url = "https://www.ti.com/product/{$family}/part-details/{$encodedMpn}";

        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'NeoGiga/1.0'])
            ->get($url);

        if (! $response->successful()) return null;

        // Extract JSON-LD image
        preg_match('/"image":\s*"([^"]+)"/', $response->body(), $matches);
        if (! empty($matches[1])) {
            return str_replace('\/', '/', $matches[1]);
        }

        // Fallback: img tag
        preg_match('/<img[^>]+src="([^"]*package[^"]*\.(?:png|jpg|jpeg|webp))"/i', $response->body(), $m2);
        return $m2[1] ?? null;
    }

    private function adiProductPage($product): ?string
    {
        $mpn = str_replace('#', '%23', $product->mpn);
        $url = "https://www.analog.com/en/products/" . strtolower(explode('-', $mpn)[0]) . ".html";
        $response = Http::timeout(15)->withHeaders(['User-Agent' => 'NeoGiga/1.0'])->get($url);
        if (! $response->successful()) return null;
        preg_match('/"image":\s*"([^"]+)"/', $response->body(), $m);
        return $m[1] ?? null;
    }

    private function downloadAndAttach($product, string $imageUrl, string $source): bool
    {
        $response = Http::timeout(20)
            ->withHeaders(['User-Agent' => 'NeoGiga/1.0'])
            ->get($imageUrl);

        if (! $response->successful() || strlen($response->body()) < 100) return false;

        $body = $response->body();
        $hash = hash('sha256', $body);
        $ext = match ($response->header('Content-Type')) {
            'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', default => 'jpg'
        };
        $filename = "products/mfr-{$product->id}-" . substr($hash, 0, 8) . ".{$ext}";

        // Dedup check
        if (DB::table('product_images')->where('checksum', $hash)->exists()) return false;

        Storage::disk('public')->put($filename, $body);

        DB::table('product_images')->updateOrInsert(
            ['product_id' => $product->id, 'source_url' => $imageUrl],
            [
                'file_path' => $filename,
                'file_name' => basename($filename),
                'mime_type' => $response->header('Content-Type') ?: 'image/png',
                'file_size' => strlen($body),
                'is_primary' => true,
                'is_active' => true,
                'source_url' => $imageUrl,
                'source_name' => $source,
                'checksum' => $hash,
                'alt_text' => $product->name . ' - official manufacturer image',
                'sort_order' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]
        );

        return true;
    }
}
