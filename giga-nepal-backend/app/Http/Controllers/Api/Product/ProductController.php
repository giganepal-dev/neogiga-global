<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponses;

    /** Relations eagerly loaded on list responses. */
    private const LIST_WITH = ['brand:id,name,slug', 'category:id,name,slug'];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'featured' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'in:newest,name'],
        ]);

        $products = $this->baseQuery()
            ->when(isset($validated['featured']), fn ($q) => $q->where('is_featured', (bool) $validated['featured']))
            ->when(
                ($validated['sort'] ?? 'newest') === 'name',
                fn ($q) => $q->orderBy('name'),
                fn ($q) => $q->latest('approved_at')->latest('id')
            )
            ->paginate($validated['per_page'] ?? 24);

        return $this->success($products);
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->baseQuery()
            ->bySlug($slug)
            ->with(['images', 'variants', 'specGroups', 'specs', 'vendor:id,name,slug'])
            ->first();

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        return $this->success($product);
    }

    public function byCategory(Request $request, string $slug): JsonResponse
    {
        $category = ProductCategory::where('slug', $slug)->where('is_active', true)->first();

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $products = $this->baseQuery()
            ->byCategory($category->id)
            ->latest('approved_at')
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->success($products, meta: ['category' => $category->only(['id', 'name', 'slug'])]);
    }

    public function byBrand(Request $request, string $slug): JsonResponse
    {
        $brand = ProductBrand::where('slug', $slug)->where('is_active', true)->first();

        if (!$brand) {
            return $this->error('Brand not found', 404);
        }

        $products = $this->baseQuery()
            ->byBrand($brand->id)
            ->latest('approved_at')
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->success($products, meta: ['brand' => $brand->only(['id', 'name', 'slug'])]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $products = $this->baseQuery()
            ->search($validated['q'])
            ->paginate($validated['per_page'] ?? 24);

        return $this->success($products, meta: ['query' => $validated['q']]);
    }

    private function baseQuery(): Builder
    {
        return Product::query()->published()->with(self::LIST_WITH);
    }

    private function perPage(Request $request): int
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return (int) ($validated['per_page'] ?? 24);
    }
}
