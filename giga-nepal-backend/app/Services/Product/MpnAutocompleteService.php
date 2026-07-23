<?php

namespace App\Services\Product;

use App\Models\Marketplace\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Fast MPN autocomplete service with ranking and caching.
 *
 * Search priority:
 * 1. Exact MPN
 * 2. MPN beginning with query
 * 3. Normalized MPN match
 * 4. Manufacturer + MPN
 * 5. NeoGiga SKU
 * 6. MPN aliases
 * 7. Product title
 * 8. Brand
 * 9. Description/keyword
 *
 * Targets: cached < 150ms, uncached < 500ms
 */
class MpnAutocompleteService
{
    private MpnNormalizationService $normalization;

    public function __construct(MpnNormalizationService $normalization)
    {
        $this->normalization = $normalization;
    }

    /**
     * Search for products by MPN or keyword.
     *
     * @return array{results: array<int, array>, total: int, query: string, cached: bool}
     */
    public function search(string $query, ?int $marketplaceId = null, int $limit = 20, int $offset = 0): array
    {
        $query = trim($query);

        if (strlen($query) < 1) {
            return ['results' => [], 'total' => 0, 'query' => $query, 'cached' => false];
        }

        // Check cache for popular queries
        $cacheKey = 'mpn_ac:' . md5($query . ':' . ($marketplaceId ?? 'all') . ':' . $limit . ':' . $offset);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return array_merge($cached, ['cached' => true]);
        }

        $results = $this->performSearch($query, $marketplaceId, $limit, $offset);
        $result = [
            'results' => $results['items'],
            'total' => $results['total'],
            'query' => $query,
            'cached' => false,
        ];

        // Cache for 5 minutes for popular queries
        if ($results['total'] > 0) {
            Cache::put($cacheKey, $result, 300);
        }

