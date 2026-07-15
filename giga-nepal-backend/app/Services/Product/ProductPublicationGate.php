<?php

namespace App\Services\Product;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Schema;

/**
 * Applies the shared storefront publication rules to product queries.
 *
 * Legacy/manual products keep their existing status-based behaviour. Products
 * linked to an import source must additionally be approved at both the product
 * and source-review levels before any public surface can expose them.
 */
class ProductPublicationGate
{
    private const PUBLIC_STATUSES = ['active', 'approved', 'published'];

    private const PUBLIC_VISIBILITIES = ['public', 'marketplace_only', 'quote_only'];

    public function apply(
        EloquentBuilder|QueryBuilder $query,
        string $productTable = 'products'
    ): EloquentBuilder|QueryBuilder {
        if (Schema::hasColumn('products', 'status')) {
            $query->whereIn($productTable.'.status', self::PUBLIC_STATUSES);
        }

        if (Schema::hasColumn('products', 'visibility_status')) {
            $query->whereIn($productTable.'.visibility_status', self::PUBLIC_VISIBILITIES);
        }

        $hasSources = Schema::hasTable('catalog_product_sources')
            && Schema::hasColumn('catalog_product_sources', 'product_id');
        $hasProductApproval = Schema::hasColumn('products', 'approval_status');
        $hasSourceReview = $hasSources
            && Schema::hasColumn('catalog_product_sources', 'review_status');

        if (! $hasSources || (! $hasProductApproval && ! $hasSourceReview)) {
            return $query;
        }

        $query->where(function ($publication) use ($productTable, $hasProductApproval, $hasSourceReview) {
            // Products without import provenance are existing/manual catalog
            // records and retain the established status-based publication path.
            $publication->whereNotExists(function ($source) use ($productTable) {
                $source->selectRaw('1')
                    ->from('catalog_product_sources as publication_source')
                    ->whereColumn('publication_source.product_id', $productTable.'.id');
            })->orWhere(function ($reviewed) use ($productTable, $hasProductApproval, $hasSourceReview) {
                if ($hasProductApproval) {
                    $reviewed->where($productTable.'.approval_status', 'approved');
                }

                if ($hasSourceReview) {
                    // Every linked source must be approved. A pending, rejected,
                    // or null review keeps the product out of every public query.
                    $reviewed->whereNotExists(function ($source) use ($productTable) {
                        $source->selectRaw('1')
                            ->from('catalog_product_sources as publication_source')
                            ->whereColumn('publication_source.product_id', $productTable.'.id')
                            ->where(function ($status) {
                                $status->whereNull('publication_source.review_status')
                                    ->orWhere('publication_source.review_status', '!=', 'approved');
                            });
                    });
                }
            });
        });

        return $query;
    }
}
