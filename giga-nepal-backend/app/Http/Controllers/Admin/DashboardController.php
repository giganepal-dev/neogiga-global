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
        ];

        $marketplaces = Marketplace::with(['currency', 'country'])->orderBy('id')->get();

        $rootCategories = ProductCategory::whereNull('parent_id')
            ->withCount('children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(14)
            ->get();

        return view('admin.dashboard', compact('stats', 'marketplaces', 'rootCategories'));
    }

    public function categories(): View
    {
        $roots = ProductCategory::whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->withCount('children')->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $total = ProductCategory::count();

        return view('admin.categories', compact('roots', 'total'));
    }

    public function marketplaces(): View
    {
        $marketplaces = Marketplace::with(['currency', 'country', 'domains'])->orderBy('id')->get();

        return view('admin.marketplaces', compact('marketplaces'));
    }

    public function products(): View
    {
        $products = Product::orderByDesc('id')->paginate(20);

        return view('admin.products', compact('products'));
    }

    public function vendors(): View
    {
        $vendors = Vendor::orderByDesc('id')->paginate(20);

        return view('admin.vendors', compact('vendors'));
    }

    public function users(): View
    {
        $users = User::with('role')->orderByDesc('id')->paginate(20);

        return view('admin.users', compact('users'));
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
            'enrollments' => DB::table('lms_enrollments')->orderByDesc('id')->limit(12)->get(),
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
        ]);
    }

    public function settings(): View
    {
        return view('admin.settings', [
            'adminSettings' => DB::table('admin_settings')->orderBy('group')->orderBy('key')->get(),
            'marketplaceSettings' => DB::table('marketplace_settings')->orderBy('group')->orderBy('key')->limit(80)->get(),
            'marketplaces' => DB::table('marketplaces')->orderBy('id')->get(),
            'countries' => DB::table('countries')->where('is_active', true)->orderBy('name')->limit(80)->get(),
            'currencies' => DB::table('currencies')->where('is_active', true)->orderBy('code')->get(),
            'roles' => DB::table('roles')->orderBy('name')->get(),
        ]);
    }

    public function media(): View
    {
        return view('admin.media', [
            'assets' => DB::table('admin_media_assets')->orderByDesc('id')->paginate(24),
            'folders' => DB::table('admin_media_assets')->select('folder')->whereNotNull('folder')->distinct()->orderBy('folder')->get(),
        ]);
    }

    public function seo(): View
    {
        return view('admin.seo', [
            'pages' => DB::table('seo_pages')->orderBy('url_path')->paginate(25),
            'redirects' => DB::table('seo_redirects')->orderByDesc('id')->limit(50)->get(),
            'productMetaCount' => $this->safeCount('product_seo_meta'),
            'sitemapUrl' => url('/sitemap.xml'),
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

    private function safeCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
