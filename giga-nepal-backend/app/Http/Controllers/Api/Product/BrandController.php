<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Catalog\BrandVisibilityService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Seo\CatalogSeoTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BrandController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly BrandVisibilityService $brands) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'featured' => ['sometimes', 'boolean'],
        ]);

        $context = app(GlobalMarketplaceContextService::class)->context($request);
        $collection = $this->brands
            ->visibleFor($context['current'] ?? null, false, $context['locale'] ?? 'en', $request->getHost())
            ->when(isset($validated['featured']), fn ($brands) => $brands->where('is_featured', (bool) $validated['featured']))
            ->values();
        $perPage = $validated['per_page'] ?? 25;
        $page = max(1, (int) $request->query('page', 1));
        $brands = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return $this->success($brands);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $context = app(GlobalMarketplaceContextService::class)->context($request);
        $brand = $this->brands->findVisible($slug, $context['current'] ?? null, $context['locale'] ?? 'en', $request->getHost());

        if (! $brand) {
            return $this->error('Brand not found', 404);
        }

        $brand->setAttribute('seo', app(CatalogSeoTemplateService::class)->activeBrand(
            $brand,
            $context['current'] ?? null,
            $context['locale'] ?? 'en',
        ));

        return $this->success($brand);
    }
}
