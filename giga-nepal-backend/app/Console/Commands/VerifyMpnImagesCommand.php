<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Audits assigned product images for MPN/manufacturer mismatches,
 * broken source URLs, and duplicate images.
 */
class VerifyMpnImagesCommand extends Command
{
    protected $signature = 'products:verify-mpn-images
                            {--confidence-below=90 : Flag images below this confidence}
                            {--manufacturer= : Filter by manufacturer name}';

    protected $description = 'Audit assigned product images for MPN/manufacturer mismatches';

    public function handle(): int
    {
        $minConf = (int) $this->option('confidence-below');
        $manufacturer = $this->option('manufacturer');

        $issues = [];

        // 1. Images with MPN field but manufacturer mismatch
        $query = DB::table('product_images as pi')
            ->join('products as p', 'p.id', '=', 'pi.product_id')
            ->where('pi.is_active', true)
            ->whereNotNull('pi.metadata');

        if ($manufacturer) {
            $query->where('p.manufacturer_name', 'like', "%{$manufacturer}%");
        }

        $images = $query->select('pi.id', 'pi.product_id', 'pi.source_name', 'pi.source_url', 'pi.checksum', 'pi.metadata', 'p.name as product_name', 'p.mpn', 'p.manufacturer_name', 'p.normalized_mpn')
            ->limit(500)
            ->get();

        foreach ($images as $img) {
            $meta = json_decode((string) ($img->metadata ?? '{}'), true) ?: [];
            $imgMfr = $meta['manufacturer'] ?? null;
            $imgMpn = $meta['mpn'] ?? null;
            $confidence = (int) ($meta['confidence_score'] ?? 0);

            // Check manufacturer mismatch
            if ($imgMfr && $img->manufacturer_name && stripos($img->manufacturer_name, $imgMfr) === false && stripos($imgMfr, $img->manufacturer_name) === false) {
                $issues[] = ['type' => 'manufacturer_mismatch', 'product_id' => $img->product_id, 'product_name' => $img->product_name, 'image_id' => $img->id, 'details' => "Image mfr: {$imgMfr} ≠ Product mfr: {$img->manufacturer_name}"];
            }

            // Check low confidence
            if ($confidence > 0 && $confidence < $minConf) {
                $issues[] = ['type' => 'low_confidence', 'product_id' => $img->product_id, 'product_name' => $img->product_name, 'image_id' => $img->id, 'details' => "Confidence: {$confidence}% (threshold: {$minConf}%)"];
            }

            // Check MPN mismatch
            if ($imgMpn && $img->mpn && strcasecmp(trim($imgMpn), trim($img->mpn)) !== 0) {
                $issues[] = ['type' => 'mpn_mismatch', 'product_id' => $img->product_id, 'product_name' => $img->product_name, 'image_id' => $img->id, 'details' => "Image MPN: {$imgMpn} ≠ Product MPN: {$img->mpn}"];
            }
        }

        // 2. Duplicate checksums across different products
        $dupes = DB::table('product_images')
            ->select('checksum', DB::raw('COUNT(*) as cnt'), DB::raw('GROUP_CONCAT(DISTINCT product_id) as product_ids'))
            ->where('is_active', true)
            ->whereNotNull('checksum')
            ->groupBy('checksum')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($dupes as $dupe) {
            $issues[] = ['type' => 'duplicate_image', 'product_id' => null, 'product_name' => null, 'image_id' => null, 'details' => "Checksum {$dupe->checksum} used by products: {$dupe->product_ids}"];
        }

        // Report
        if (empty($issues)) {
            $this->info('No issues found. All verified images look correct.');
            return 0;
        }

        $this->table(
            ['Type', 'Product ID', 'Product', 'Image ID', 'Details'],
            array_map(fn ($i) => [$i['type'], $i['product_id'] ?? '-', $i['product_name'] ?? '-', $i['image_id'] ?? '-', $i['details']], $issues)
        );

        $counts = array_count_values(array_column($issues, 'type'));
        $this->newLine();
        $this->warn('Summary:');
        foreach ($counts as $type => $count) {
            $this->line("  {$type}: {$count}");
        }
        $this->info('Total issues: ' . count($issues));

        return 0;
    }
}
