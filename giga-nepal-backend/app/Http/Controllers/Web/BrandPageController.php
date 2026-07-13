<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Services\Catalog\BrandVisibilityService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandPageController extends Controller
{
    public function __construct(private readonly BrandVisibilityService $brands)
    {
    }

    public function index(Request $request): View
    {
        $context = app(GlobalMarketplaceContextService::class)->context($request);

        return view('frontend.brands.index', [
            'brands' => $this->brands->visibleFor($context['current'] ?? null),
            'marketplaceContext' => $context,
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $context = app(GlobalMarketplaceContextService::class)->context($request);
        $brand = $this->brands->visibleFor($context['current'] ?? null, false)->firstWhere('slug', $slug);
        abort_unless($brand && $brand->landing_page_enabled, 404);

        return view('frontend.brands.show', [
            'brand' => $brand,
            'products' => Product::query()->with('category')->published()->where('brand_id', $brand->id)->orderByDesc('is_featured')->orderBy('name')->paginate(24),
            'marketplaceContext' => $context,
            'canonical' => $brand->canonical_url ?: url()->current(),
        ]);
    }
}
