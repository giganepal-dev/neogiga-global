<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $roots = ProductCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $total = ProductCategory::where('is_active', true)->count();

        return view('frontend.categories.index', compact('roots', 'total'));
    }

    public function show(string $slug): View
    {
        $category = ProductCategory::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $children = ProductCategory::query()
            ->where('parent_id', $category->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->where('category_id', $category->id)
            ->where('status', 'approved')
            ->latest('id')
            ->limit(24)
            ->get();

        $breadcrumb = $this->breadcrumb($category);

        return view('frontend.categories.show', compact('category', 'children', 'products', 'breadcrumb'));
    }

    /**
     * @return array<int, array{name:string, url:string}>
     */
    private function breadcrumb(ProductCategory $category): array
    {
        $chain = [];
        $node = $category;
        $guard = 0;

        while ($node && $guard++ < 12) {
            array_unshift($chain, ['name' => $node->name, 'url' => url('/categories/'.$node->slug)]);
            $node = $node->parent;
        }

        return array_merge(
            [
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Categories', 'url' => url('/categories')],
            ],
            $chain,
        );
    }
}
