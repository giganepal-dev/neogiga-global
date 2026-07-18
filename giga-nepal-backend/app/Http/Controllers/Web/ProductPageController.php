<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Services\Catalog\CatalogSearchService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Product\ProductSpecificationResolver;
use App\Services\Marketplace\MarketplaceSeoRenderer;
use App\Services\Seo\CatalogSeoTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public SSR product pages (listing + detail). Read-only over the existing
 * marketplace catalog; only publicly visible statuses are shown. Pricing is
 * intentionally omitted until the pricing/offer layer is wired — pages lead
 * with specs + RFQ, which fits the B2B engineering catalog.
 */
class ProductPageController extends Controller
{
    public function index(Request $request): View
    {
        $marketplaceContext = app(GlobalMarketplaceContextService::class)->context($request);
        $q = trim((string) $request->query('q', ''));
        $categorySlug = (string) $request->query('category', '');
        $brandId = (int) $request->query('brand_id', 0);
        $manufacturer = trim((string) $request->query('manufacturer', ''));
        $stock = (string) $request->query('stock', '');
        $countryId = (int) $request->query('country_id', $marketplaceContext['country_id'] ?: 0);
        $datasheet = (string) $request->query('datasheet', '');
        $package = trim((string) $request->query('package', ''));
        $quality = trim((string) $request->query('quality', ''));
        $sort = (string) $request->query('sort', 'relevance');
        $catalogSearch = app(CatalogSearchService::class);

        $category = $categorySlug !== ''
            ? ProductCategory::where('slug', $categorySlug)->first()
            : null;

        $products = Product::with([
            'brand',
            'category',
            'images' => fn ($query) => $query->where('is_active', true)->orderByDesc('is_primary')->orderBy('sort_order')->limit(1),
        ])
            ->published()
            ->withSum(['inventoryStocks as regional_available' => function ($query) use ($countryId) {
                if ($countryId <= 0) {
                    return;
                }
                if (Schema::hasColumn('inventory_stocks', 'country_id')) {
                    $query->where('country_id', $countryId);
                } elseif (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'country_id')) {
                    $query->whereIn('warehouse_id', DB::table('warehouses')->where('country_id', $countryId)->select('id'));
                }
            }], 'quantity_available')
            ->when($category, fn ($query) => $query->where('category_id', $category->id))
            ->when($brandId > 0, fn ($query) => $query->where('brand_id', $brandId))
            ->when($manufacturer !== '', fn ($query) => $query->where(function ($w) use ($manufacturer) {
                $w->where('manufacturer_name', 'ilike', "%{$manufacturer}%")
                    ->orWhere('mpn', 'ilike', "%{$manufacturer}%");
            }))
            ->when($stock === 'low', fn ($query) => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold'))
            ->when($stock === 'out', fn ($query) => $query->where('stock_quantity', '<=', 0))
            ->when($datasheet === '1' && Schema::hasTable('product_documents'), fn ($query) => $query->whereExists(function ($sub) {
                $sub->selectRaw('1')->from('product_documents')->whereColumn('product_documents.product_id', 'products.id')->where('document_type', 'datasheet');
            }))
            ->tap(fn ($query) => $catalogSearch->applyPublicFilters($query, [
                'q' => $q,
                'stock' => $stock,
                'package' => $package,
                'quality' => $quality,
            ]))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('id'))
            ->when($sort === 'price', fn ($query) => $query->orderBy('base_price'))
            ->when($sort === 'stock', fn ($query) => $query->orderByDesc('stock_quantity'))
            ->when($sort === 'manufacturer', fn ($query) => $query->orderBy('manufacturer_name')->orderBy('name'))
            ->when(! in_array($sort, ['newest', 'price', 'stock', 'manufacturer'], true), fn ($query) => $query->orderByDesc('is_featured')->orderBy('name'))
            ->paginate(24)
            ->withQueryString();

