<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\ProductCategory;
use App\Services\Product\ProductVisibilityService;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Preview of the Stitch "Precision Engineering" redesign, wired to real NeoGiga
 * data. Served at /preview/* so it can be reviewed WITHOUT touching the live
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
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->limit(4)
                    ->get(['name', 'slug', 'description']);
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

        return view('frontend.redesign.home', compact('categories', 'products'));
    }
}
