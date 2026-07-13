<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\ProductBrand;
use App\Services\Catalog\BrandVisibilityService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'featured' => ['sometimes', 'boolean'],
        ]);

        $context = app(GlobalMarketplaceContextService::class)->context($request);
        $brands = app(BrandVisibilityService::class)
            ->visibleFor($context['current'] ?? null)
            ->when(isset($validated['featured']), fn ($items) => $items->where('is_featured', (bool) $validated['featured']))
            ->values();

        $perPage = $validated['per_page'] ?? 25;
        $page = max(1, (int) $request->query('page', 1));
        $brands = new \Illuminate\Pagination\LengthAwarePaginator($brands->forPage($page, $perPage)->values(), $brands->count(), $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return $this->success($brands);
    }

    public function show(string $slug): JsonResponse
    {
        $context = app(GlobalMarketplaceContextService::class)->context($request);
        $brand = app(BrandVisibilityService::class)->visibleFor($context['current'] ?? null, false)->firstWhere('slug', $slug);

        if (!$brand) {
            return $this->error('Brand not found', 404);
        }

        return $this->success($brand);
    }
}
