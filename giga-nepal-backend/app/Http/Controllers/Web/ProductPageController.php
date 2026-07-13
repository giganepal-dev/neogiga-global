<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Services\Catalog\CatalogSearchService;
use App\Services\Catalog\BrandVisibilityService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\RegionalVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Public SSR product pages (listing + detail). Read-only over the existing
 * marketplace catalog; only publicly visible statuses are shown. Pricing is
 * intentionally omitted until the pricing/offer layer is wired — pages lead
 * with specs + RFQ, which fits the B2B engineering catalog.
 */
class ProductPageController extends Controller
{
    private const VISIBLE = ['active', 'approved', 'published'];

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

        $products = Product::with(['brand', 'category'])
            ->withSum(['inventoryStocks as regional_available' => fn ($query) => $countryId > 0 ? $query->where('country_id', $countryId) : $query], 'quantity_available')
            ->whereIn('status', self::VISIBLE)
            ->when(Schema::hasColumn('products', 'visibility_status'), fn ($query) => $query->whereIn('visibility_status', ['public', 'marketplace_only', 'quote_only']))
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

        return view('frontend.products.index', [
            'products' => $products,
            'q' => $q,
            'category' => $category,
            'filters' => compact('brandId', 'manufacturer', 'stock', 'countryId', 'datasheet', 'package', 'quality', 'sort'),
            'facetGroups' => $catalogSearch->publicFacetGroups(compact('q')),
            'indexedSummary' => $catalogSearch->indexedSummary(),
            'rootCategories' => ProductCategory::whereNull('parent_id')
                ->orderBy('sort_order')->orderBy('name')->limit(80)->get(),
            'brands' => app(BrandVisibilityService::class)->visibleFor($marketplaceContext['current'] ?? null, false),
            'countries' => DB::table('countries')->where('is_active', true)->orderBy('name')->limit(80)->get(['id', 'name']),
        ]);
    }

    public function show(string $slug): View
    {
        $marketplaceContext = app(GlobalMarketplaceContextService::class)->context(request());
        $marketplace = $marketplaceContext['current'] ?? null;
        $product = Product::with(['brand', 'category', 'specs', 'images'])
            ->where('slug', $slug)
            ->whereIn('status', self::VISIBLE)
            ->when(Schema::hasColumn('products', 'visibility_status'), fn ($query) => $query->whereIn('visibility_status', ['public', 'marketplace_only', 'quote_only']))
            ->firstOrFail();

        $related = Product::with('brand')
            ->whereIn('status', self::VISIBLE)
            ->when(Schema::hasColumn('products', 'visibility_status'), fn ($q) => $q->whereIn('visibility_status', ['public', 'marketplace_only', 'quote_only']))
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->limit(6)
            ->get();

        $regionalVisibility = app(RegionalVisibilityService::class);
        $stockRows = $regionalVisibility->stockRows($product->id, $marketplace);

        return view('frontend.products.show', [
            'product' => $product,
            'related' => $related,
            'marketplaceContext' => $marketplaceContext,
            'stockRows' => $stockRows,
            'regionalStockTotal' => (int) $stockRows->sum('quantity_available'),
            'marketplacePrice' => $regionalVisibility->marketplacePrice($product->id, $marketplace),
            'sellerOffers' => $regionalVisibility->sellerOffers($product->id, $marketplace),
            'documents' => $this->productDocuments($product->id),
            'lmsLinks' => $this->productLmsLinks($product->id),
            'alternatives' => $this->alternatives($product->id),
            'advancedSpecs' => $this->advancedSpecs($product->id),
            'reviewSummary' => $this->reviewSummary($product->id),
            'reviews' => $this->approvedReviews($product->id),
        ]);
    }

    public function storeReview(Request $request, string $slug): RedirectResponse
    {
        $product = Product::where('slug', $slug)
            ->whereIn('status', self::VISIBLE)
            ->when(Schema::hasColumn('products', 'visibility_status'), fn ($query) => $query->whereIn('visibility_status', ['public', 'marketplace_only', 'quote_only']))
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

    private function stockRows(int $productId, int $countryId = 0)
    {
        if (! Schema::hasTable('inventory_stocks')) {
            return collect();
        }

        return DB::table('inventory_stocks as s')
            ->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->leftJoin('countries as c', 'c.id', '=', 's.country_id')
            ->where('s.product_id', $productId)
            ->when($countryId > 0, fn ($query) => $query->orderByRaw('CASE WHEN s.country_id = ? THEN 0 ELSE 1 END', [$countryId]))
            ->select('s.*', 'w.name as warehouse_name', 'c.name as country_name')
            ->orderByDesc('s.quantity_available')
            ->limit(12)
            ->get();
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
