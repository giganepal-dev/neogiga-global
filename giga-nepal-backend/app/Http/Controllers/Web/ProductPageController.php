<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public SSR product pages (listing + detail). Read-only over the existing
 * marketplace catalog; only publicly visible statuses are shown. Pricing is
 * intentionally omitted until the pricing/offer layer is wired — pages lead
 * with specs + RFQ, which fits the B2B engineering catalog.
 */
class ProductPageController extends Controller
{
    private const VISIBLE = ['active', 'approved', 'published'];

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $categorySlug = (string) $request->query('category', '');

        $category = $categorySlug !== ''
            ? ProductCategory::where('slug', $categorySlug)->first()
            : null;

        $products = Product::with(['brand', 'category'])
            ->whereIn('status', self::VISIBLE)
            ->when($category, fn ($query) => $query->where('category_id', $category->id))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(fn ($w) => $w
                    ->where('name', 'ilike', "%{$q}%")
                    ->orWhere('sku', 'ilike', "%{$q}%")
                    ->orWhere('mpn', 'ilike', "%{$q}%"));
            })
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('frontend.products.index', [
            'products' => $products,
            'q' => $q,
            'category' => $category,
            'rootCategories' => ProductCategory::whereNull('parent_id')
                ->orderBy('sort_order')->orderBy('name')->limit(12)->get(),
        ]);
    }

    public function show(string $slug): View
    {
        $product = Product::with(['brand', 'category', 'specs', 'images'])
            ->where('slug', $slug)
            ->whereIn('status', self::VISIBLE)
            ->firstOrFail();

        $related = Product::with('brand')
            ->whereIn('status', self::VISIBLE)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->limit(6)
            ->get();

        $reviews = collect();
        $reviewAgg = (object) ['count' => 0, 'avg_rating' => 0];
        try {
            $reviews = \Illuminate\Support\Facades\DB::table('product_reviews as r')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->where('r.product_id', $product->id)
                ->where('r.status', 'approved')
                ->orderByDesc('r.id')
                ->limit(10)
                ->get(['r.rating', 'r.title', 'r.body', 'r.created_at', 'u.name as reviewer']);
            $reviewAgg = \Illuminate\Support\Facades\DB::table('product_reviews')
                ->where('product_id', $product->id)->where('status', 'approved')
                ->selectRaw('count(*) as count, coalesce(avg(rating),0) as avg_rating')
                ->first();
        } catch (\Throwable) {
            // table may not exist yet in some environments — page stays functional
        }

        return view('frontend.products.show', [
            'product' => $product,
            'related' => $related,
            'reviews' => $reviews,
            'reviewAgg' => $reviewAgg,
        ]);
    }
}
