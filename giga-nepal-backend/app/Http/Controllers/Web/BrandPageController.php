<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use App\Services\Catalog\BrandVisibilityService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\MarketplaceUrlGenerator;
use App\Services\Product\ProductPublicationGate;
use App\Services\Seo\CatalogSeoTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BrandPageController extends Controller
{
    public function __construct(private readonly BrandVisibilityService $brands) {}

    public function index(Request $request): View
    {
        $context = app(GlobalMarketplaceContextService::class)->context($request);
        $locale = $context['locale'] ?? 'en';
        $marketplace = $context['current'] ?? null;

        // Invalid/MPN-like names are already rejected inside the (cached) service.
        $all = $this->brands->visibleFor($marketplace, false, $locale, $request->getHost());

        // Collapse duplicate identities (e.g. "SparkFun" / "SparkFun Electronics")
        // into one canonical card. Every underlying brand page still resolves, so
        // existing URLs never 404 — true record merges are an audited admin task.
        $brands = $this->sortBrands($this->dedupeForDisplay($all), $this->resolveSort($request));

        // First-letter buckets that actually have brands (drives the A–Z nav's
        // disabled state). Everything else (letter/search/sort) is client-side.
        $availableLetters = $brands
            ->map(fn (ProductBrand $brand) => $this->firstLetterBucket((string) $brand->name))
            ->unique()
            ->values()
            ->all();

        return view('frontend.brands.index', [
            'brands' => $brands,
            'availableLetters' => $availableLetters,
            'activeLetter' => strtoupper((string) $request->query('letter', 'all')),
            'activeSort' => $this->resolveSort($request),
            'searchQuery' => trim((string) $request->query('q', '')),
            'totalBrands' => $brands->count(),
            'marketplaceContext' => $context,
            'publicBase' => '/'.$locale,
            // Canonical stays the clean directory URL; letter/search/sort are view
            // state, not separate indexable pages.
            'canonical' => $marketplace
                ? app(MarketplaceUrlGenerator::class)->forMarketplace($marketplace, '/'.$locale.'/brands')
                : 'https://neogiga.com/'.$locale.'/brands',
            'robots' => ($marketplace?->is_active && $marketplace?->is_visible && $marketplace?->indexable) ? 'index,follow' : 'noindex,nofollow',
        ]);
    }

    private function resolveSort(Request $request): string
    {
        $sort = (string) $request->query('sort', 'az');

        return in_array($sort, ['az', 'za', 'products', 'recent'], true) ? $sort : 'az';
    }

    /**
     * @param  Collection<int, ProductBrand>  $brands
     * @return Collection<int, ProductBrand>
     */
    private function dedupeForDisplay(Collection $brands): Collection
    {
        return $brands
            ->groupBy(fn (ProductBrand $brand) => $this->normalizeName((string) $brand->name))
            ->map(function (Collection $group) {
                // Canonical = most products, then the shortest (usually official
                // short) name. Clone before mutating so the CACHED model instance
                // is never altered, then show the combined product count.
                $canonical = clone $group->sort(fn (ProductBrand $a, ProductBrand $b) => [(int) $b->public_products_count, mb_strlen((string) $a->name)]
                    <=> [(int) $a->public_products_count, mb_strlen((string) $b->name)])->first();
                $canonical->public_products_count = (int) $group->sum('public_products_count');

                return $canonical;
            })
            ->values();
    }

    /**
     * @param  Collection<int, ProductBrand>  $brands
     * @return Collection<int, ProductBrand>
     */
    private function sortBrands(Collection $brands, string $sort): Collection
    {
        return match ($sort) {
            'za' => $brands->sortByDesc(fn (ProductBrand $b) => mb_strtolower((string) $b->name))->values(),
            'products' => $brands->sortByDesc(fn (ProductBrand $b) => (int) $b->public_products_count)->values(),
            'recent' => $brands->sortByDesc(fn (ProductBrand $b) => $b->created_at?->getTimestamp() ?? 0)->values(),
            default => $brands->sortBy(fn (ProductBrand $b) => mb_strtolower((string) $b->name))->values(),
        };
    }

    private function firstLetterBucket(string $name): string
    {
        $char = mb_strtoupper(mb_substr(trim($name), 0, 1));
        if ($char >= '0' && $char <= '9') {
            return '0-9';
        }

        return ($char >= 'A' && $char <= 'Z') ? $char : '0-9';
    }

    private function normalizeName(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        // Drop common corporate suffixes so "SparkFun" == "SparkFun Electronics".
        $normalized = preg_replace('/\b(electronics?|incorporated|inc|corporation|corp|company|co|technologies|technology|semiconductors?|international|limited|ltd|llc|gmbh|group)\b/u', '', $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/u', '', (string) $normalized);

        return $normalized !== '' ? $normalized : mb_strtolower(trim($name));
    }

    public function show(Request $request, string $slug): View
    {
        $context = app(GlobalMarketplaceContextService::class)->context($request);
        $brand = $this->brands->findVisible($slug, $context['current'] ?? null, $context['locale'] ?? 'en', $request->getHost());
        abort_unless($brand, 404);

        $search = trim((string) $request->query('q', ''));
        $categoryFilter = (int) $request->query('category', 0);
        $productSort = (string) $request->query('sort', 'featured');
        $productSort = in_array($productSort, ['featured', 'name', 'newest'], true) ? $productSort : 'featured';

        $products = Product::query()
            ->with([
                'category',
                'images' => fn ($query) => $query->where('is_active', true)->orderByDesc('is_primary')->orderBy('sort_order'),
            ])
            ->published()
            ->where('brand_id', $brand->id)
            ->when($categoryFilter > 0, fn ($query) => $query->where('category_id', $categoryFilter))
            ->when($search !== '', function ($query) use ($search) {
                // LOWER(...) LIKE keeps this portable across Postgres and SQLite.
                $like = '%'.mb_strtolower($search).'%';
                $query->where(fn ($match) => $match->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(mpn) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(sku) LIKE ?', [$like]));
            })
            ->when($productSort === 'name', fn ($query) => $query->orderBy('name'))
            ->when($productSort === 'newest', fn ($query) => $query->orderByDesc('id'))
            ->when($productSort === 'featured', fn ($query) => $query->orderByDesc('is_featured')->orderBy('name'))
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
            'brandProductTotal' => Product::query()->published()->where('brand_id', $brand->id)->count(),
            'productSearch' => $search,
            'productCategory' => $categoryFilter,
            'productSort' => $productSort,
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
        app(ProductPublicationGate::class)->apply($query, 'product');

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
        app(ProductPublicationGate::class)->apply($query, 'product');

        if (Schema::hasColumn('product_documents', 'is_active')) {
            $query->where('document.is_active', true);
        }

        return $query->get();
    }
}
