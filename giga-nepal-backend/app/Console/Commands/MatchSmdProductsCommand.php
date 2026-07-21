<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MatchSmdProductsCommand extends Command
{
    protected $signature = 'neogiga:smd-match-products';
    protected $description = 'Match SMD marking candidates against NeoGiga products with batch SQL JOINs.';

    public function handle(): int
    {
        $this->info('Strategy 1: Exact MPN/SKU match (75% confidence)...');

        // Single UPDATE: JOIN smd_marking_matches ↔ products on normalized MPN
        $exact = DB::statement('
            UPDATE smd_marking_matches
            SET product_id = p.id,
                manufacturer_id = p.manufacturer_id,
                match_confidence = 75,
                verification_status = \'matched\',
                updated_at = NOW()
            FROM products p
            WHERE smd_marking_matches.product_id IS NULL
              AND smd_marking_matches.verification_status = \'unverified\'
              AND p.status IN (\'active\', \'approved\')
              AND UPPER(p.mpn) = smd_marking_matches.normalized_mpn
        ');

        $this->info("  Exact MPN matches: {$exact}");

        // Strategy 2: SKU match (75% confidence)
        $sku = DB::statement('
            UPDATE smd_marking_matches
            SET product_id = p.id,
                manufacturer_id = p.manufacturer_id,
                match_confidence = 75,
                verification_status = \'matched\',
                updated_at = NOW()
            FROM products p
            WHERE smd_marking_matches.product_id IS NULL
              AND smd_marking_matches.verification_status = \'unverified\'
              AND p.status IN (\'active\', \'approved\')
              AND UPPER(p.sku) = smd_marking_matches.normalized_mpn
        ');

        $this->info("  Exact SKU matches: {$sku}");

        // Strategy 3: Case-insensitive MPN match (60% confidence)
        $ci = DB::statement('
            UPDATE smd_marking_matches
            SET product_id = p.id,
                manufacturer_id = p.manufacturer_id,
                match_confidence = 60,
                verification_status = \'matched\',
                updated_at = NOW()
            FROM products p
            WHERE smd_marking_matches.product_id IS NULL
              AND smd_marking_matches.verification_status = \'unverified\'
              AND p.status IN (\'active\', \'approved\')
              AND p.mpn ILIKE smd_marking_matches.candidate_mpn
        ');

        $this->info("  Case-insensitive MPN matches: {$ci}");

        // Strategy 4: MPN contains candidate (55% confidence, min 4 chars)
        $contains = DB::statement("
            UPDATE smd_marking_matches
            SET product_id = p.id,
                manufacturer_id = p.manufacturer_id,
                match_confidence = 55,
                verification_status = 'matched',
                updated_at = NOW()
            FROM products p
            WHERE smd_marking_matches.product_id IS NULL
              AND smd_marking_matches.verification_status = 'unverified'
              AND p.status IN ('active', 'approved')
              AND length(smd_marking_matches.candidate_mpn) >= 4
              AND (p.mpn ILIKE '%' || smd_marking_matches.candidate_mpn || '%'
                   OR p.sku ILIKE '%' || smd_marking_matches.candidate_mpn || '%')
        ");

        $this->info("  Partial MPN/SKU matches: {$contains}");

        // Final stats
        $total = DB::table('smd_marking_matches')->whereNotNull('product_id')->count();
        $this->info("Done. {$total} matches now linked to NeoGiga products.");

        return self::SUCCESS;
    }
}
