<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    use ApiResponses;

    /**
     * Paginated flat list of active categories.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'featured' => ['sometimes', 'boolean'],
            'parent_id' => ['sometimes', 'integer', 'exists:product_categories,id'],
        ]);

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->when(! isset($validated['parent_id']), fn ($q) => $q
                ->where('sort_order', '>', 0)
                ->where('slug', '!=', 'uncategorized'))
            ->when(isset($validated['featured']), fn ($q) => $q->where('is_featured', (bool) $validated['featured']))
            ->when(isset($validated['parent_id']), fn ($q) => $q->where('parent_id', $validated['parent_id']))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($validated['per_page'] ?? 25);

        return $this->success($categories);
    }

    /**
     * Full category tree (roots with nested children), cached.
     */
    public function tree(): JsonResponse
    {
        $tree = Cache::remember('categories:tree:public-v2', 1800, function () {
            return ProductCategory::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->where('sort_order', '>', 0)
                ->where('slug', '!=', 'uncategorized')
                ->with(['children' => fn ($q) => $q
                    ->where('is_active', true)
                    ->where('name', 'not like', '%|%')
                    ->orderBy('sort_order')
                    ->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        });

        return $this->success($tree)
            ->header('Cache-Control', 'public, max-age=600');
    }

    /**
     * Single category by slug, with parent and children.
     */
    public function show(string $slug): JsonResponse
    {
        $category = ProductCategory::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'parent',
                'children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            ])
            ->first();

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        return $this->success($category);
    }
}