        // Search and faceted combinations are useful to people but create an
        // unbounded duplicate-URL space for crawlers. Keep clean pagination
        // indexable and self-canonical; noindex filtered/search result pages.
        $hasFilters = collect($request->query())
            ->except('page')
            ->filter(fn ($value) => is_array($value) ? $value !== [] : trim((string) $value) !== '')
            ->isNotEmpty();
        $marketplaceTags = app(MarketplaceSeoRenderer::class)->tags(
            $marketplaceContext['current'] ?? null,
            url()->current(),
        );
        $canonical = $marketplaceTags['canonical'];
        if (! $hasFilters && $products->currentPage() > 1) {
            $canonical .= '?page='.$products->currentPage();
        }
        $pageSuffix = $products->currentPage() > 1 ? ' — Page '.$products->currentPage() : '';
        $pageTitle = ($category?->name ?? 'Products').$pageSuffix.' — NeoGiga Engineering Marketplace';
        $metaDescription = $category
            ? 'Browse '.$category->name.' with technical specifications, regional availability and RFQ sourcing on NeoGiga.'
            : 'Browse engineering components and tools: semiconductors, electronics, IoT, robotics, batteries, power and automation. Search by part number, SKU or keyword.';

        return view('frontend.products.index', [
            'products' => $products,
            'q' => $q,
            'category' => $category,
            'catalogTotal' => $hasFilters ? null : ($catalogSearch->cachedPublicProductCount() ?? 0),
            'filters' => compact('brandId', 'manufacturer', 'stock', 'countryId', 'datasheet', 'package', 'quality', 'sort'),
            'facetGroups' => $hasFilters ? $catalogSearch->publicFacetGroups(compact('q')) : collect(),
            'indexedSummary' => $hasFilters ? $catalogSearch->indexedSummary() : ['documents'=>0,'facets'=>0,'approved_documents'=>0],
            'rootCategories' => ProductCategory::whereNull('parent_id')
                ->orderBy('sort_order')->orderBy('name')->limit(80)->get(),
            'brands' => DB::table('product_brands')->orderBy('name')->limit(120)->get(['id', 'name']),
            'countries' => DB::table('countries')->where('is_active', true)->orderBy('name')->limit(80)->get(['id', 'name']),
            'canonical' => $canonical,
            'robots' => $hasFilters ? 'noindex,follow' : $marketplaceTags['robots'],
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription,
        ]);
    }

    public function show(string $slug): View
    {
        $marketplaceContext = app(GlobalMarketplaceContextService::class)->context(request());
        $relations = [
            'brand',
            'category',
            'specs',
            'images' => fn ($query) => $query->where('is_active', true)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
        ];
        if (Schema::hasTable('manufacturers') && Schema::hasColumn('products', 'manufacturer_id')) {
            $relations[] = 'manufacturer';
            $relations[] = 'brand.manufacturer';
        }

        $product = Product::with($relations)
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Product::with([
            'brand',
            'images' => fn ($query) => $query->where('is_active', true)->orderByDesc('is_primary')->orderBy('sort_order')->limit(1),
        ])
            ->published()
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->limit(6)
            ->get();

        $pageSeo = app(CatalogSeoTemplateService::class)->activeProduct(
            $product,
            $marketplaceContext['current'] ?? null,
            $marketplaceContext['locale'] ?? 'en',
        );

        return view('frontend.products.show', [
            'product' => $product,
            'related' => $related,
            'marketplaceContext' => $marketplaceContext,
            'stockRows' => $this->stockRows($product->id, (int) ($marketplaceContext['country_id'] ?: 0)),
            'marketplacePrice' => $this->marketplacePrice($product->id, (int) ($marketplaceContext['current']?->id ?? 0)),
            'sellerOffers' => $this->sellerOffers($product->id, (int) ($marketplaceContext['current']?->id ?? 0)),
            'documents' => $this->productDocuments($product->id),
            'lmsLinks' => $this->productLmsLinks($product->id),
            'alternatives' => $this->alternatives($product->id),
            'advancedSpecs' => $this->advancedSpecs($product->id),
            'sourceSpecs' => app(ProductSpecificationResolver::class)->sourceSpecifications($product),
            'reviewSummary' => $this->reviewSummary($product->id),
            'reviews' => $this->approvedReviews($product->id),
            'pageSeo' => $pageSeo,
            'canonical' => $pageSeo['canonical'],
            'robots' => $pageSeo['robots'],
            'robotsReason' => $pageSeo['robots_reason'],
            'ogImage' => $this->productImage($product),
            'productImages' => $product->images->values(),
        ]);
    }

    public function storeReview(Request $request, string $slug): RedirectResponse
    {
        $product = Product::published()
            ->where('slug', $slug)
            ->firstOrFail();

        if (! Schema::hasTable('product_reviews')) {
            return back()->with('error', 'Product reviews are not enabled yet.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'min:10', 'max:2500'],
            'use_case' => ['nullable', 'string', 'max:160'],
            'reviewer_name' => ['nullable', 'string', 'max:120'],
            'reviewer_email' => ['nullable', 'email', 'max:190'],
        ]);

        DB::table('product_reviews')->insert([
            'product_id' => $product->id,
            'user_id' => $request->user()?->id,
            'reviewer_name' => $request->user()?->name ?: ($data['reviewer_name'] ?? null),
            'reviewer_email' => $request->user()?->email ?: ($data['reviewer_email'] ?? null),
            'rating' => (int) $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'use_case' => $data['use_case'] ?? null,
            'is_verified_buyer' => false,
            'status' => 'pending',
            'metadata' => json_encode([
                'source' => 'public_product_page',
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Review submitted for moderation.');
    }

    private function advancedSpecs(int $productId)
    {
        if (! Schema::hasTable('product_specifications') || ! Schema::hasTable('spec_template_fields')) {
            return collect();
        }

        return DB::table('product_specifications as ps')
            ->join('spec_template_fields as f', 'f.id', '=', 'ps.template_field_id')
            ->leftJoin('category_spec_templates as t', 't.id', '=', 'f.template_id')
            ->leftJoin('specification_group_fields as gf', 'gf.template_field_id', '=', 'f.id')
            ->leftJoin('specification_groups as g', 'g.id', '=', 'gf.group_id')
            ->where('ps.product_id', $productId)
            ->where('ps.is_visible', true)
            ->select([
                'ps.id',
                'ps.value',
                'ps.unit_override',
                'f.field_name',
                'f.field_label',
                'f.unit',
                'f.sort_order',
                't.name as template_name',
                'g.name as group_name',
                'g.sort_order as group_sort_order',
            ])
            ->orderBy('g.sort_order')
            ->orderBy('f.sort_order')
            ->orderBy('f.field_label')
            ->get();
    }

    public function suggest(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $products = \App\Models\Marketplace\Product::query()
            ->published()
            ->where(function ($w) use ($q) {
                $w->where('name', 'ilike', "%{$q}%")
                  ->orWhere('mpn', 'ilike', "%{$q}%")
                  ->orWhere('sku', 'ilike', "%{$q}%");
            })
            ->with(['category:id,name', 'images' => fn ($qImg) => $qImg->where('is_active', true)->where('is_primary', true)->limit(1)])
            ->limit(8)
            ->get();

        $data = $products->map(fn ($p) => [
            'name' => $p->name,
            'slug' => $p->slug,
            'mpn' => $p->mpn,
            'sku' => $p->sku,
            'category' => $p->category?->name,
            'image' => ($img = $p->images->first()) ? $img->publicUrl() : null,
            'url' => url('/'.$request->segment(1).'/products/'.$p->slug),
        ])->values()->all();

        return response()->json(['data' => $data]);
    }

    private function stockRows(int $productId, int $countryId = 0)
    {
        if (! Schema::hasTable('inventory_stocks')) {
            return collect();
        }

        $stockHasCountry = Schema::hasColumn('inventory_stocks', 'country_id');
        $warehouseHasCountry = Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'country_id');
        $countryJoinColumn = $stockHasCountry ? 's.country_id' : ($warehouseHasCountry ? 'w.country_id' : null);

        $query = DB::table('inventory_stocks as s')
            ->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->where('s.product_id', $productId)
            ->select('s.*', 'w.name as warehouse_name');

        if ($countryJoinColumn !== null && Schema::hasTable('countries')) {
            $query->leftJoin('countries as c', 'c.id', '=', DB::raw($countryJoinColumn))
                ->addSelect('c.name as country_name');
        } else {
            $query->addSelect(DB::raw('NULL as country_name'));
        }

        if ($countryId > 0 && $countryJoinColumn !== null) {
            $query->orderByRaw("CASE WHEN {$countryJoinColumn} = ? THEN 0 ELSE 1 END", [$countryId]);
        }

        return $query
            ->orderByDesc('s.quantity_available')
            ->limit(12)
            ->get();
    }

    private function pageSeo(Product $product): array
    {
        $seoMeta = is_array($product->seo_meta) ? $product->seo_meta : [];
        $title = $product->meta_title ?? null;
        $description = $product->meta_description ?? null;

        if (Schema::hasTable('product_seo_meta')) {
            $row = DB::table('product_seo_meta')->where('product_id', $product->id)->first();
            $title = $row?->meta_title ?: $title;
            $description = $row?->meta_description ?: $description;
        }

        $title = $title ?: ($seoMeta['title'] ?? null) ?: ($product->name.' - NeoGiga');
        $description = $description ?: ($seoMeta['description'] ?? null)
            ?: Str::limit(strip_tags($product->short_description ?: ($product->description ?: 'Datasheet, technical specifications, stock and RFQ for '.$product->name.' on NeoGiga.')), 155);

        return [
            'title' => $title,
            'description' => $description,
        ];
    }

    private function productImage(Product $product): string
    {
        $image = $product->images->firstWhere('is_primary', true) ?: $product->images->first();

        return $image?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png');
    }

    private function productDocuments(int $productId)
    {
        if (! Schema::hasTable('product_documents')) {
            return collect();
        }

        return DB::table('product_documents')->where('product_id', $productId)->orderByDesc('id')->limit(12)->get();
    }

    private function productLmsLinks(int $productId)
    {
        if (! Schema::hasTable('product_lms_links')) {
            return collect();
        }

        return DB::table('product_lms_links')->where('product_id', $productId)->orderByDesc('id')->limit(6)->get();
    }

    private function alternatives(int $productId)
    {
        if (! Schema::hasTable('product_related_items')) {
            return collect();
        }

        return DB::table('product_related_items as r')
            ->leftJoin('products as p', 'p.id', '=', 'r.related_product_id')
            ->where('r.product_id', $productId)
            ->whereIn('r.related_product_id', Product::query()->published()->select('products.id'))
            ->select('r.*', 'p.name', 'p.slug', 'p.sku', 'p.mpn')
            ->limit(8)
            ->get();
    }

    private function marketplacePrice(int $productId, int $marketplaceId): ?object
    {
        if (! Schema::hasTable('marketplace_product_prices')) {
            return null;
        }

        $price = DB::table('marketplace_product_prices as p')
            ->leftJoin('marketplaces as m', 'm.id', '=', 'p.marketplace_id')
            ->leftJoin('currencies as c', 'c.code', '=', 'p.currency_code')
            ->where('p.product_id', $productId)
            ->where('p.is_active', true)
            ->when($marketplaceId > 0, fn ($query) => $query->orderByRaw('CASE WHEN p.marketplace_id = ? THEN 0 ELSE 1 END', [$marketplaceId]))
            ->select('p.*', 'm.name as marketplace_name', 'c.symbol as currency_symbol', 'c.native_symbol as currency_native_symbol')
            ->orderByRaw('coalesce(p.sale_price, p.base_price) asc')
            ->first();

        return $price ?: null;
    }

    private function sellerOffers(int $productId, int $marketplaceId)
    {
        if (! Schema::hasTable('vendor_product_prices')) {
            return collect();
        }

        return DB::table('vendor_product_prices as price')
            ->leftJoin('vendors as v', 'v.id', '=', 'price.vendor_id')
            ->leftJoin('vendor_products as vp', function ($join) use ($productId) {
                $join->on('vp.vendor_id', '=', 'price.vendor_id')
                    ->where('vp.product_id', '=', $productId);
            })
            ->leftJoin('marketplaces as m', 'm.id', '=', 'vp.marketplace_id')
            ->leftJoin('currencies as c', 'c.code', '=', 'price.currency_code')
            ->where('price.product_id', $productId)
            ->where('price.is_active', true)
            ->when($marketplaceId > 0, fn ($query) => $query->orderByRaw('CASE WHEN vp.marketplace_id = ? THEN 0 ELSE 1 END', [$marketplaceId]))
            ->select([
                'price.*',
                'v.name as vendor_name',
                'v.slug as vendor_slug',
                'v.status as vendor_status',
                'v.is_verified',
                'vp.marketplace_id',
                'vp.status as vendor_product_status',
                'm.name as marketplace_name',
                'c.symbol as currency_symbol',
                'c.native_symbol as currency_native_symbol',
            ])
            ->orderBy('price.selling_price')
            ->limit(8)
            ->get();
    }

    private function approvedReviews(int $productId)
    {
        if (! Schema::hasTable('product_reviews')) {
            return collect();
        }

        return DB::table('product_reviews')
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->limit(12)
            ->get();
    }

    private function reviewSummary(int $productId): object
    {
        if (! Schema::hasTable('product_reviews')) {
            return (object) ['count' => 0, 'average' => null];
        }

        $row = DB::table('product_reviews')
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->selectRaw('count(*) as count, avg(rating) as average')
            ->first();

        return (object) [
            'count' => (int) ($row->count ?? 0),
            'average' => $row?->average !== null ? round((float) $row->average, 1) : null,
        ];
    }
}
