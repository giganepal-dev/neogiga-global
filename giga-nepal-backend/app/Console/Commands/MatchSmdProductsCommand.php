<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MatchSmdProductsCommand extends Command
{
    protected $signature = 'neogiga:smd-match-products
                            {--limit=0 : Max matches to process}
                            {--min-confidence=50 : Minimum score to auto-link}';

    protected $description = 'Match imported SMD marking candidates against existing NeoGiga products.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $minConfidence = (int) $this->option('min-confidence');

        $this->info('Matching SMD candidates against NeoGiga catalog...');

        // Get unverified matches
        $query = DB::table('smd_marking_matches')
            ->where('verification_status', 'unverified')
            ->whereNull('product_id')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $matches = $query->get();
        $total = $matches->count();
        $linked = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($total);

        foreach ($matches as $match) {
            $result = $this->tryMatch($match);

            if ($result['linked']) {
                DB::table('smd_marking_matches')->where('id', $match->id)->update([
                    'product_id' => $result['product_id'],
                    'manufacturer_id' => $result['manufacturer_id'],
                    'match_confidence' => $result['confidence'],
                    'verification_status' => $result['confidence'] >= $minConfidence ? 'matched' : 'unverified',
                    'updated_at' => now(),
                ]);
                $linked++;
            } else {
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Linked: {$linked}, Skipped: {$skipped}");

        return self::SUCCESS;
    }

    private function tryMatch(object $match): array
    {
        $mpn = $match->normalized_mpn;
        $candidateMpn = $match->candidate_mpn;

        // Strategy 1: Exact MPN match in products table
        $product = DB::table('products')
            ->where(function ($q) use ($mpn, $candidateMpn) {
                $q->whereRaw('upper(mpn) = ?', [$mpn])
                  ->orWhereRaw('upper(sku) = ?', [$mpn])
                  ->orWhere('mpn', 'ILIKE', $candidateMpn)
                  ->orWhere('sku', 'ILIKE', $candidateMpn);
            })
            ->whereIn('status', ['active', 'approved'])
            ->select('id', 'name', 'mpn', 'manufacturer_id', 'brand_id')
            ->first();

        if ($product) {
            // Verify: check if the function matches what we'd expect
            $mfrId = $product->manufacturer_id;
            $confidence = 75; // MPN found in catalog

            return [
                'linked' => true,
                'product_id' => $product->id,
                'manufacturer_id' => $mfrId,
                'confidence' => $confidence,
            ];
        }

        // Strategy 2: MPN contains match (partial)
        $product2 = DB::table('products')
            ->where(function ($q) use ($candidateMpn, $mpn) {
                $q->where('mpn', 'ILIKE', '%' . $candidateMpn . '%')
                  ->orWhere('sku', 'ILIKE', '%' . $candidateMpn . '%')
                  ->orWhere('name', 'ILIKE', '%' . $candidateMpn . '%');
            })
            ->whereIn('status', ['active', 'approved'])
            ->select('id', 'name', 'mpn', 'manufacturer_id')
            ->first();

        if ($product2 && strlen($candidateMpn) >= 4) {
            return [
                'linked' => true,
                'product_id' => $product2->id,
                'manufacturer_id' => $product2->manufacturer_id,
                'confidence' => 55, // partial match
            ];
        }

        return ['linked' => false, 'product_id' => null, 'manufacturer_id' => null, 'confidence' => 0];
    }
}
