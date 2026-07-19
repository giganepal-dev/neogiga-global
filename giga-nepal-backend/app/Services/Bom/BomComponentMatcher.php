<?php

namespace App\Services\Bom;

use App\Models\Marketplace\Product;
use Illuminate\Support\Facades\DB;

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
 *     0  no match (near-miss `suggestions` may still be present)
 */
class BomComponentMatcher
{
    public static function normalizeMpn(?string $mpn): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $mpn) ?? '');
    }

    /**
     * @param  iterable<int, array{line_no?:int, mpn?:?string, manufacturer?:?string}>  $lines
     * @return array<int, array{matched_product_id:?int, match_status:string, match_confidence:int, candidates:array, suggestions:array}>
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

        // Near-miss lookups only for MPNs with no exact bucket (e.g. a trailing
        // suffix/digit differs: NE555P vs NE555DR). Kept in a separate
        // `suggestions` key so import-flow persistence contracts are untouched.
        $missing = array_values(array_filter(array_keys($norms), fn ($n) => ! isset($byMpn[$n])));
        $suggestions = $this->suggestionsForMissing($missing);

        $results = [];
        foreach ($lines as $index => $line) {
            $key = $line['line_no'] ?? $index;
            $results[$key] = $this->resolve($line['mpn'] ?? null, $line['manufacturer'] ?? null, $byMpn, $suggestions);
        }

        return $results;
    }

    /** @return array{matched_product_id:?int, match_status:string, match_confidence:int, candidates:array, suggestions:array} */
    private function resolve(?string $mpn, ?string $manufacturer, array $byMpn, array $suggestions = []): array
    {
        $norm = self::normalizeMpn($mpn);
        $none = [
            'matched_product_id' => null,
            'match_status' => 'none',
            'match_confidence' => 0,
            'candidates' => [],
            'suggestions' => $norm !== '' ? ($suggestions[$norm] ?? []) : [],
        ];

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
                'suggestions' => [],
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
                    'suggestions' => [],
                ];
            }
        }

        return [
            'matched_product_id' => null,
            'match_status' => 'multiple',
            'match_confidence' => 60,
            'candidates' => $candidates,
            'suggestions' => [],
        ];
    }

    /**
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
     * Near-miss suggestions for MPNs with no exact match. PostgreSQL uses the
     * trigram index on products.mpn (similarity ranking); other drivers fall
     * back to a shortened-prefix LIKE so tests and dev SQLite keep working.
     *
     * @param  array<int, string>  $norms
     * @return array<string, array<int, array>>
     */
    private function suggestionsForMissing(array $norms): array
    {
        if ($norms === []) {
            return [];
        }

        // ponytail: cap lookups per request; giant BOMs still get suggestions
        // for their first 50 unmatched lines, which is what a human reviews.
        $norms = array_slice($norms, 0, 50);

        $isPgsql = DB::connection()->getDriverName() === 'pgsql';
        $out = [];

        foreach ($norms as $norm) {
            if (strlen($norm) < 4) {
                continue;
            }

            $query = Product::query()
                ->published()
                ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id');

            if ($isPgsql) {
                $query->whereRaw('products.mpn % ?', [$norm])
                    ->orderByRaw('similarity(upper(products.mpn), ?) DESC', [$norm]);
            } else {
                $prefix = substr($norm, 0, max(4, strlen($norm) - 2));
                $query->whereRaw("{$this->normalizedMpnExpression()} LIKE ?", [$prefix.'%'])
                    ->orderBy('products.mpn');
            }

            $rows = $query->limit(3)->get([
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

    private function normalizedMpnExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "upper(replace(replace(replace(replace(coalesce(products.mpn, ''), ' ', ''), char(9), ''), char(10), ''), char(13), ''))",
            'mysql', 'mariadb' => "upper(regexp_replace(coalesce(products.mpn, ''), '[[:space:]]+', ''))",
            default => "upper(regexp_replace(coalesce(products.mpn, ''), '\\s+', '', 'g'))",
        };
    }
}
