<?php

namespace App\Services\Catalog;

use App\Services\Product\ProductPublicationGate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalogSearchService
{
    private const INDEXED_SOURCE = 'jlcpcb_parts_database';

    public function applyPublicFilters(Builder $query, array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $stock = (string) ($filters['stock'] ?? '');
        $package = trim((string) ($filters['package'] ?? ''));
        $quality = trim((string) ($filters['quality'] ?? ''));

        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $like = $this->likeTerm($q);
                $inner->where('products.name', $this->likeOperator(), $like)
                    ->orWhere('products.sku', $this->likeOperator(), $like)
                    ->orWhere('products.mpn', $this->likeOperator(), $like)
                    ->orWhere('products.manufacturer_name', $this->likeOperator(), $like);

                if ($this->hasSearchTables()) {
                    $inner->orWhereExists(function ($sub) use ($like, $q) {
                        $sub->selectRaw('1')
                            ->from('product_search_documents as psd')
                            ->whereColumn('psd.product_id', 'products.id')
                            ->where('psd.source_code', self::INDEXED_SOURCE)
                            ->where(function ($doc) use ($like, $q) {
                                $this->tsQueryWheres($doc, 'psd.searchable_text', $q);
                                $doc->orWhere('psd.title', $this->likeOperator(), $like)
                                    ->orWhere('psd.sku', $this->likeOperator(), $like)
                                    ->orWhere('psd.mpn', $this->likeOperator(), $like)
                                    ->orWhere('psd.manufacturer', $this->likeOperator(), $like);
                            });
                    });
                }
            });
        }

        if ($stock === 'in' && $this->hasSearchTables()) {
            $query->where(function ($inner) {
                $inner->where('products.stock_quantity', '>', 0)
                    ->orWhereExists($this->facetExistsQuery('stock', 'in_stock'));
            });
        }

        if ($package !== '' && $this->hasSearchTables()) {
            $query->whereExists($this->facetExistsQuery('package', $package));
        }

        if ($quality !== '' && $this->hasSearchTables()) {
            $query->whereExists($this->facetExistsQuery('quality_band', $quality));
        }

        return $query;
    }

    public function publicFacetGroups(array $filters = []): Collection
    {
        if (! $this->hasSearchTables()) {
            return collect();
        }

        $cacheKey = 'catalog:facets:' . sha1(json_encode($filters));

        // Fast path: cached result
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // ponytail: cache lock prevents thundering herd on cold start.
        // LockTimeoutException is caught — never crash a page for facets.
        try {
            $lock = Cache::lock('catalog:facets:lock', 15);
            if ($lock->get()) {
                try {
                    return Cache::remember($cacheKey, 3600, fn () => $this->computeFacetGroups($filters));
                } finally {
                    $lock->release();
                }
            }
            if ($lock->block(5)) {
                try {
                    return Cache::remember($cacheKey, 3600, fn () => $this->computeFacetGroups($filters));
                } finally {
                    $lock->release();
                }
            }
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException) {
            // Lock contention — skip facets gracefully.
        }
        return Cache::remember($cacheKey, 3600, fn () => $this->computeFacetGroups($filters));
    }

    private function computeFacetGroups(array $filters): Collection
    {

        $q = trim((string) ($filters['q'] ?? ''));

        $query = DB::table('product_facet_values as pfv')
            ->join('product_search_documents as psd', function ($join) {
                $join->on('psd.product_id', '=', 'pfv.product_id')
                    ->whereColumn('psd.source_code', 'pfv.source_code');
            })
            ->join('products as p', 'p.id', '=', 'pfv.product_id')
            ->where('pfv.source_code', self::INDEXED_SOURCE)
            ->whereIn('pfv.facet_name', ['manufacturer', 'category', 'stock', 'package', 'quality_band'])
            ->when($q !== '', function ($query) use ($q) {
                $this->tsQueryWheres($query, 'psd.searchable_text', $q);
            });
        app(ProductPublicationGate::class)->apply($query, 'p');

        return $query
            ->select('pfv.facet_name', 'pfv.facet_value', DB::raw('count(distinct pfv.product_id) as product_count'))
            ->groupBy('pfv.facet_name', 'pfv.facet_value')
            ->orderBy('pfv.facet_name')
            ->orderByDesc('product_count')
            ->limit(80)
            ->get()
            ->groupBy('facet_name');
    }

    public function indexedSummary(): array
    {
        if (! $this->hasSearchTables()) {
            return ['documents' => 0, 'facets' => 0, 'approved_documents' => 0];
        }

        return Cache::remember('catalog:search-summary', 3600, function () {
            return $this->computeIndexedSummary();
        });
    }

    private function computeIndexedSummary(): array
    {

        $documents = DB::table('product_search_documents as psd')
            ->join('products as p', 'p.id', '=', 'psd.product_id');
        app(ProductPublicationGate::class)->apply($documents, 'p');

        $facets = DB::table('product_facet_values as pfv')
            ->join('products as p', 'p.id', '=', 'pfv.product_id');
        app(ProductPublicationGate::class)->apply($facets, 'p');

        return [
            'documents' => (clone $documents)->count('psd.id'),
            'facets' => (clone $facets)->count('pfv.id'),
            'searchable_documents' => (clone $documents)
                ->where('psd.source_code', self::INDEXED_SOURCE)
                ->count('psd.id'),
            'approved_documents' => (clone $documents)
                ->where('psd.source_code', self::INDEXED_SOURCE)
                ->where('psd.review_status', 'approved')
                ->count('psd.id'),
        ];
    }

    private function facetExistsQuery(string $name, string $value): \Closure
    {
        return function ($sub) use ($name, $value) {
            $sub->selectRaw('1')
                ->from('product_facet_values as pfv')
                ->join('product_search_documents as psd', function ($join) {
                    $join->on('psd.product_id', '=', 'pfv.product_id')
                        ->whereColumn('psd.source_code', 'pfv.source_code');
                })
                ->whereColumn('pfv.product_id', 'products.id')
                ->where('pfv.source_code', self::INDEXED_SOURCE)
                ->where('pfv.facet_name', $name)
                ->where('pfv.facet_value', $value);
        };
    }

    private function hasSearchTables(): bool
    {
        return Schema::hasTable('product_search_documents') && Schema::hasTable('product_facet_values');
    }

    private function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    private function likeTerm(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }

    /**
     * Full-text search using PostgreSQL tsvector when available.
     * Falls back to ILIKE for SQLite and pre-migration states.
     * ponytail: plainto_tsquery is safe for arbitrary user input.
     */
    private function tsQueryWheres($query, string $column, string $term): void
    {
        $isPgsql = DB::connection()->getDriverName() === 'pgsql';
        $hasTsVector = $isPgsql && Schema::hasColumn('product_search_documents', 'search_vector');

        if ($hasTsVector) {
            $query->whereRaw(
                "{$column} @@ plainto_tsquery('english', ?)",
                [$term]
            );
        } else {
            $query->where($column, $this->likeOperator(), $this->likeTerm($term));
        }
    }

    /**
     * Cached public product count for the products listing page.
     * ponytail: 5-min cache, busted by page-cache-version bump.
     */
    public function cachedPublicProductCount(): int
    {
        return Cache::remember('catalog:public-product-count', 300, function () {
            $query = DB::table('products as p');
            app(ProductPublicationGate::class)->apply($query, 'p');
            return $query->count('p.id');
        });
    }
}
