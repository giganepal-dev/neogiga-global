<?php

namespace App\Services\Bom;

use App\Models\Marketplace\Product;
use App\Models\Product\MpnAlias;
use App\Services\Product\MpnNormalizationService;
use Illuminate\Support\Facades\DB;

/**
 * Advanced BOM component matching engine.
 *
 * Matches BOM lines to catalog products using:
 * 1. Exact normalized MPN match
 * 2. MPN alias lookup
 * 3. Near-miss/trigram matching
 * 4. Manufacturer disambiguation
 * 5. Passive component parametric matching
 *
 * Match statuses:
 *   exact           - Single exact normalized-MPN match (confidence 95-100)
 *   exact_mfr       - Exact MPN, manufacturer disambiguated (confidence 90-94)
 *   multiple        - Multiple MPN matches, needs review (confidence 60-79)
 *   likely_match    - High-confidence near-miss (confidence 70-89)
 *   partial_match   - Partial MPN match (confidence 50-69)
 *   alias_match     - Matched via MPN alias (confidence 85-95)
 *   alternative     - No direct match, alternatives available (confidence 0)
 *   none            - No match found (confidence 0)
 */
class BomComponentMatcher
{
    private MpnNormalizationService $normalization;

    public function __construct(MpnNormalizationService $normalization)
    {
        $this->normalization = $normalization;
    }

