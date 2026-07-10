<?php

namespace App\Services\Catalog;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalogSearchService
{
    private const PUBLIC_STATUSES = ['active', 'approved', 'published'];
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
                    $inner->orWhereExists(function ($sub) use ($like) {
                        $sub->selectRaw('1')
                            ->from('product_search_documents as psd')
                            ->whereColumn('psd.product_id', 'products.id')
                            ->where('psd.source_code', self::INDEXED_SOURCE)
                            ->where('psd.review_status', 'approved')
                            ->where(function ($doc) use ($like) {
                                $doc->where('psd.searchable_text', $this->likeOperator(), $like)
                                    ->orWhere('psd.title', $this->likeOperator(), $like)
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

        $q = trim((string) ($filters['q'] ?? ''));

        return DB::table('product_facet_values as pfv')
            ->join('product_search_documents as psd', function ($join) {
                $join->on('psd.product_id', '=', 'pfv.product_id')
                    ->whereColumn('psd.source_code', 'pfv.source_code');
            })
            ->join('products as p', 'p.id', '=', 'pfv.product_id')
            ->where('pfv.source_code', self::INDEXED_SOURCE)
            ->where('psd.review_status', 'approved')
            ->whereIn('p.status', self::PUBLIC_STATUSES)
            ->when(Schema::hasColumn('products', 'visibility_status'), fn ($query) => $query->whereIn('p.visibility_status', ['public', 'marketplace_only', 'quote_only']))
            ->whereIn('pfv.facet_name', ['manufacturer', 'category', 'stock', 'package', 'quality_band'])
            ->when($q !== '', function ($query) use ($q) {
                $like = $this->likeTerm($q);
                $query->where('psd.searchable_text', $this->likeOperator(), $like);
            })
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

        return [
            'documents' => DB::table('product_search_documents')->count(),
            'facets' => DB::table('product_facet_values')->count(),
            'approved_documents' => DB::table('product_search_documents')
                ->where('source_code', self::INDEXED_SOURCE)
                ->where('review_status', 'approved')
                ->count(),
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
                ->where('psd.review_status', 'approved')
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
}
