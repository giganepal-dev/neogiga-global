<?php

namespace App\Services\Smd;

use Illuminate\Support\Facades\DB;

class SmdIdentificationService
{
    public function __construct(
        private readonly SmdMarkingNormalizer $normalizer = new SmdMarkingNormalizer(),
        private readonly SmdConfidenceScorer $scorer = new SmdConfidenceScorer(),
    ) {}

    /**
     * Search SMD markings and return ranked candidates.
     */
    public function search(array $params): array
    {
        $marking = $params['marking'] ?? '';
        if ($marking === '') {
            return [];
        }

        $normalized = $this->normalizer->normalize($marking);
        $prefixes = $this->normalizer->prefixes($marking);

        // Find matching marking codes (exact or prefix)
        $codes = DB::table('smd_marking_codes')
            ->where('normalized_marking', $normalized)
            ->orWhere(function ($q) use ($prefixes) {
                $q->where('first_two_characters', $prefixes['first_two'])
                  ->where('marking_length', $prefixes['length']);
            })
            ->limit(50)
            ->get();

        if ($codes->isEmpty()) {
            // Fallback to prefix-only search
            $codes = DB::table('smd_marking_codes')
                ->where('first_two_characters', $prefixes['first_two'])
                ->limit(30)
                ->get();
        }

        if ($codes->isEmpty()) {
            return [];
        }

        $codeIds = $codes->pluck('id');
        $query = DB::table('smd_marking_matches')
            ->join('smd_marking_codes', 'smd_marking_matches.smd_marking_code_id', '=', 'smd_marking_codes.id')
            ->leftJoin('smd_packages', 'smd_marking_matches.package_id', '=', 'smd_packages.id')
            ->leftJoin('manufacturers', 'smd_marking_matches.manufacturer_id', '=', 'manufacturers.id')
            ->leftJoin('products', 'smd_marking_matches.product_id', '=', 'products.id')
            ->whereIn('smd_marking_code_id', $codeIds->toArray())
            ->select(
                'smd_marking_matches.*',
                'smd_marking_codes.display_marking',
                'smd_packages.canonical_name as package_name',
                'smd_packages.pin_count',
                'manufacturers.name as manufacturer_name',
                'products.name as product_name',
                'products.slug as product_slug',
            );

        // Apply filters
        if (! empty($params['package'])) {
            $pkgNormalized = $this->normalizer->normalize($params['package']);
            $query->where(function ($q) use ($pkgNormalized) {
                $q->where('smd_packages.normalized_name', $pkgNormalized)
                  ->orWhere('smd_packages.canonical_name', 'ILIKE', "%{$pkgNormalized}%")
                  ->orWhere('smd_marking_matches.package_text', 'ILIKE', "%{$pkgNormalized}%");
            });
        }

        if (! empty($params['pins'])) {
            $query->where('smd_packages.pin_count', (int) $params['pins']);
        }

        if (! empty($params['manufacturer'])) {
            $query->where('manufacturers.name', 'ILIKE', '%' . $params['manufacturer'] . '%');
        }

        if (! empty($params['function'])) {
            $query->where('smd_marking_matches.component_function', 'ILIKE', '%' . $params['function'] . '%');
        }

        $matches = $query->orderByDesc('smd_marking_matches.match_confidence')->limit(30)->get();

        return $matches->map(function ($match) use ($params) {
            $m = (array) $match;
            $scored = $this->scorer->score(
                [
                    'marking_matches_exact' => ($m['display_marking'] ?? '') === $params['marking'],
                    'package_matches' => ! empty($params['package']),
                    'package_conflict' => false,
                    'manufacturer_matches' => ! empty($params['manufacturer']),
                    'manufacturer_conflict' => false,
                    'pin_count_matches' => ! empty($params['pins']) && ($m['pin_count'] ?? 0) == (int) $params['pins'],
                    'function_matches' => ! empty($params['function']),
                    'electrical_context_match' => false,
                ],
                $m,
                (bool) ($m['product_id'] ?? false),
                ($m['verification_status'] ?? '') === 'verified',
            );

            return [
                'id' => $m['id'] ?? null,
                'marking' => $m['display_marking'] ?? '',
                'mpn' => $m['candidate_mpn'] ?? '',
                'manufacturer' => $m['manufacturer_name'] ?? null,
                'package' => $m['package_name'] ?? $m['package_text'] ?? null,
                'pins' => $m['pin_count'] ?? null,
                'function' => $m['component_function'] ?? null,
                'characteristics' => $m['characteristic_text'] ?? null,
                'confidence_score' => $scored['score'],
                'confidence_class' => $scored['class'],
                'confidence_factors' => $scored['factors'],
                'has_product' => (bool) ($m['product_id'] ?? false),
                'product_name' => $m['product_name'] ?? null,
                'product_slug' => $m['product_slug'] ?? null,
                'verification_status' => $m['verification_status'] ?? 'unverified',
                'source_url' => $m['source_url'] ?? null,
            ];
        })->all();
    }

    /**
     * Log a user identification search.
     */
    public function logSearch(array $params, int $resultCount, ?int $userId = null): void
    {
        DB::table('smd_identification_searches')->insert([
            'user_id' => $userId,
            'marking_query' => $params['marking'] ?? '',
            'package_query' => $params['package'] ?? null,
            'manufacturer_query' => $params['manufacturer'] ?? null,
            'function_query' => $params['function'] ?? null,
            'board_context' => $params['context'] ?? null,
            'result_count' => $resultCount,
            'created_at' => now(),
        ]);
    }
}