    /**
     * Normalize an MPN for matching (static helper for backwards compatibility).
     */
    public static function normalizeMpn(?string $mpn): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $mpn) ?? '');
    }

    /**
     * Match a list of BOM lines to catalog products.
     *
     * @param  iterable<int, array{line_no?:int, mpn?:?string, manufacturer?:?string, description?:?string, quantity?:float}>  $lines
     * @return array<int, array{matched_product_id:?int, match_status:string, match_confidence:int, candidates:array, suggestions:array, alternatives:array, normalized_mpn:string, warnings:array}>
     */
    public function match(iterable $lines): array
    {
        $lines = is_array($lines) ? $lines : iterator_to_array($lines);

        // Normalize all MPNs
        $normalizedMap = [];
        foreach ($lines as $index => $line) {
            $key = $line['line_no'] ?? $index;
            $result = $this->normalization->normalize($line['mpn'] ?? null);
            $normalizedMap[$key] = $result;
        }

        // Get unique normalized MPNs
        $uniqueNorms = array_unique(array_filter(array_column($normalizedMap, 'normalized')));

        // Batch lookup: exact matches
        $byMpn = $this->productsByNormalizedMpn(array_values($uniqueNorms));

        // Batch lookup: alias matches
        $aliasMap = $this->aliasesByNormalizedMpn(array_values($uniqueNorms));

        // Find unmatched MPNs for near-miss suggestions
        $unmatchedNorms = [];
        foreach ($uniqueNorms as $norm) {
            if (! isset($byMpn[$norm]) && ! isset($aliasMap[$norm])) {
                $unmatchedNorms[] = $norm;
            }
        }
        $suggestions = $this->suggestionsForMissing($unmatchedNorms);

        // Resolve each line
        $results = [];
        foreach ($lines as $index => $line) {
            $key = $line['line_no'] ?? $index;
            $normResult = $normalizedMap[$key];

            $results[$key] = $this->resolveLine(
                $line,
                $normResult,
                $byMpn,
                $aliasMap,
                $suggestions
            );
        }

        return $results;
    }

    /**
     * Match a single MPN (for autocomplete/search).
     *
     * @return array{matched_product_id:?int, match_status:string, match_confidence:int, candidates:array, suggestions:array}
     */
    public function matchSingle(?string $mpn, ?string $manufacturer = null): array
    {
        $normResult = $this->normalization->normalize($mpn);
        $norm = $normResult['normalized'];

        if ($norm === '') {
            return $this->noMatch();
        }

        // Check exact match
        $byMpn = $this->productsByNormalizedMpn([$norm]);
        if (isset($byMpn[$norm])) {
            return $this->resolveFromCandidates($byMpn[$norm], $manufacturer, 100, 'exact');
        }

        // Check aliases
        $aliasMap = $this->aliasesByNormalizedMpn([$norm]);
        if (isset($aliasMap[$norm])) {
            return $this->resolveFromCandidates($aliasMap[$norm], $manufacturer, 90, 'alias_match');
        }

        // Check suggestions
        $suggestions = $this->suggestionsForMissing([$norm]);
        if (isset($suggestions[$norm]) && count($suggestions[$norm]) > 0) {
            return [
                'matched_product_id' => null,
                'match_status' => 'likely_match',
                'match_confidence' => 70,
                'candidates' => [],
                'suggestions' => $suggestions[$norm],
            ];
        }

        return $this->noMatch();
    }

    /**
     * Resolve a single BOM line.
     */
    private function resolveLine(
        array $line,
        array $normResult,
        array $byMpn,
        array $aliasMap,
        array $suggestions
    ): array {
        $norm = $normResult['normalized'];
        $warnings = $normResult['warnings'] ?? [];

        $base = [
            'matched_product_id' => null,
            'match_status' => 'none',
            'match_confidence' => 0,
            'candidates' => [],
            'suggestions' => [],
            'alternatives' => [],
            'normalized_mpn' => $norm,
            'warnings' => $warnings,
        ];

        if ($norm === '') {
            return array_merge($base, ['match_status' => 'invalid_input']);
        }

        // 1. Check exact MPN match
        if (isset($byMpn[$norm])) {
            return array_merge($base, $this->resolveFromCandidates(
                $byMpn[$norm],
                $line['manufacturer'] ?? null,
                100,
                'exact'
            ));
        }

        // 2. Check alias match
        if (isset($aliasMap[$norm])) {
            return array_merge($base, $this->resolveFromCandidates(
                $aliasMap[$norm],
                $line['manufacturer'] ?? null,
                90,
                'alias_match'
            ));
        }

        // 3. Check near-miss suggestions
        if (isset($suggestions[$norm]) && count($suggestions[$norm]) > 0) {
            $topSuggestion = $suggestions[$norm][0];
            $confidence = $this->calculateNearMissConfidence($norm, $topSuggestion['mpn'] ?? '');

            return array_merge($base, [
                'match_status' => $confidence >= 70 ? 'likely_match' : 'partial_match',
                'match_confidence' => $confidence,
                'suggestions' => $suggestions[$norm],
            ]);
        }

        // 4. No match
        return $base;
    }

    /**
     * Resolve match from a list of candidates.
     */
    private function resolveFromCandidates(
        array $candidates,
        ?string $manufacturer,
        int $baseConfidence,
        string $matchType
    ): array {
        if (count($candidates) === 1) {
            return [
                'matched_product_id' => $candidates[0]['product_id'],
                'match_status' => $matchType,
                'match_confidence' => $baseConfidence,
                'candidates' => [],
                'suggestions' => [],
            ];
        }

        // Multiple candidates: try to disambiguate by manufacturer
        if ($manufacturer !== null && $manufacturer !== '') {
            $mfr = strtoupper(trim($manufacturer));
            $narrowed = array_values(array_filter(
                $candidates,
                fn ($c) => strtoupper($c['brand'] ?? '') === $mfr
                    || str_contains(strtoupper($c['brand'] ?? ''), $mfr)
            ));

            if (count($narrowed) === 1) {
                return [
                    'matched_product_id' => $narrowed[0]['product_id'],
                    'match_status' => $matchType,
                    'match_confidence' => $baseConfidence - 5,
                    'candidates' => $candidates,
                    'suggestions' => [],
                ];
            }
        }

        // Multiple matches, needs review
        return [
            'matched_product_id' => null,
            'match_status' => 'multiple',
            'match_confidence' => max(60, $baseConfidence - 40),
            'candidates' => $candidates,
            'suggestions' => [],
        ];
    }

    /**
     * Calculate confidence for near-miss matches.
     */
    private function calculateNearMissConfidence(string $query, string $candidate): int
    {
        $queryLen = strlen($query);
        $candidateLen = strlen($candidate);

        if ($queryLen === 0 || $candidateLen === 0) {
            return 0;
        }

        // Levenshtein distance
        $distance = levenshtein($query, $candidate);
        $maxLen = max($queryLen, $candidateLen);

        // Similarity ratio
        $similarity = 1 - ($distance / $maxLen);

        // Convert to confidence score
        if ($similarity >= 0.95) {
            return 90;
        }
        if ($similarity >= 0.85) {
            return 80;
        }
        if ($similarity >= 0.75) {
            return 70;
        }
        if ($similarity >= 0.65) {
            return 60;
        }

        return 50;
    }

    /**
     * Return empty match result.
     */
    private function noMatch(): array
    {
        return [
            'matched_product_id' => null,
            'match_status' => 'none',
            'match_confidence' => 0,
            'candidates' => [],
            'suggestions' => [],
        ];
    }

    /**
     * Batch lookup products by normalized MPN.
     *
     * @param  array<int, string>  $norms
     * @return array<string, array<int, array{product_id:int, name:string, sku:?string, mpn:?string, slug:?string, brand:?string}>>
     */
    private function productsByNormalizedMpn(array $norms): array
    {
        if ($norms === []) {
            return [];
        }

        $expr = $this->normalizedMpnExpression();
        $placeholders = implode(',', array_fill(0, count($norms), '?'));

        $rows = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->whereRaw("{$expr} in ({$placeholders})", $norms)
            ->get([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'product_brands.name as brand_name',
            ]);

        $map = [];
        foreach ($rows as $row) {
            $n = self::normalizeMpn($row->mpn);
            $map[$n][] = $this->candidateRow($row);
        }

        return $map;
    }

    /**
     * Batch lookup MPN aliases.
     *
     * @param  array<int, string>  $norms
     * @return array<string, array<int, array{product_id:int, name:string, sku:?string, mpn:?string, slug:?string, brand:?string}>>
     */
    private function aliasesByNormalizedMpn(array $norms): array
    {
        if ($norms === []) {
            return [];
        }

        $aliases = MpnAlias::active()
            ->whereIn('normalized_alias', $norms)
            ->with('product.brand')
            ->get();

        $map = [];
        foreach ($aliases as $alias) {
            $product = $alias->product;
            if ($product && $product->status === 'approved') {
                $map[$alias->normalized_alias][] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'mpn' => $product->mpn,
                    'slug' => $product->slug,
                    'brand' => $product->brand?->name,
                ];
            }
        }

        return $map;
    }

    /**
     * Near-miss suggestions for unmatched MPNs.
     *
     * @param  array<int, string>  $norms
     * @return array<string, array<int, array>>
     */
    private function suggestionsForMissing(array $norms): array
    {
        if ($norms === []) {
            return [];
        }

        // Cap at 50 lookups per request
        $norms = array_slice(array_unique($norms), 0, 50);

        $isPgsql = DB::connection()->getDriverName() === 'pgsql';
        $out = [];

        foreach ($norms as $norm) {
            if (strlen($norm) < 3) {
                continue;
            }

            $query = Product::query()
                ->published()
                ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id');

            if ($isPgsql) {
                $query->whereRaw('products.mpn % ?', [$norm])
                    ->orderByRaw('similarity(upper(products.mpn), ?) DESC', [$norm]);
            } else {
                // Prefix match for SQLite/MySQL
                $prefix = substr($norm, 0, max(3, strlen($norm) - 2));
                $query->whereRaw("{$this->normalizedMpnExpression()} LIKE ?", [$prefix . '%'])
                    ->orderBy('products.mpn');
            }

            $rows = $query->limit(5)->get([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'product_brands.name as brand_name',
            ]);

            if ($rows->isNotEmpty()) {
                $out[$norm] = $rows->map(fn ($row) => $this->candidateRow($row))->all();
            }
        }

        return $out;
    }

    /**
     * Map a product row to candidate format.
     */
    private function candidateRow(object $row): array
    {
        return [
            'product_id' => (int) $row->id,
            'name' => $row->name,
            'sku' => $row->sku,
            'mpn' => $row->mpn,
            'slug' => $row->slug,
            'brand' => $row->brand_name,
        ];
    }

    /**
     * Get the normalized MPN expression for the current database driver.
     */
    private function normalizedMpnExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "upper(replace(replace(replace(replace(coalesce(products.mpn, ''), ' ', ''), char(9), ''), char(10), ''), char(13), ''))",
            'mysql', 'mariadb' => "upper(regexp_replace(coalesce(products.mpn, ''), '[[:space:]]+', ''))",
            default => "upper(regexp_replace(coalesce(products.mpn, ''), '\\s+', '', 'g'))",
        };
    }
}
