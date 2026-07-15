<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\Product;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Display a listing of all brands.
     */
    public function index(Request $request)
    {
        $query = ProductBrand::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filter by featured
        if ($request->filled('featured') && $request->featured === '1') {
            $query->where('is_featured', true);
        }

        $brands = $query->paginate(24)->withQueryString();

        return view('frontend.brands.index', compact('brands'));
    }

    /**
     * Display the specified brand with its products.
     */
    public function show(string $slug)
    {
        $brand = ProductBrand::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Get products for this brand
        $productsQuery = Product::query()
            ->where('brand_id', $brand->id)
            ->where('is_active', true)
            ->with(['brand', 'category', 'primaryImage']);

        // Pagination
        $products = $productsQuery->paginate(24);

        // SEO metadata
        $seoTitle = $brand->seo_meta['title'] ?? "Buy {$brand->name} Products on NeoGiga | NeoGiga Engineering Marketplace";
        $seoDescription = $brand->seo_meta['description'] ?? "Shop {$brand->name} electronic components, semiconductors, modules and engineering products on NeoGiga. Authorized distributors, competitive pricing and regional availability.";

        return view('frontend.brands.show', compact('brand', 'products', 'seoTitle', 'seoDescription'));
    }
}
