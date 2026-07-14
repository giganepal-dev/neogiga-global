<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use App\Services\Catalog\CatalogSearchService;
use App\Services\Product\ProductImageManager;
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
            ->with(['images' => fn ($query) => $query->where('is_active', true)->orderByDesc('is_primary')->orderBy('sort_order'), 'variants', 'specGroups', 'specs', 'vendor:id,name,slug'])
            ->first();

        if (! $product) {
            return $this->error('Product not found', 404);
        }

        $payload = $product->toArray();
        $payload['images'] = $product->images
            ->map(fn ($image) => app(ProductImageManager::class)->serialize($image))
            ->values()
            ->all();

        return $this->success($payload);
    }

    public function byCategory(Request $request, string $slug): JsonResponse
    {
        $category = ProductCategory::where('slug', $slug)->where('is_active', true)->first();

        if (! $category) {
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

        if (! $brand) {
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
            'stock' => ['sometimes', 'in:in,low,out'],
            'package' => ['sometimes', 'string', 'max:120'],
            'quality' => ['sometimes', 'in:high,needs_review'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $catalogSearch = app(CatalogSearchService::class);

        $products = $this->baseQuery()
            ->tap(fn ($query) => $catalogSearch->applyPublicFilters($query, [
                'q' => $validated['q'],
                'stock' => $validated['stock'] ?? '',
                'package' => $validated['package'] ?? '',
                'quality' => $validated['quality'] ?? '',
            ]))
            ->paginate($validated['per_page'] ?? 24);

        return $this->success($products, meta: [
            'query' => $validated['q'],
            'facets' => $catalogSearch->publicFacetGroups(['q' => $validated['q']]),
            'index' => $catalogSearch->indexedSummary(),
        ]);
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
