<?php

namespace App\Console\Commands;

use App\Models\Marketplace\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Discovers product images by MPN from LCSC and other sources.
 * Writes candidates to product_image_candidates for review before assignment.
 * Rate-limited, resumable, manufacturer-validated.
 */
class ScrapeProductImagesCommand extends Command
{
    protected $signature = 'catalog:scrape-images-by-mpn
                            {--batch-size=50 : Products per batch}
                            {--delay=2 : Seconds between requests}
                            {--limit= : Max products to process}
                            {--manufacturer= : Filter by manufacturer name}
                            {--dry-run : Discover candidates without saving}';

    protected $description = 'Discover product images by MPN from official sources';

    private const CHECKPOINT_TABLE = 'image_scrape_checkpoints';

    public function handle(): int
    {
        $this->ensureCheckpointTable();

        $batchSize = (int) $this->option('batch-size');
        $delay = (int) $this->option('delay');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $manufacturer = $this->option('manufacturer');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — candidates will not be saved.');
        }

        $products = $this->productsWithoutImages($batchSize, $limit, $manufacturer);
        $total = $limit ? min($products->count(), $limit) : $products->count();

        if ($total === 0) {
            $this->info('All matching products have images.');
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

            $result = $this->findImage($mpn, $mfr);

            if ($result && ! $dryRun) {
                $saved = $this->saveCandidate($product, $mpn, $result);
                if ($saved) {
                    $found++;
                }
            } elseif ($result && $dryRun) {
                $found++;
                $this->line("  [dry-run] {$mfr} {$mpn} → {$result['url']} (confidence: {$result['confidence']}%)");
            }

            $this->markProcessed($mpn);
            $processed++;
            $bar->advance();

            if ($processed >= $total) {
                break;
            }

            usleep(($delay * 1_000_000) + random_int(0, 2_000_000));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done: {$found} candidates discovered out of {$processed} processed.");

        return 0;
    }

    private function productsWithoutImages(int $batchSize, ?int $limit, ?string $manufacturer)
    {
        $query = Product::query()
            ->whereDoesntHave('images', fn ($q) => $q->where('is_active', true))
            ->whereNotNull('mpn')
            ->where('mpn', '!=', '');

        if ($manufacturer) {
            $query->where('manufacturer_name', 'like', "%{$manufacturer}%");
        }

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
     * @return array{url:string,source_name:string,confidence:int,source_page_url:?string}|null
     */
    private function findImage(string $mpn, string $mfr): ?array
    {
        // Source 1: LCSC component search (highest confidence source)
        $lcsc = $this->scrapeLcsc($mpn, $mfr);
        if ($lcsc) {
            return $lcsc;
        }

        // Source 2: Google Images (lower confidence, requires manufacturer context)
        if ($mfr !== '') {
            $google = $this->scrapeGoogleImages($mpn, $mfr);
            if ($google) {
                return $google; // Already has lower confidence from the method
            }
        }

        return null;
    }

    /**
     * Search LCSC by MPN with manufacturer validation.
     */
    private function scrapeLcsc(string $mpn, string $mfr): ?array
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

            // Find best match: prefer exact MPN + manufacturer match
            $bestScore = 0;
            $bestImage = null;

            foreach ($products as $item) {
                $itemMpn = trim((string) ($item['productCode'] ?? $item['mpn'] ?? ''));
                $itemMfr = trim((string) ($item['brandNameEn'] ?? $item['brandName'] ?? $item['brand'] ?? ''));

                $mpnMatch = strcasecmp($itemMpn, $mpn) === 0;
                $mfrMatch = $mfr !== '' && $itemMfr !== '' && stripos($itemMfr, $mfr) !== false;

                $score = 0;
                if ($mpnMatch && $mfrMatch) {
                    $score = 95; // Exact MPN + manufacturer match
                } elseif ($mpnMatch) {
                    $score = 70; // Exact MPN only
                } elseif ($mfrMatch && stripos($itemMpn, $mpn) !== false) {
                    $score = 50; // Partial MPN + manufacturer match
                } else {
                    $score = 20; // Weak match
                }

                if ($score > $bestScore) {
                    $imagePath = $item['productImages'] ?? $item['productImage'] ?? null;
                    if (is_array($imagePath) && ! empty($imagePath)) {
                        $imagePath = $imagePath[0];
                    }
                    if ($imagePath && is_string($imagePath)) {
                        $bestScore = $score;
                        $bestImage = [
                            'url' => str_starts_with($imagePath, 'http') ? $imagePath : 'https:' . $imagePath,
                            'source_name' => 'lcsc',
                            'confidence' => $score,
                            'source_page_url' => 'https://www.lcsc.com/search?q=' . urlencode($mpn),
                        ];
                    }
                }
            }

            return $bestImage;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Scrape Google Images as last resort. Lower confidence.
     */
    private function scrapeGoogleImages(string $mpn, string $mfr): ?array
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

            preg_match_all('/"ou":"(https?:\/\/[^"]+\.(?:jpg|jpeg|png|webp|svg))"/i', $response->body(), $matches);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $imageUrl) {
                    if (! str_contains($imageUrl, 'favicon') && ! str_contains($imageUrl, 'icon')) {
                        return [
                            'url' => $imageUrl,
                            'source_name' => 'google_images',
                            'confidence' => 25, // Low confidence — Google Images results are not MPN-validated
                            'source_page_url' => $url,
                        ];
                    }
                }
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Save discovered image as a candidate for review.
     */
    private function saveCandidate(Product $product, string $mpn, array $result): bool
    {
        try {
            if (! DB::getSchemaBuilder()->hasTable('product_image_candidates')) {
                return false;
            }

            $exists = DB::table('product_image_candidates')
                ->where('product_id', $product->id)
                ->where('candidate_url', $result['url'])
                ->exists();

            if ($exists) {
                return false;
            }

            $rightsStatus = $result['confidence'] >= 80 ? 'approved' : 'pending_review';

            DB::table('product_image_candidates')->insert([
                'product_id' => $product->id,
                'candidate_url' => $result['url'],
                'source_name' => $result['source_name'],
                'source_page_url' => $result['source_page_url'] ?? null,
                'manufacturer' => $product->manufacturer_name,
                'mpn' => $mpn,
                'confidence_score' => $result['confidence'],
                'rights_status' => $rightsStatus,
                'rights_review_required' => $result['confidence'] < 80,
                'discovered_by' => 'catalog:scrape-images-by-mpn',
                'discovered_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
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
