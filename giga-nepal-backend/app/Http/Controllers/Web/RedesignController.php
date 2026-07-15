<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\ProductCategory;
use App\Services\Product\ProductVisibilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Preview of the Stitch "Precision Engineering" redesign, wired to real NeoGiga
 * data and framed as a GLOBAL marketplace (live regional editions, worldwide
 * stats). Served at /preview/* so it can be reviewed WITHOUT touching the live
 * homepage/layout. Fully defensive: any data error falls back to sample content
 * so the design always renders.
 */
class RedesignController extends Controller
{
    public function home(): View
    {
        $categories = collect();
        try {
            if (Schema::hasTable('product_categories')) {
                $categories = ProductCategory::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')->orderBy('name')
                    ->limit(6)
                    ->get(['id', 'name', 'slug', 'description']);
            }
        } catch (\Throwable) {
            $categories = collect();
        }

        $products = collect();
        try {
            $products = app(ProductVisibilityService::class)->publicProducts()
                ->whereNotNull('slug')->where('slug', '!=', '')
                ->orderByDesc('updated_at')
                ->limit(3)
                ->get(['id', 'name', 'slug', 'mpn', 'sku', 'base_price']);
        } catch (\Throwable) {
            $products = collect();
        }

        // Live regional editions (active, publicly visible marketplaces).
        $editions = collect();
        try {
            if (Schema::hasTable('marketplaces')) {
                $editions = Marketplace::query()
                    ->with(['country:id,name,iso_code_2', 'currency:id,code,symbol'])
                    ->where('is_active', true)
                    ->orderByDesc('is_default')->orderBy('name')
                    ->limit(6)
                    ->get(['id', 'name', 'code', 'country_id', 'currency_id', 'domain', 'generated_domain']);
            }
        } catch (\Throwable) {
            $editions = collect();
        }

        // Global stats (best-effort counts; never fatal).
        $stats = [
            'marketplaces' => $this->count('marketplaces'),
            'countries' => $this->count('countries'),
            'products' => $this->publishedProductCount(),
            'categories' => $this->count('product_categories'),
        ];

        return view('frontend.redesign.home', compact('categories', 'products', 'editions', 'stats'));
    }

    private function count(string $table): int
    {
        try {
            return Schema::hasTable($table) ? (int) DB::table($table)->count() : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function publishedProductCount(): int
    {
        try {
            return (int) app(ProductVisibilityService::class)->publicProducts()->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
