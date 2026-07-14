<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Services\Catalog\BrandVisibilityService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\MarketplaceUrlGenerator;
use App\Services\Seo\CatalogSeoTemplateService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BrandPageController extends Controller
{
    public function __construct(private readonly BrandVisibilityService $brands) {}

    public function index(Request $request): View
    {
        $context = app(GlobalMarketplaceContextService::class)->context($request);
        $all = $this->brands->visibleFor($context['current'] ?? null, false, $context['locale'] ?? 'en', $request->getHost());
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 24;
        $brands = new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('frontend.brands.index', [
            'brands' => $brands,
            'marketplaceContext' => $context,
            'canonical' => $context['current']
                ? app(MarketplaceUrlGenerator::class)->forMarketplace($context['current'], '/'.($context['locale'] ?? 'en').'/brands')
                : 'https://neogiga.com/'.($context['locale'] ?? 'en').'/brands',
            'robots' => ($context['current']?->is_active && $context['current']?->is_visible && $context['current']?->indexable) ? 'index,follow' : 'noindex,nofollow',
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $context = app(GlobalMarketplaceContextService::class)->context($request);
        $brand = $this->brands->findVisible($slug, $context['current'] ?? null, $context['locale'] ?? 'en', $request->getHost());
        abort_unless($brand, 404);

        $products = Product::query()
            ->with([
                'category',
                'images' => fn ($query) => $query->where('is_active', true)->orderByDesc('is_primary')->orderBy('sort_order'),
            ])
            ->published()
            ->where('brand_id', $brand->id)
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();
        $categoryIds = Product::query()->published()->where('brand_id', $brand->id)->whereNotNull('category_id')->distinct()->pluck('category_id');
        $categories = ProductCategory::query()
            ->whereIn('id', $categoryIds)
            ->where('is_active', true)
            ->withCount(['products' => fn ($query) => $query->published()->where('brand_id', $brand->id)])
            ->orderBy('name')
            ->limit(60)
            ->get();
        $seo = app(CatalogSeoTemplateService::class)->activeBrand($brand, $context['current'] ?? null, $context['locale'] ?? 'en');

        return view('frontend.brands.show', [
            'brand' => $brand,
            'products' => $products,
            'categories' => $categories,
            'regionalAvailability' => $this->regionalAvailability($brand->id),
            'technicalResources' => $this->technicalResources($brand->id),
            'marketplaceContext' => $context,
            'pageSeo' => $seo,
            'canonical' => $seo['canonical'],
            'robots' => $seo['robots'],
            'robotsReason' => $seo['robots_reason'],
        ]);
    }

    private function regionalAvailability(int $brandId)
    {
        if (! Schema::hasTable('inventory_stocks') || ! Schema::hasColumn('inventory_stocks', 'quantity_available')) {
            return collect();
        }

        $query = DB::table('inventory_stocks as stock')
            ->join('products as product', 'product.id', '=', 'stock.product_id')
            ->where('product.brand_id', $brandId)
            ->where('stock.quantity_available', '>', 0)
            ->orderByDesc('available_quantity')
            ->limit(20);

        if (Schema::hasTable('countries') && Schema::hasColumn('inventory_stocks', 'country_id')) {
            return $query
                ->leftJoin('countries as country', 'country.id', '=', 'stock.country_id')
                ->select('country.name', DB::raw('count(distinct product.id) as product_count'), DB::raw('sum(stock.quantity_available) as available_quantity'))
                ->groupBy('country.id', 'country.name')
                ->get();
        }

        if (Schema::hasTable('countries') && Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'country_id')) {
            return $query
                ->leftJoin('warehouses as warehouse', 'warehouse.id', '=', 'stock.warehouse_id')
                ->leftJoin('countries as country', 'country.id', '=', 'warehouse.country_id')
                ->select('country.name', DB::raw('count(distinct product.id) as product_count'), DB::raw('sum(stock.quantity_available) as available_quantity'))
                ->groupBy('country.id', 'country.name')
                ->get();
        }

        return $query
            ->selectRaw("'Regional network' as name, count(distinct product.id) as product_count, sum(stock.quantity_available) as available_quantity")
            ->get();
    }

    private function technicalResources(int $brandId)
    {
        if (! Schema::hasTable('product_documents')) {
            return collect();
        }

        $query = DB::table('product_documents as document')
            ->join('products as product', 'product.id', '=', 'document.product_id')
            ->where('product.brand_id', $brandId)
            ->select('document.title', 'document.document_type', 'document.file_url', 'document.source_url', 'product.name as product_name', 'product.slug as product_slug')
            ->orderByDesc('document.id')
            ->limit(12);

        if (Schema::hasColumn('product_documents', 'is_active')) {
            $query->where('document.is_active', true);
        }

        return $query->get();
    }
}