        return $result;
    }

    /**
     * Perform the actual search with ranking.
     */
    private function performSearch(string $query, ?int $marketplaceId, int $limit, int $offset): array
    {
        $normalized = $this->normalization->normalize($query)['normalized'];
        $variations = $this->normalization->searchVariations($query);

        // Build the query with UNION ALL for ranking
        $allResults = [];

        // Tier 1: Exact MPN match
        $tier1 = $this->searchByMpn($normalized, $marketplaceId, 100);
        $allResults = array_merge($allResults, $tier1);

        // Tier 2: MPN starts with query
        if (count($allResults) < $limit) {
            $tier2 = $this->searchByMpnPrefix($normalized, $marketplaceId, 90, $this->excludeIds($allResults));
            $allResults = array_merge($allResults, $tier2);
        }

        // Tier 3: Normalized MPN variations
        if (count($allResults) < $limit && count($variations) > 1) {
            $tier3 = $this->searchByMpnVariations(array_slice($variations, 1), $marketplaceId, 80, $this->excludeIds($allResults));
            $allResults = array_merge($allResults, $tier3);
        }

        // Tier 4: SKU match
        if (count($allResults) < $limit) {
            $tier4 = $this->searchBySku($query, $marketplaceId, 70, $this->excludeIds($allResults));
            $allResults = array_merge($allResults, $tier4);
        }

        // Tier 5: Product title search
        if (count($allResults) < $limit) {
            $tier5 = $this->searchByTitle($query, $marketplaceId, 50, $this->excludeIds($allResults));
            $allResults = array_merge($allResults, $tier5);
        }

        // Tier 6: Brand/manufacturer search
        if (count($allResults) < $limit) {
            $tier6 = $this->searchByBrand($query, $marketplaceId, 40, $this->excludeIds($allResults));
            $allResults = array_merge($allResults, $tier6);
        }

        // Tier 7: Description search
        if (count($allResults) < $limit) {
            $tier7 = $this->searchByDescription($query, $marketplaceId, 20, $this->excludeIds($allResults));
            $allResults = array_merge($allResults, $tier7);
        }

        // Sort by relevance score
        usort($allResults, fn ($a, $b) => $b['relevance'] <=> $a['relevance']);

        // Paginate
        $total = count($allResults);
        $paginated = array_slice($allResults, $offset, $limit);

        return ['items' => $paginated, 'total' => $total];
    }

    /**
     * Search by exact normalized MPN.
     */
    private function searchByMpn(string $mpn, ?int $marketplaceId, int $relevance): array
    {
        if (empty($mpn)) {
            return [];
        }

        $query = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->whereRaw($this->normalizedMpnExpression() . ' = ?', [$mpn])
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'products.status',
                'product_brands.name as brand_name',
            ])
            ->limit(10);

        return $this->mapResults($query->get(), $relevance, 'exact_mpn');
    }

    /**
     * Search by MPN prefix.
     */
    private function searchByMpnPrefix(string $prefix, ?int $marketplaceId, int $relevance, array $excludeIds): array
    {
        if (empty($prefix) || strlen($prefix) < 2) {
            return [];
        }

        $query = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->whereRaw($this->normalizedMpnExpression() . ' LIKE ?', [$prefix . '%'])
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'products.status',
                'product_brands.name as brand_name',
            ]);

        if (! empty($excludeIds)) {
            $query->whereNotIn('products.id', $excludeIds);
        }

        return $this->mapResults($query->limit(10)->get(), $relevance, 'mpn_prefix');
    }

    /**
     * Search by MPN variations.
     */
    private function searchByMpnVariations(array $variations, ?int $marketplaceId, int $relevance, array $excludeIds): array
    {
        $query = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->where(function ($q) use ($variations) {
                foreach ($variations as $v) {
                    $q->orWhereRaw($this->normalizedMpnExpression() . ' LIKE ?', [$v . '%']);
                }
            })
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'products.status',
                'product_brands.name as brand_name',
            ]);

        if (! empty($excludeIds)) {
            $query->whereNotIn('products.id', $excludeIds);
        }

        return $this->mapResults($query->limit(10)->get(), $relevance, 'mpn_variation');
    }

    /**
     * Search by SKU.
     */
    private function searchBySku(string $sku, ?int $marketplaceId, int $relevance, array $excludeIds): array
    {
        if (empty($sku)) {
            return [];
        }

        $query = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->whereRaw('lower(products.sku) LIKE ?', ['%' . strtolower($sku) . '%'])
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'products.status',
                'product_brands.name as brand_name',
            ]);

        if (! empty($excludeIds)) {
            $query->whereNotIn('products.id', $excludeIds);
        }

        return $this->mapResults($query->limit(10)->get(), $relevance, 'sku_match');
    }

    /**
     * Search by product title.
     */
    private function searchByTitle(string $title, ?int $marketplaceId, int $relevance, array $excludeIds): array
    {
        if (empty($title)) {
            return [];
        }

        $query = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->whereRaw('lower(products.name) LIKE ?', ['%' . strtolower($title) . '%'])
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'products.status',
                'product_brands.name as brand_name',
            ]);

        if (! empty($excludeIds)) {
            $query->whereNotIn('products.id', $excludeIds);
        }

        return $this->mapResults($query->limit(10)->get(), $relevance, 'title_match');
    }

    /**
     * Search by brand/manufacturer.
     */
    private function searchByBrand(string $brand, ?int $marketplaceId, int $relevance, array $excludeIds): array
    {
        if (empty($brand)) {
            return [];
        }

        $query = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->whereRaw('lower(product_brands.name) LIKE ?', ['%' . strtolower($brand) . '%'])
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'products.status',
                'product_brands.name as brand_name',
            ]);

        if (! empty($excludeIds)) {
            $query->whereNotIn('products.id', $excludeIds);
        }

        return $this->mapResults($query->limit(10)->get(), $relevance, 'brand_match');
    }

    /**
     * Search by description.
     */
    private function searchByDescription(string $description, ?int $marketplaceId, int $relevance, array $excludeIds): array
    {
        if (empty($description)) {
            return [];
        }

        $query = Product::query()
            ->published()
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->whereRaw('lower(products.description) LIKE ?', ['%' . strtolower($description) . '%'])
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.mpn',
                'products.slug',
                'products.status',
                'product_brands.name as brand_name',
            ]);

        if (! empty($excludeIds)) {
            $query->whereNotIn('products.id', $excludeIds);
        }

        return $this->mapResults($query->limit(5)->get(), $relevance, 'description_match');
    }

    /**
     * Map query results to autocomplete format.
     */
    private function mapResults($products, int $relevance, string $matchType): array
    {
        $results = [];

        foreach ($products as $product) {
            $results[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'mpn' => $product->mpn,
                'slug' => $product->slug,
                'brand' => $product->brand_name,
                'status' => $product->status,
                'relevance' => $relevance,
                'match_type' => $matchType,
            ];
        }

        return $results;
    }

    /**
     * Get product IDs to exclude from results.
     */
    private function excludeIds(array $results): array
    {
        return array_map(fn ($r) => $r['product_id'], $results);
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
