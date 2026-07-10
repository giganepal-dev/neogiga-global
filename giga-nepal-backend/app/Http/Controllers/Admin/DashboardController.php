<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\Vendor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'marketplaces' => Marketplace::count(),
            'categories' => ProductCategory::count(),
            'products' => Product::count(),
            'vendors' => Vendor::count(),
            'users' => User::count(),
            'orders' => $this->safeCount('orders'),
            'customers' => $this->safeCount('customers'),
            'sales' => $this->safeSum('orders', 'grand_total'),
            'pendingRfqs' => $this->safeWhereCount('rfq_requests', 'status', 'open'),
            'pendingApplications' => $this->safeWhereCount('seller_applications', 'status', 'pending') + $this->safeWhereCount('distributor_applications', 'status', 'pending'),
            'lowStock' => $this->safeLowStockCount(),
            'queuePending' => $this->safeWhereCount('jobs', 'queue', 'default'),
            'openSupport' => $this->safeWhereCount('support_tickets', 'status', 'open'),
            'aiConversations' => $this->safeCount('commerce_ai_sessions'),
        ];

        $marketplaces = Marketplace::with(['currency', 'country'])->orderBy('id')->get();

        $rootCategories = ProductCategory::whereNull('parent_id')
            ->withCount('children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(14)
            ->get();

        $recentOrders = DB::table('orders')->orderByDesc('id')->limit(6)->get();
        $recentProducts = DB::table('products')->orderByDesc('id')->limit(6)->get();
        $recentVendors = DB::table('vendors')->orderByDesc('id')->limit(6)->get();
        $recentSupport = DB::table('support_tickets')->orderByDesc('id')->limit(6)->get();
        $countryPerformance = DB::table('countries as c')
            ->leftJoin('marketplaces as m', 'm.country_id', '=', 'c.id')
            ->leftJoin('orders as o', 'o.marketplace_id', '=', 'm.id')
            ->select('c.name', DB::raw('count(o.id) as orders_count'), DB::raw('coalesce(sum(o.grand_total),0) as sales_total'))
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('sales_total')
            ->limit(6)
            ->get();
        $inventoryBands = [
            'low' => $stats['lowStock'],
            'total' => $stats['products'],
        ];
        $marketingStats = [
            'emailCampaigns' => $this->safeCount('email_campaigns'),
            'newsletterSubscribers' => $this->safeCount('newsletter_subscribers'),
            'segments' => $this->safeCount('customer_segments'),
        ];

        return view('admin.dashboard', compact(
            'stats',
            'marketplaces',
            'rootCategories',
            'recentOrders',
            'recentProducts',
            'recentVendors',
            'recentSupport',
            'countryPerformance',
            'inventoryBands',
            'marketingStats'
        ));
    }

    public function categories(\Illuminate\Http\Request $request): View
    {
        $roots = ProductCategory::whereNull('parent_id')
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('name', 'ilike', "%{$term}%")->orWhere('slug', 'ilike', "%{$term}%");
            }))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->with(['children' => fn ($q) => $q->withCount('children')->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $total = ProductCategory::count();

        return view('admin.categories', [
            'roots' => $roots,
            'total' => $total,
            'allCategories' => ProductCategory::orderBy('name')->get(),
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'status' => (string) $request->query('status', ''),
            ],
        ]);
    }

    public function marketplaces(): View
    {
        $marketplaces = Marketplace::with(['currency', 'country', 'domains'])->orderBy('id')->get();

        return view('admin.marketplaces', compact('marketplaces'));
    }

    public function products(\Illuminate\Http\Request $request): View
    {
        $products = Product::query()
            ->leftJoin('product_categories as c', 'c.id', '=', 'products.category_id')
            ->leftJoin('product_brands as b', 'b.id', '=', 'products.brand_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'products.vendor_id')
            ->select('products.*', 'c.name as category_name', 'b.name as brand_name', 'v.name as vendor_name')
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('products.name', 'ilike', "%{$term}%")
                    ->orWhere('products.sku', 'ilike', "%{$term}%")
                    ->orWhere('products.mpn', 'ilike', "%{$term}%");
            }))
            ->when($request->query('category_id'), fn ($q, $category) => $q->where('products.category_id', $category))
            ->when($request->query('brand_id'), fn ($q, $brand) => $q->where('products.brand_id', $brand))
            ->when($request->query('vendor_id'), fn ($q, $vendor) => $q->where('products.vendor_id', $vendor))
            ->when($request->query('status'), fn ($q, $status) => $q->where('products.status', $status))
            ->when($request->query('stock') === 'low', fn ($q) => $q->whereColumn('products.stock_quantity', '<=', 'products.low_stock_threshold'))
            ->when($request->query('stock') === 'out', fn ($q) => $q->where('products.stock_quantity', '<=', 0))
            ->orderByDesc('products.id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products', [
            'products' => $products,
            'categories' => ProductCategory::orderBy('name')->get(),
            'brands' => DB::table('product_brands')->orderBy('name')->get(),
            'vendors' => Vendor::orderBy('name')->get(),
            'allProducts' => Product::orderBy('name')->limit(500)->get(['id', 'name', 'sku']),
            'productSpecs' => DB::table('product_specs')->whereIn('product_id', collect($products->items())->pluck('id'))->orderBy('sort_order')->get()->groupBy('product_id'),
            'productDocuments' => DB::table('product_documents')->whereIn('product_id', collect($products->items())->pluck('id'))->orderByDesc('id')->get()->groupBy('product_id'),
            'productRelated' => DB::table('product_related_items as r')
                ->leftJoin('products as rp', 'rp.id', '=', 'r.related_product_id')
                ->whereIn('r.product_id', collect($products->items())->pluck('id'))
                ->select('r.*', 'rp.name as related_name', 'rp.sku as related_sku')
                ->orderBy('r.sort_order')
                ->get()
                ->groupBy('product_id'),
            'productLmsLinks' => DB::table('product_lms_links')->whereIn('product_id', collect($products->items())->pluck('id'))->orderByDesc('id')->get()->groupBy('product_id'),
            'productSeo' => DB::table('product_seo_meta')->whereIn('product_id', collect($products->items())->pluck('id'))->get()->keyBy('product_id'),
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'category_id' => (string) $request->query('category_id', ''),
                'brand_id' => (string) $request->query('brand_id', ''),
                'vendor_id' => (string) $request->query('vendor_id', ''),
                'status' => (string) $request->query('status', ''),
                'stock' => (string) $request->query('stock', ''),
            ],
            'stats' => [
                'total' => Product::count(),
                'active' => Product::whereIn('status', ['active', 'approved', 'published'])->count(),
                'draft' => Product::where('status', 'draft')->count(),
                'lowStock' => Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            ],
        ]);
    }

    public function vendors(\Illuminate\Http\Request $request): View
    {
        $vendors = Vendor::query()
            ->leftJoin('vendor_profiles as p', 'p.vendor_id', '=', 'vendors.id')
            ->select('vendors.*', 'p.business_type', 'p.rating_average', 'p.total_sales')
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('vendors.name', 'ilike', "%{$term}%")
                    ->orWhere('vendors.email', 'ilike', "%{$term}%")
                    ->orWhere('vendors.slug', 'ilike', "%{$term}%");
            }))
            ->when($request->query('status'), fn ($q, $status) => $q->where('vendors.status', $status))
            ->when($request->query('type'), fn ($q, $type) => $q->where('vendors.type', $type))
            ->orderByDesc('vendors.id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.vendors', [
            'vendors' => $vendors,
            'stats' => [
                'total' => Vendor::count(),
                'pending' => Vendor::where('status', 'pending')->count(),
                'approved' => Vendor::whereIn('status', ['approved', 'active'])->count(),
                'suspended' => Vendor::where('status', 'suspended')->count(),
                'documentsPending' => $this->safeWhereCount('vendor_documents', 'status', 'pending'),
            ],
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'status' => (string) $request->query('status', ''),
                'type' => (string) $request->query('type', ''),
            ],
            'countries' => DB::table('countries')->where('is_active', true)->orderBy('name')->get(),
            'recentDocuments' => $this->safeRows('vendor_documents'),
            'recentProducts' => $this->safeRows('vendor_products'),
        ]);
    }

    public function distributors(): View
    {
        $query = DB::table('distributors as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->leftJoin('distributor_profiles as p', 'p.distributor_id', '=', 'd.id')
            ->select([
                'd.*',
                'u.name as user_name',
                'u.email as user_email',
                'p.business_name',
                'p.address',
            ])
            ->orderByDesc('d.id');

        return view('admin.distributors', [
            'distributors' => $query->paginate(20),
            'stats' => [
                'total' => $this->safeCount('distributors'),
                'active' => $this->safeWhereCount('distributors', 'status', 'active'),
                'pending' => $this->safeWhereCount('distributors', 'status', 'pending'),
                'suspended' => $this->safeWhereCount('distributors', 'status', 'suspended'),
                'territories' => $this->safeCount('distributor_territories'),
            ],
            'territories' => DB::table('distributor_territories as t')
                ->leftJoin('distributors as d', 'd.id', '=', 't.distributor_id')
                ->select('t.*', 'd.name as distributor_name')
                ->orderByDesc('t.id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function users(\Illuminate\Http\Request $request): View
    {
        $users = User::with('role')
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('name', 'ilike', "%{$term}%")->orWhere('email', 'ilike', "%{$term}%");
            }))
            ->when($request->query('role_id'), fn ($q, $role) => $q->where('role_id', $role))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'roles' => DB::table('roles')->where('is_active', true)->orderBy('name')->get(),
            'countries' => DB::table('countries')->where('is_active', true)->orderBy('name')->get(),
            'stats' => [
                'total' => User::count(),
                'admins' => User::whereIn('role_id', DB::table('roles')->whereIn('name', ['super_admin', 'admin'])->pluck('id'))->count(),
                'verified' => User::whereNotNull('email_verified_at')->count(),
                'recentLogins' => User::whereNotNull('last_login_at')->count(),
            ],
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'role_id' => (string) $request->query('role_id', ''),
            ],
            'auditLogs' => DB::table('audit_logs')->orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function lms(): View
    {
        return view('admin.lms', [
            'stats' => [
                'courses' => $this->safeCount('lms_courses'),
                'publishedCourses' => DB::table('lms_courses')->where('status', 'published')->count(),
                'projects' => $this->safeCount('lms_projects'),
                'lessons' => $this->safeCount('lms_lessons'),
                'enrollments' => $this->safeCount('lms_enrollments'),
                'certificates' => $this->safeCount('lms_certificates'),
            ],
            'courses' => DB::table('lms_courses')->orderByDesc('id')->limit(12)->get(),
            'projects' => DB::table('lms_projects')->orderByDesc('id')->limit(12)->get(),
            'modules' => DB::table('lms_modules')->orderByDesc('id')->limit(20)->get(),
            'lessons' => DB::table('lms_lessons')->orderByDesc('id')->limit(20)->get(),
            'enrollments' => DB::table('lms_enrollments')->orderByDesc('id')->limit(12)->get(),
            'categories' => DB::table('lms_course_categories')->where('is_active', true)->orderBy('name')->get(),
            'products' => DB::table('products')->orderBy('name')->limit(300)->get(['id', 'name', 'sku']),
        ]);
    }

    public function inventory(): View
    {
        return view('admin.inventory', [
            'stats' => [
                'warehouses' => $this->safeCount('warehouses'),
                'stockRows' => $this->safeCount('inventory_stocks'),
                'availableUnits' => (int) DB::table('inventory_stocks')->sum('quantity_available'),
                'reservedUnits' => (int) DB::table('inventory_stocks')->sum('quantity_reserved'),
                'lowStockRows' => DB::table('inventory_stocks')->whereColumn('quantity_available', '<=', 'reorder_point')->count(),
                'movements' => $this->safeCount('inventory_movements'),
            ],
            'stocks' => DB::table('inventory_stocks as s')
                ->leftJoin('products as p', 'p.id', '=', 's.product_id')
                ->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')
                ->select('s.*', 'p.name as product_name', 'p.sku as product_sku', 'w.name as warehouse_name')
                ->orderByDesc('s.id')
                ->limit(15)
                ->get(),
            'movements' => DB::table('inventory_movements')->orderByDesc('id')->limit(15)->get(),
            'warehouses' => DB::table('warehouses')->orderBy('name')->get(),
            'products' => DB::table('products')->orderBy('name')->limit(300)->get(['id', 'name', 'sku']),
            'countries' => DB::table('countries')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function pos(): View
    {
        return view('admin.pos', [
            'stats' => [
                'terminals' => $this->safeCount('pos_terminals'),
                'openSessions' => DB::table('pos_sessions')->where('status', 'open')->count(),
                'sales' => $this->safeCount('pos_sales'),
                'paidSales' => DB::table('pos_sales')->where('payment_status', 'paid')->count(),
                'revenue' => DB::table('pos_sales')->sum('total_amount'),
            ],
            'sessions' => DB::table('pos_sessions')->orderByDesc('id')->limit(15)->get(),
            'sales' => DB::table('pos_sales')->orderByDesc('id')->limit(15)->get(),
            'terminals' => DB::table('pos_terminals')->orderByDesc('id')->limit(50)->get(),
            'warehouses' => DB::table('warehouses')->where('is_active', true)->orderBy('name')->get(),
            'products' => DB::table('products')->orderBy('name')->limit(300)->get(['id', 'name', 'sku', 'base_price']),
        ]);
    }

    public function settings(\Illuminate\Http\Request $request): View
    {
        $adminSettings = DB::table('admin_settings')
            ->when($request->query('group'), fn ($q, $group) => $q->where('group', $group))
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('key', 'ilike', "%{$term}%")->orWhere('description', 'ilike', "%{$term}%");
            }))
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        $marketplaceSettings = DB::table('marketplace_settings')
            ->when($request->query('marketplace_id'), fn ($q, $marketplace) => $q->where('marketplace_id', $marketplace))
            ->when($request->query('setting_group'), fn ($q, $group) => $q->where('group', $group))
            ->when($request->query('q'), fn ($q, $term) => $q->where('key', 'ilike', "%{$term}%"))
            ->orderBy('group')
            ->orderBy('key')
            ->limit(120)
            ->get();

        return view('admin.settings', [
            'adminSettings' => $adminSettings,
            'marketplaceSettings' => $marketplaceSettings,
            'marketplaces' => DB::table('marketplaces')->orderBy('id')->get(),
            'countries' => DB::table('countries')->where('is_active', true)->orderBy('name')->limit(80)->get(),
            'currencies' => DB::table('currencies')->where('is_active', true)->orderBy('code')->get(),
            'roles' => DB::table('roles')->orderBy('name')->get(),
            'settingGroups' => DB::table('admin_settings')->select('group')->distinct()->orderBy('group')->pluck('group'),
            'marketplaceGroups' => DB::table('marketplace_settings')->select('group')->distinct()->orderBy('group')->pluck('group'),
            'filters' => [
                'group' => (string) $request->query('group', ''),
                'setting_group' => (string) $request->query('setting_group', ''),
                'marketplace_id' => (string) $request->query('marketplace_id', ''),
                'q' => (string) $request->query('q', ''),
            ],
        ]);
    }

    public function media(\Illuminate\Http\Request $request): View
    {
        $assets = DB::table('admin_media_assets')
            ->when($request->query('folder'), fn ($q, $folder) => $q->where('folder', $folder))
            ->when($request->query('type'), fn ($q, $type) => $q->where('mime_type', 'ilike', "%{$type}%"))
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('title', 'ilike', "%{$term}%")
                    ->orWhere('original_name', 'ilike', "%{$term}%")
                    ->orWhere('alt_text', 'ilike', "%{$term}%");
            }))
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        return view('admin.media', [
            'assets' => $assets,
            'folders' => DB::table('admin_media_assets')->select('folder')->whereNotNull('folder')->distinct()->orderBy('folder')->get(),
            'filters' => [
                'folder' => (string) $request->query('folder', ''),
                'type' => (string) $request->query('type', ''),
                'q' => (string) $request->query('q', ''),
            ],
        ]);
    }

    public function seo(\Illuminate\Http\Request $request): View
    {
        $pages = DB::table('seo_pages')
            ->when($request->query('robots'), fn ($q, $robots) => $q->where('robots', $robots))
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('url_path', 'ilike', "%{$term}%")
                    ->orWhere('title', 'ilike', "%{$term}%")
                    ->orWhere('meta_description', 'ilike', "%{$term}%");
            }))
            ->orderBy('url_path')
            ->paginate(25)
            ->withQueryString();

        return view('admin.seo', [
            'pages' => $pages,
            'redirects' => DB::table('seo_redirects')->orderByDesc('id')->limit(50)->get(),
            'productMetaCount' => $this->safeCount('product_seo_meta'),
            'sitemapUrl' => url('/sitemap.xml'),
            'filters' => [
                'robots' => (string) $request->query('robots', ''),
                'q' => (string) $request->query('q', ''),
            ],
        ]);
    }


    public function marketing(): View
    {
        $stats = [
            'customers' => $this->safeCount('customer_profiles'),
            'segments' => $this->safeCount('customer_segments'),
            'newsletterSubscribers' => $this->safeCount('newsletter_subscribers'),
            'emailTemplates' => $this->safeCount('email_templates'),
            'emailCampaigns' => $this->safeCount('email_campaigns'),
            'whatsappCampaigns' => $this->safeCount('whatsapp_campaigns'),
            'abandonedCarts' => $this->safeCount('abandoned_carts'),
            'analyticsEvents' => $this->safeCount('analytics_events'),
        ];

        $recentEvents = DB::table('analytics_events')->orderByDesc('id')->limit(8)->get();
        $campaigns = DB::table('email_campaigns')->orderByDesc('id')->limit(6)->get();

        return view('admin.marketing.dashboard', compact('stats', 'recentEvents', 'campaigns'));
    }

    public function crm(): View
    {
        return view('admin.marketing.crm', [
            'customers' => DB::table('customer_profiles')->orderByDesc('id')->paginate(20),
            'segments' => DB::table('customer_segments')->orderBy('name')->get(),
            'contactLists' => DB::table('contact_lists')->orderBy('name')->get(),
            'suppressed' => $this->safeCount('suppression_lists'),
        ]);
    }

    public function newsletter(): View
    {
        return view('admin.marketing.newsletter', [
            'subscribers' => DB::table('newsletter_subscribers')->orderByDesc('id')->paginate(20),
            'categories' => DB::table('newsletter_categories')->orderBy('name')->get(),
            'campaigns' => DB::table('newsletter_campaigns')->orderByDesc('id')->limit(12)->get(),
        ]);
    }

    public function emailMarketing(): View
    {
        return view('admin.marketing.email', [
            'templates' => DB::table('email_templates')->orderBy('type')->get(),
            'campaigns' => DB::table('email_campaigns')->orderByDesc('id')->limit(15)->get(),
            'messages' => DB::table('email_messages')->orderByDesc('id')->limit(15)->get(),
        ]);
    }

    public function automation(): View
    {
        return view('admin.marketing.automation', [
            'rules' => DB::table('email_automation_rules')->orderBy('trigger')->get(),
            'runs' => DB::table('email_automation_runs')->orderByDesc('id')->limit(20)->get(),
            'jobs' => [
                'DetectAbandonedCartsJob' => 'Every 15 minutes',
                'CalculateTrendingProductsJob' => 'Hourly',
                'CalculateTrendingCategoriesJob' => 'Hourly',
                'CalculateTopSearchTermsJob' => 'Hourly',
                'RefreshCustomerSegmentJob' => 'Daily',
                'GenerateRegionalSalesReportJob' => 'Daily',
            ],
        ]);
    }

    public function abandonedCarts(): View
    {
        return view('admin.marketing.abandoned-carts', [
            'carts' => DB::table('abandoned_carts')->orderByDesc('id')->paginate(20),
            'openValue' => DB::table('abandoned_carts')->where('status', 'open')->sum('cart_total'),
            'recoveredValue' => DB::table('abandoned_cart_recoveries')->sum('recovered_revenue'),
        ]);
    }

    public function whatsapp(): View
    {
        return view('admin.marketing.whatsapp', [
            'templates' => DB::table('whatsapp_templates')->orderBy('name')->get(),
            'campaigns' => DB::table('whatsapp_campaigns')->orderByDesc('id')->limit(15)->get(),
            'optIns' => DB::table('whatsapp_opt_ins')->where('opted_in', true)->count(),
        ]);
    }

    public function marketingAnalytics(): View
    {
        return view('admin.marketing.analytics', [
            'events' => DB::table('analytics_events')->orderByDesc('id')->paginate(20),
            'topSearches' => DB::table('top_search_terms')->orderByDesc('search_count')->limit(20)->get(),
            'trendingProducts' => DB::table('trending_products')->orderByDesc('score')->limit(20)->get(),
            'trendingCategories' => DB::table('trending_categories')->orderByDesc('score')->limit(20)->get(),
        ]);
    }


    public function marketingAudit(): View
    {
        return view('admin.marketing.audit', [
            'logs' => DB::table('marketing_admin_audit_logs')->orderByDesc('id')->paginate(30),
        ]);
    }

    public function marketingSettings(): View
    {
        return view('admin.marketing.settings', [
            'marketingSettings' => DB::table('marketing_settings')->orderBy('key')->get(),
            'analyticsSettings' => DB::table('analytics_settings')->orderBy('key')->get(),
            'notificationSettings' => DB::table('notification_settings')->orderBy('key')->get(),
        ]);
    }

    // ---- Commerce ops (adaptation modules) ---------------------------------

    public function affiliate(): View
    {
        return view('admin.affiliate', [
            'stats' => [
                'affiliates' => $this->safeCount('affiliates'),
                'pending' => (int) DB::table('affiliates')->where('status', 'pending')->count(),
                'commissionsPending' => (float) DB::table('commission_ledger')->where('status', 'pending')->sum('commission_amount'),
                'commissionsApproved' => (float) DB::table('commission_ledger')->where('status', 'approved')->sum('commission_amount'),
                'payoutRequests' => $this->safeCount('affiliate_payout_requests'),
            ],
            'affiliates' => DB::table('affiliates')->orderByDesc('id')->limit(20)->get(),
            'commissions' => DB::table('commission_ledger')->orderByDesc('id')->limit(20)->get(),
            'rules' => DB::table('commission_rules')->orderBy('priority')->get(),
        ]);
    }

    public function promotions(): View
    {
        return view('admin.promotions', [
            'stats' => [
                'coupons' => $this->safeCount('coupons'),
                'activeCoupons' => (int) DB::table('coupons')->where('is_active', true)->count(),
                'giftCards' => $this->safeCount('gift_cards'),
                'giftBalance' => (float) DB::table('gift_cards')->where('status', 'active')->sum('current_balance'),
            ],
            'coupons' => DB::table('coupons')->orderByDesc('id')->limit(25)->get(),
            'giftCards' => DB::table('gift_cards')->orderByDesc('id')->limit(25)->get(),
        ]);
    }

    public function procurement(): View
    {
        return view('admin.procurement', [
            'stats' => [
                'suppliers' => $this->safeCount('suppliers'),
                'purchaseOrders' => $this->safeCount('purchase_orders'),
                'openValue' => (float) DB::table('purchase_orders')->whereIn('status', ['ordered', 'partially_received'])->sum('grand_total'),
            ],
            'suppliers' => DB::table('suppliers')->orderByDesc('id')->limit(20)->get(),
            'purchaseOrders' => DB::table('purchase_orders as po')
                ->leftJoin('suppliers as s', 's.id', '=', 'po.supplier_id')
                ->select('po.*', 's.name as supplier_name')->orderByDesc('po.id')->limit(20)->get(),
        ]);
    }

    public function quotations(): View
    {
        $rfqs = DB::table('rfq_requests')
            ->select('rfq_requests.*')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('admin.quotations', [
            'stats' => [
                'rfqTotal' => $this->safeCount('rfq_requests'),
                'rfqOpen' => (int) DB::table('rfq_requests')->where('status', 'open')->count(),
                'quotesSent' => (int) DB::table('quotations')->where('status', 'sent')->count(),
                'quotesAccepted' => (int) DB::table('quotations')->where('status', 'accepted')->count(),
            ],
            'rfqs' => $rfqs,
            'rfqItems' => DB::table('rfq_items')->whereIn('rfq_request_id', $rfqs->pluck('id'))->get()->groupBy('rfq_request_id'),
            'quotations' => DB::table('quotations')->orderByDesc('id')->limit(20)->get(),
            'quotationItems' => DB::table('quotation_items')->orderBy('id')->get()->groupBy('quotation_id'),
        ]);
    }

    public function quotationPreview(int $quotation): View
    {
        $quote = DB::table('quotations')->where('id', $quotation)->first();
        abort_if(! $quote, 404);

        return view('admin.quotation-preview', [
            'quote' => $quote,
            'items' => DB::table('quotation_items')->where('quotation_id', $quotation)->orderBy('id')->get(),
            'rfq' => $quote->rfq_request_id ? DB::table('rfq_requests')->where('id', $quote->rfq_request_id)->first() : null,
        ]);
    }

    public function expenses(): View
    {
        return view('admin.expenses', [
            'stats' => [
                'total' => (float) DB::table('expenses')->sum('amount'),
                'count' => $this->safeCount('expenses'),
            ],
            'byCategory' => DB::table('expenses')->select('category', DB::raw('sum(amount) as amount'))
                ->groupBy('category')->orderByDesc('amount')->get(),
            'expenses' => DB::table('expenses')->orderByDesc('expense_date')->orderByDesc('id')->limit(25)->get(),
        ]);
    }

    public function payments(): View
    {
        return view('admin.payments', [
            'stats' => [
                'providers' => $this->safeCount('payment_providers'),
                'enabled' => (int) DB::table('payment_providers')->where('is_enabled', true)->count(),
                'wallets' => $this->safeCount('wallets'),
                'walletBalance' => (float) DB::table('wallets')->sum('balance'),
                'payoutsPending' => (int) DB::table('vendor_payouts')->where('status', 'pending')->count(),
            ],
            'providers' => DB::table('payment_providers')->orderBy('sort_order')->get(),
            'vendorPayouts' => DB::table('vendor_payouts')->orderByDesc('id')->limit(20)->get(),
            'events' => DB::table('payment_transaction_events')->orderByDesc('id')->limit(15)->get(),
        ]);
    }

    public function orders(\Illuminate\Http\Request $request): View
    {
        $query = \App\Models\Order::with(['user', 'marketplace'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('payment'), fn ($q, $p) => $q->where('payment_status', $p))
            ->when($request->query('q'), fn ($q, $t) => $q->where('order_number', 'ilike', "%{$t}%"))
            ->orderByDesc('id');

        return view('admin.orders', [
            'orders' => $query->paginate(20)->withQueryString(),
            'stats' => [
                'total' => $this->safeCount('orders'),
                'pending' => $this->safeWhereCount('orders', 'status', 'pending'),
                'processing' => $this->safeWhereCount('orders', 'status', 'processing'),
                'delivered' => $this->safeWhereCount('orders', 'status', 'delivered'),
                'unpaid' => $this->safeWhereCount('orders', 'payment_status', 'pending'),
            ],
            'filters' => [
                'status' => (string) $request->query('status', ''),
                'payment' => (string) $request->query('payment', ''),
                'q' => (string) $request->query('q', ''),
            ],
        ]);
    }

    public function order(int $id): View
    {
        $order = \App\Models\Order::with(['user', 'marketplace', 'items', 'payments'])->findOrFail($id);

        return view('admin.order-detail', [
            'order' => $order,
            'history' => \App\Models\OrderStatusHistory::where('order_id', $id)->orderByDesc('id')->get(),
        ]);
    }

    public function invoice(int $id): View
    {
        return view('admin.invoice', [
            'order' => \App\Models\Order::with(['user', 'marketplace', 'items', 'payments'])->findOrFail($id),
        ]);
    }

    public function support(\Illuminate\Http\Request $request): View
    {
        $tickets = DB::table('support_tickets as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->leftJoin('users as a', 'a.id', '=', 't.assigned_to')
            ->leftJoin('customers as c', 'c.id', '=', 't.customer_id')
            ->select('t.*', 'u.name as requester_name', 'u.email as requester_email', 'a.name as assigned_name', 'c.name as customer_name')
            ->when($request->query('status'), fn ($q, $status) => $q->where('t.status', $status))
            ->when($request->query('priority'), fn ($q, $priority) => $q->where('t.priority', $priority))
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('t.ticket_number', 'ilike', "%{$term}%")
                    ->orWhere('t.subject', 'ilike', "%{$term}%")
                    ->orWhere('u.email', 'ilike', "%{$term}%")
                    ->orWhere('c.name', 'ilike', "%{$term}%");
            }))
            ->orderByRaw("case t.priority when 'urgent' then 1 when 'high' then 2 when 'medium' then 3 else 4 end")
            ->orderByDesc('t.id')
            ->paginate(20)
            ->withQueryString();

        $ticketIds = collect($tickets->items())->pluck('id');

        return view('admin.support', [
            'tickets' => $tickets,
            'messages' => DB::table('support_ticket_messages')
                ->whereIn('support_ticket_id', $ticketIds)
                ->orderBy('created_at')
                ->get()
                ->groupBy('support_ticket_id'),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'customers' => DB::table('customers')->orderBy('name')->limit(200)->get(['id', 'name', 'email']),
            'stats' => [
                'total' => $this->safeCount('support_tickets'),
                'open' => $this->safeWhereCount('support_tickets', 'status', 'open'),
                'pending' => $this->safeWhereCount('support_tickets', 'status', 'waiting_customer'),
                'closed' => $this->safeWhereCount('support_tickets', 'status', 'closed'),
            ],
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'status' => (string) $request->query('status', ''),
                'priority' => (string) $request->query('priority', ''),
            ],
        ]);
    }

    public function rfqs(\Illuminate\Http\Request $request): View
    {
        $query = \App\Models\Erp\RfqRequest::with('items')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('id');

        return view('admin.rfqs', [
            'rfqs' => $query->paginate(20)->withQueryString(),
            'stats' => [
                'total' => $this->safeCount('rfq_requests'),
                'open' => $this->safeWhereCount('rfq_requests', 'status', 'open'),
                'quoted' => $this->safeWhereCount('rfq_requests', 'status', 'quoted'),
                'accepted' => $this->safeWhereCount('rfq_requests', 'status', 'accepted'),
            ],
            'statusFilter' => (string) $request->query('status', ''),
        ]);
    }

    public function rfq(int $id): View
    {
        return view('admin.rfq-detail', [
            'rfq' => \App\Models\Erp\RfqRequest::with('items')->findOrFail($id),
            'history' => DB::table('rfq_status_histories')->where('rfq_request_id', $id)->orderByDesc('id')->get(),
        ]);
    }

    public function applications(): View
    {
        return view('admin.applications', [
            'stats' => [
                'sellerTotal' => $this->safeCount('seller_applications'),
                'sellerPending' => $this->safeWhereCount('seller_applications', 'status', 'pending'),
                'distributorTotal' => $this->safeCount('distributor_applications'),
                'distributorPending' => $this->safeWhereCount('distributor_applications', 'status', 'pending'),
                'aiSessions' => $this->safeCount('commerce_ai_sessions'),
            ],
            'sellerApps' => $this->safeRows('seller_applications'),
            'distributorApps' => $this->safeRows('distributor_applications'),
        ]);
    }

    public function regionStock(): View
    {
        return view('admin.region-stock', [
            'stats' => [
                'rules' => $this->safeCount('region_stock_visibilities'),
                'allocations' => $this->safeCount('territory_stock_allocations'),
                'reservations' => $this->safeWhereCount('stock_reservations', 'status', 'pending'),
                'alerts' => $this->safeWhereCount('low_stock_alerts', 'status', 'active'),
            ],
            'rules' => $this->safeRows('region_stock_visibilities'),
            'allocations' => $this->safeRows('territory_stock_allocations'),
            'reservations' => $this->safeRows('stock_reservations'),
            'alerts' => $this->safeRows('low_stock_alerts'),
        ]);
    }

    private function safeCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function safeWhereCount(string $table, string $column, string $value): int
    {
        try {
            return DB::table($table)->where($column, $value)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function safeSum(string $table, string $column): float
    {
        try {
            return (float) DB::table($table)->sum($column);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function safeLowStockCount(): int
    {
        try {
            return Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function safeRows(string $table, int $limit = 20): \Illuminate\Support\Collection
    {
        try {
            return DB::table($table)->orderByDesc('id')->limit($limit)->get();
        } catch (\Throwable) {
            return collect();
        }
    }
}
