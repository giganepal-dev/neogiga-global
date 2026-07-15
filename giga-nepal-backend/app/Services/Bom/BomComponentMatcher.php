<?php

namespace App\Services\Bom;

use App\Models\Marketplace\Product;

/**
 * Matches BOM lines to catalog products by manufacturer part number.
 *
 * MPNs are normalized (whitespace removed, upper-cased) to match the functional
 * index `products_brand_normalized_mpn_idx`. Matching is read-only; the caller
 * persists the results.
 *
 * Confidence:
 *   100  single exact normalized-MPN match
 *    90  ambiguous MPN disambiguated by manufacturer/brand
 *    60  multiple MPN matches, needs human review (matched_product_id = null)
 *     0  no match
 */
class BomComponentMatcher
{
    public static function normalizeMpn(?string $mpn): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $mpn) ?? '');
    }

    /**
     * @param  iterable<int, array{line_no?:int, mpn?:?string, manufacturer?:?string}>  $lines
     * @return array<int, array{matched_product_id:?int, match_status:string, match_confidence:int, candidates:array}>
     */
    public function match(iterable $lines): array
    {
        $lines = is_array($lines) ? $lines : iterator_to_array($lines);

        $norms = [];
        foreach ($lines as $line) {
            $n = self::normalizeMpn($line['mpn'] ?? null);
            if ($n !== '') {
                $norms[$n] = true;
            }
        }

        $byMpn = $this->productsByNormalizedMpn(array_keys($norms));

        $results = [];
        foreach ($lines as $index => $line) {
            $key = $line['line_no'] ?? $index;
            $results[$key] = $this->resolve($line['mpn'] ?? null, $line['manufacturer'] ?? null, $byMpn);
        }

        return $results;
    }

    /** @return array{matched_product_id:?int, match_status:string, match_confidence:int, candidates:array} */
    private function resolve(?string $mpn, ?string $manufacturer, array $byMpn): array
    {
        $none = ['matched_product_id' => null, 'match_status' => 'none', 'match_confidence' => 0, 'candidates' => []];

        $norm = self::normalizeMpn($mpn);
        if ($norm === '' || ! isset($byMpn[$norm])) {
            return $none;
        }

        $candidates = $byMpn[$norm];

        if (count($candidates) === 1) {
            return [
                'matched_product_id' => $candidates[0]['product_id'],
                'match_status' => 'exact',
                'match_confidence' => 100,
                'candidates' => [],
            ];
        }

        // Ambiguous MPN: try to disambiguate by manufacturer against the brand name.
        if ($manufacturer !== null && $manufacturer !== '') {
            $mfr = self::normalizeMpn($manufacturer);
            $narrowed = array_values(array_filter(
                $candidates,
                fn ($c) => $c['brand'] !== null && str_contains(self::normalizeMpn($c['brand']), $mfr)
            ));
            if (count($narrowed) === 1) {
                return [
                    'matched_product_id' => $narrowed[0]['product_id'],
                    'match_status' => 'exact',
                    'match_confidence' => 90,
                    'candidates' => $candidates,
                ];
            }
        }

        return [
            'matched_product_id' => null,
            'match_status' => 'multiple',
            'match_confidence' => 60,
            'candidates' => $candidates,
        ];
    }

    /**
     * @param  array<int, string>  $norms
     * @return array<string, array<int, array{product_id:int, name:string, sku:?string, mpn:?string, brand:?string}>>
     */
    private function productsByNormalizedMpn(array $norms): array
    {
        if ($norms === []) {
            return [];
        }

        $expr = "upper(regexp_replace(coalesce(products.mpn, ''), '\s+', '', 'g'))";
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
                'product_brands.name as brand_name',
            ]);

        $map = [];
        foreach ($rows as $row) {
            $n = self::normalizeMpn($row->mpn);
            $map[$n][] = [
                'product_id' => (int) $row->id,
                'name' => $row->name,
                'sku' => $row->sku,
                'mpn' => $row->mpn,
                'brand' => $row->brand_name,
            ];
        }

        return $map;
    }
}
