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
 * Promotes approved product_image_candidates into product_images,
 * downloads the image, generates derivatives, and tracks provenance.
 */
class AssignMpnImagesCommand extends Command
{
    protected $signature = 'products:assign-mpn-images
                            {--missing-only : Only products without any active image}
                            {--manufacturer= : Filter by manufacturer name}
                            {--batch=100 : Candidates per batch}
                            {--dry-run : Preview assignments without saving}
                            {--min-confidence=80 : Minimum confidence score to auto-assign}';

    protected $description = 'Assign MPN-matched image candidates to products';

    public function handle(): int
    {
        $batch = (int) $this->option('batch');
        $manufacturer = $this->option('manufacturer');
        $minConfidence = (int) $this->option('min-confidence');
        $missingOnly = (bool) $this->option('missing-only');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no images will be saved.');
        }

        if (! DB::getSchemaBuilder()->hasTable('product_image_candidates')) {
            $this->error('product_image_candidates table does not exist. Run migration first.');
            return 1;
        }

        $query = DB::table('product_image_candidates as pic')
            ->join('products as p', 'p.id', '=', 'pic.product_id')
            ->where('pic.rights_status', 'approved')
            ->where('pic.confidence_score', '>=', $minConfidence)
            ->where('pic.candidate_url', 'not like', '')
            ->whereNotNull('pic.candidate_url')
            ->select('pic.*', 'p.manufacturer_name as product_manufacturer', 'p.mpn as product_mpn');

        if ($manufacturer) {
            $query->where('pic.manufacturer', 'like', "%{$manufacturer}%");
        }

        if ($missingOnly) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('product_images')
                    ->whereColumn('product_images.product_id', 'pic.product_id')
                    ->where('product_images.is_active', true);
            });
        }

        $candidates = $query->limit($batch)->get();
        $total = $candidates->count();

        if ($total === 0) {
            $this->info('No approved candidates match the criteria.');
            return 0;
        }

        $this->info("Processing {$total} candidates...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $assigned = 0;
        $skipped = 0;

        foreach ($candidates as $candidate) {
            $mpn = trim((string) ($candidate->product_mpn ?? $candidate->mpn ?? ''));
            $productMfr = trim((string) ($candidate->product_manufacturer ?? $candidate->manufacturer ?? ''));
            $candidateMfr = trim((string) ($candidate->manufacturer ?? ''));

            // Guard: verify manufacturer still matches
            if ($productMfr !== '' && $candidateMfr !== '' && stripos($productMfr, $candidateMfr) === false && stripos($candidateMfr, $productMfr) === false) {
                // Manufacturer mismatch — mark for review instead
                if (! $dryRun) {
                    DB::table('product_image_candidates')->where('id', $candidate->id)->update([
                        'rights_status' => 'pending_review',
                        'rights_review_required' => true,
                        'confidence_score' => max(0, (int) $candidate->confidence_score - 40),
                        'updated_at' => now(),
                    ]);
                }
                $skipped++;
                $bar->advance();
                continue;
            }

            if (! $dryRun) {
                $saved = $this->downloadAndAssign($candidate, $mpn);
                if ($saved) {
                    $assigned++;
                } else {
                    $skipped++;
                }
            } else {
                $this->line("  [dry-run] Product #{$candidate->product_id} ← {$candidate->candidate_url} (confidence: {$candidate->confidence_score}%)");
                $assigned++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done: {$assigned} assigned, {$skipped} skipped.");

        return 0;
    }

    private function downloadAndAssign(object $candidate, string $mpn): bool
    {
        try {
            $imageUrl = $candidate->candidate_url;

            // Download with safety checks (reuse ElecForest pattern)
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; NeoGigaBot/1.0)',
            ])->timeout(30)->get($imageUrl);

            if (! $response->successful()) {
                DB::table('product_image_candidates')->where('id', $candidate->id)->update([
                    'asset_fetch_status' => 'fetch_failed',
                    'updated_at' => now(),
                ]);
                return false;
            }

            $body = $response->body();
            if (strlen($body) < 512) {
                return false; // Too small to be a real image
            }

            $contentType = $response->header('Content-Type');
            $ext = $this->guessExtension($imageUrl, $contentType);
            $checksum = hash('sha256', $body);

            // Dedup: skip if this checksum already exists for this product
            $existingChecksum = DB::table('product_images')
                ->where('product_id', $candidate->product_id)
                ->where('checksum', $checksum)
                ->exists();

            if ($existingChecksum) {
                DB::table('product_image_candidates')->where('id', $candidate->id)->update([
                    'rights_status' => 'assigned',
                    'is_active' => true,
                    'asset_fetch_status' => 'duplicate',
                    'source_checksum' => $checksum,
                    'updated_at' => now(),
                ]);
                return true;
            }

            // Get image dimensions
            [$width, $height] = $this->imageDimensions($body);

            // Store file
            $filename = 'products/assigned/' . $candidate->product_id . '_' . Str::random(8) . '.' . $ext;
            Storage::disk('public')->put($filename, $body);

            // Create product image
            ProductImage::create([
                'product_id' => $candidate->product_id,
                'file_path' => $filename,
                'file_name' => basename($filename),
                'mime_type' => $contentType ?: 'image/jpeg',
                'file_size' => strlen($body),
                'original_url' => $imageUrl,
                'source_url' => $candidate->source_page_url ?: $imageUrl,
                'source_name' => $candidate->source_name ?: 'mpn-assignment',
                'checksum' => $checksum,
                'width' => $width,
                'height' => $height,
                'downloaded_at' => now(),
                'is_active' => true,
                'is_primary' => true,
                'sort_order' => 0,
                'alt_text' => trim(($candidate->manufacturer ?: $candidate->product_manufacturer ?? '') . ' ' . $mpn . ' product image'),
                'metadata' => json_encode([
                    'mpn' => $mpn,
                    'manufacturer' => $candidate->manufacturer ?? $candidate->product_manufacturer ?? null,
                    'confidence_score' => (int) $candidate->confidence_score,
                    'candidate_id' => $candidate->id,
                ]),
            ]);

            // Mark candidate as assigned
            DB::table('product_image_candidates')->where('id', $candidate->id)->update([
                'rights_status' => 'assigned',
                'is_active' => true,
                'asset_fetch_status' => 'downloaded',
                'source_checksum' => $checksum,
                'downloaded_at' => now(),
                'pixel_width' => $width,
                'pixel_height' => $height,
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Throwable) {
            DB::table('product_image_candidates')->where('id', $candidate->id)->update([
                'asset_fetch_status' => 'error',
                'updated_at' => now(),
            ]);
            return false;
        }
    }

    private function imageDimensions(string $body): array
    {
        try {
            $info = getimagesizefromstring($body);
            if ($info) {
                return [$info[0], $info[1]];
            }
        } catch (\Throwable) {
        }
        return [null, null];
    }

    private function guessExtension(string $url, ?string $contentType): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'avif'])) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        return match ($contentType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'image/svg+xml' => 'svg',
            default => 'jpg',
        };
    }
}
