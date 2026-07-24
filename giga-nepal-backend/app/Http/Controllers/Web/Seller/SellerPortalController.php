<?php

namespace App\Http\Controllers\Web\Seller;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\VendorSupportTicket;
use App\Services\Seller\SellerContextService;
use App\Services\Seller\SellerDashboardService;
use App\Support\Sql;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use App\Services\Partner\PartnerCountryService;

class SellerPortalController extends Controller
{
    public function showLogin(SellerContextService $c): View|RedirectResponse
    {
        if (Auth::check() && $c->vendorFor(Auth::user())) {
            return redirect('/seller');
        }

        return view('seller.login');
    }

    public function login(Request $r, SellerContextService $c): RedirectResponse
    {
        $r->validate(['email' => 'required|email|max:190', 'password' => 'required|string|max:120']);
        if (! Auth::attempt($r->only('email', 'password'), $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        if (! $c->vendorFor(Auth::user())) {
            Auth::logout();

            return back()->withErrors(['email' => 'No seller account linked.']);
        }

        return redirect()->intended('/seller');
    }

    public function logout(Request $r): RedirectResponse
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/seller/login');
    }

    public function dashboard(Request $r, SellerDashboardService $d): View
    {
        $v = $r->attributes->get('vendor');
        $overview = $d->overview($v);
        $stats = [
            'product_count' => $overview['products']['total_products'],
            'order_count' => $overview['orders']['total_orders'],
        ];
        $recentProducts = DB::table('products')->where('vendor_id', $v->id)
            ->select('id', 'name', 'sku', 'status', 'updated_at')
            ->latest('updated_at')->limit(5)->get();
        $recentOrders = Schema::hasTable('vendor_orders')
            ? DB::table('vendor_orders')->where('vendor_id', $v->id)->latest('created_at')->limit(5)->get()
            : collect();

        return view('seller.dashboard', compact('v', 'overview', 'stats', 'recentProducts', 'recentOrders'));
    }

    public function profile(Request $r, PartnerCountryService $countries): View
    {
        return view('seller.profile', ['v' => $r->attributes->get('vendor'), 'countries' => $countries->activeCountries()]);
    }

    public function updateProfile(Request $r, PartnerCountryService $countries): RedirectResponse
    {
        $v = $r->attributes->get('vendor');
        $data = $r->validate([
            'name' => ['required', 'string', 'max:190'], 'email' => ['nullable', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'], 'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'], 'country_id' => ['nullable', 'integer'],
            'operating_scope' => ['required', 'in:country,global'],
        ]);
        $data['country_id'] = ! empty($data['country_id'])
            ? $countries->assertActiveCountryId($data['country_id'])
            : ($v->status === 'pending' ? $countries->assertActiveCountryId(null) : null);
        $data['operating_scope'] = $countries->normalizeScope($data['operating_scope']);
        $scopeChangeRestricted = $v->status !== 'pending'
            && ($data['operating_scope'] !== ($v->operating_scope ?? 'country') || $data['country_id'] !== (int) $v->country_id);
        if ($scopeChangeRestricted) {
            $data['operating_scope'] = $v->operating_scope ?? 'country';
            $data['country_id'] = (int) $v->country_id;
        }
        DB::transaction(function () use ($v, $data, $countries): void {
            DB::table('vendors')->where('id', $v->id)->update($data + ['updated_at' => now()]);
            foreach ($data['country_id'] ? $countries->marketplaceIdsForScope($data['operating_scope'], $data['country_id']) : [] as $marketplaceId) {
                DB::table('vendor_marketplace_approvals')->insertOrIgnore([
                    'vendor_id' => $v->id, 'marketplace_id' => $marketplaceId, 'status' => 'pending',
                    'application_notes' => 'Operating scope requested from seller profile.',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        return back()->with('status', $scopeChangeRestricted
            ? 'Profile updated. Open a support ticket to request a country or global-scope change.'
            : 'Profile and operating scope updated. New marketplace access remains subject to approval.');
    }

    public function products(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $like = Sql::ilike();
        $products = DB::table('products')->where('vendor_id', $v->id)
            ->when($r->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w->where('name', $like, "%{$term}%")->orWhere('sku', $like, "%{$term}%")->orWhere('mpn', $like, "%{$term}%")))
            ->when($r->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        $filters = ['q' => (string) $r->query('q', ''), 'status' => (string) $r->query('status', '')];

        return view('seller.products', compact('v', 'products', 'filters'));
    }

    public function orders(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $orders = Schema::hasTable('vendor_orders')
            ? DB::table('vendor_orders')->where('vendor_id', $v->id)
                ->when($r->query('status'), fn ($q, $s) => $q->where('status', $s))
                ->orderByDesc('id')->paginate(20)->withQueryString()
            : null;

        return view('seller.orders', ['v' => $v, 'orders' => $orders, 'filters' => ['status' => (string) $r->query('status', '')]]);
    }

    public function inventory(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $inventory = Schema::hasTable('vendor_inventory')
            ? DB::table('vendor_inventory')
                ->leftJoin('products', 'products.id', '=', 'vendor_inventory.product_id')
                ->leftJoin('warehouses', 'warehouses.id', '=', 'vendor_inventory.warehouse_id')
                ->where('vendor_inventory.vendor_id', $v->id)
                ->select([
                    'vendor_inventory.*',
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'warehouses.name as warehouse_name',
                ])
                ->orderBy('products.name')->paginate(20)->withQueryString()
            : new LengthAwarePaginator([], 0, 20);

        return view('seller.inventory', compact('v', 'inventory'));
    }

    public function payouts(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $payouts = Schema::hasTable('vendor_payouts')
            ? DB::table('vendor_payouts')->where('vendor_id', $v->id)->latest('id')->paginate(20)
            : new LengthAwarePaginator([], 0, 20);

        return view('seller.payouts', compact('v', 'payouts'));
    }

    public function support(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $tickets = Schema::hasTable('vendor_support_tickets')
            ? VendorSupportTicket::query()->where('vendor_id', $v->id)->latest()->paginate(20)
            : new LengthAwarePaginator([], 0, 20);

        return view('seller.support', compact('v', 'tickets'));
    }

    public function storeSupport(Request $r): RedirectResponse
    {
        $v = $r->attributes->get('vendor');
        abort_unless(Schema::hasTable('vendor_support_tickets'), 503, 'Seller support is not available yet.');
        $data = $r->validate([
            'subject' => ['required', 'string', 'min:3', 'max:190'],
            'category' => ['required', 'in:general,account,products,orders,payouts,technical'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        VendorSupportTicket::create([
            ...$data,
            'vendor_id' => $v->id,
            'user_id' => Auth::id(),
            'ticket_number' => 'VST-'.now()->format('YmdHis').'-'.$v->id,
            'status' => 'open',
        ]);

        return back()->with('status', 'Support ticket opened.');
    }

    /**
     * Seller onboarding readiness checklist.
     */
    public function readiness(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $vendorId = $v->id;

        // Business profile completeness
        $profileComplete = ! empty($v->name) && ! empty($v->email) && ! empty($v->phone);
        $hasDescription = ! empty($v->description);
        $hasWebsite = ! empty($v->website);
        $profileScore = collect([$profileComplete, $hasDescription, $hasWebsite])->filter()->count();
        $profileTotal = 3;

        // Verification status
        $isVerified = (bool) ($v->is_verified ?? false);
        $hasDocuments = Schema::hasTable('vendor_documents')
            ? DB::table('vendor_documents')->where('vendor_id', $vendorId)->exists()
            : false;
        $verifiedDocuments = Schema::hasTable('vendor_documents')
            ? DB::table('vendor_documents')->where('vendor_id', $vendorId)->where('status', 'verified')->count()
            : 0;

        // Marketplace application status
        $marketplaceApprovals = Schema::hasTable('vendor_marketplace_approvals')
            ? DB::table('vendor_marketplace_approvals')
                ->leftJoin('marketplaces', 'marketplaces.id', '=', 'vendor_marketplace_approvals.marketplace_id')
                ->where('vendor_marketplace_approvals.vendor_id', $vendorId)
                ->select('vendor_marketplace_approvals.*', 'marketplaces.name as marketplace_name')
                ->get()
            : collect();
        $approvedMarketplaces = $marketplaceApprovals->where('status', 'approved');
        $hasApprovedMarketplace = $approvedMarketplaces->isNotEmpty();

        // Warehouse setup
        $hasWarehouse = Schema::hasTable('warehouses')
            ? DB::table('warehouses')->where('vendor_id', $vendorId)->exists()
            : false;
        $warehouseCount = Schema::hasTable('warehouses')
            ? DB::table('warehouses')->where('vendor_id', $vendorId)->count()
            : 0;

        // Product listing
        $hasProducts = DB::table('products')->where('vendor_id', $vendorId)->exists();
        $approvedProducts = DB::table('products')->where('vendor_id', $vendorId)->where('status', 'approved')->count();

        // Build checklist
        $checklist = [
            ['label' => 'Business profile created', 'done' => $profileComplete, 'detail' => 'Name, email, and phone are required', 'link' => '/seller/profile'],
            ['label' => 'Profile description added', 'done' => $hasDescription, 'detail' => 'Helps buyers understand your business', 'link' => '/seller/profile'],
            ['label' => 'Website listed', 'done' => $hasWebsite, 'detail' => 'Optional but recommended for trust', 'link' => '/seller/profile'],
            ['label' => 'Compliance documents uploaded', 'done' => $hasDocuments, 'detail' => $hasDocuments ? $verifiedDocuments . ' document(s) verified' : 'Business license, tax certificate, or ID proof', 'link' => '/seller/documents'],
            ['label' => 'Identity verification', 'done' => $isVerified, 'detail' => $isVerified ? 'Your account is verified' : 'Pending admin review', 'link' => '/seller/readiness'],
            ['label' => 'Marketplace application submitted', 'done' => $marketplaceApprovals->isNotEmpty(), 'detail' => $marketplaceApprovals->count() . ' application(s) submitted', 'link' => '/seller/marketplace'],
            ['label' => 'Marketplace access approved', 'done' => $hasApprovedMarketplace, 'detail' => $approvedMarketplaces->count() . ' marketplace(s) approved', 'link' => '/seller/marketplace'],
            ['label' => 'Warehouse configured', 'done' => $hasWarehouse, 'detail' => $warehouseCount . ' warehouse(s) set up', 'link' => '/seller/warehouses'],
            ['label' => 'First product listed', 'done' => $hasProducts, 'detail' => $approvedProducts . ' approved product(s)', 'link' => '/seller/products'],
        ];

        $doneCount = collect($checklist)->where('done', true)->count();
        $total = count($checklist);
        $progressPercent = $total > 0 ? round(($doneCount / $total) * 100) : 0;

        return view('seller.readiness', compact('v', 'checklist', 'doneCount', 'total', 'progressPercent', 'isVerified', 'hasApprovedMarketplace', 'marketplaceApprovals'));
    }

    /**
     * Seller notification center.
     */
    public function notifications(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $vendorId = $v->id;

        if (! Schema::hasTable('seller_notifications')) {
            return view('seller.notifications', ['v' => $v, 'notifications' => new LengthAwarePaginator([], 0, 20), 'unreadCount' => 0]);
        }

        $notifications = DB::table('seller_notifications')
            ->where('vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $unreadCount = DB::table('seller_notifications')
            ->where('vendor_id', $vendorId)
            ->where('is_read', false)
            ->count();

        return view('seller.notifications', compact('v', 'notifications', 'unreadCount'));
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationRead(Request $r, int $notificationId): RedirectResponse
    {
        $v = $r->attributes->get('vendor');
        if (Schema::hasTable('seller_notifications')) {
            DB::table('seller_notifications')
                ->where('id', $notificationId)
                ->where('vendor_id', $v->id)
                ->update(['is_read' => true, 'read_at' => now()]);
        }

        return back()->with('status', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsRead(Request $r): RedirectResponse
    {
        $v = $r->attributes->get('vendor');
        if (Schema::hasTable('seller_notifications')) {
            DB::table('seller_notifications')
                ->where('vendor_id', $v->id)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);
        }

        return back()->with('status', 'All notifications marked as read.');
    }

    /**
     * Warehouse management listing.
     */
    public function warehouses(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $vendorId = $v->id;

        $warehouses = Schema::hasTable('warehouses')
            ? DB::table('warehouses')
                ->where('vendor_id', $vendorId)
                ->leftJoin('countries', 'countries.id', '=', 'warehouses.country_id')
                ->select('warehouses.*', 'countries.name as country_name')
                ->orderByDesc('is_default')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString()
            : new LengthAwarePaginator([], 0, 20);

        $stats = [
            'total' => $warehouses->total(),
            'active' => Schema::hasTable('warehouses')
                ? DB::table('warehouses')->where('vendor_id', $vendorId)->where('is_active', true)->count()
                : 0,
        ];

        return view('seller.warehouses', compact('v', 'warehouses', 'stats'));
    }

    /**
     * Seller offers management.
     */
    public function offers(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $vendorId = $v->id;

        $offers = Schema::hasTable('seller_offers')
            ? DB::table('seller_offers')
                ->where('seller_offers.seller_id', $vendorId)
                ->leftJoin('canonical_products', 'canonical_products.id', '=', 'seller_offers.canonical_product_id')
                ->leftJoin('warehouses', 'warehouses.id', '=', 'seller_offers.warehouse_id')
                ->leftJoin('marketplaces', 'marketplaces.id', '=', 'seller_offers.marketplace_id')
                ->select(
                    'seller_offers.*',
                    'canonical_products.mpn as product_mpn',
                    'canonical_products.brand_name as product_brand',
                    'canonical_products.description as product_name',
                    'warehouses.name as warehouse_name',
                    'marketplaces.name as marketplace_name'
                )
                ->when($r->query('status'), fn ($q, $s) => $q->where('seller_offers.status', $s))
                ->orderByDesc('seller_offers.id')
                ->paginate(20)
                ->withQueryString()
            : new LengthAwarePaginator([], 0, 20);

        return view('seller.offers', ['v' => $v, 'offers' => $offers, 'filters' => ['status' => (string) $r->query('status', '')]]);
    }

    /**
     * RFQ responses listing.
     */
    public function rfqs(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $vendorId = $v->id;

        $assignments = Schema::hasTable('rfq_assignments')
            ? DB::table('rfq_assignments')
                ->where('rfq_assignments.vendor_id', $vendorId)
                ->leftJoin('rfq_requests', 'rfq_requests.id', '=', 'rfq_assignments.rfq_id')
                ->leftJoin('rfq_items', 'rfq_items.rfq_request_id', '=', 'rfq_requests.id')
                ->select(
                    'rfq_assignments.*',
                    'rfq_requests.rfq_number',
                    'rfq_requests.status as rfq_status',
                    'rfq_requests.company_name',
                    'rfq_requests.contact_name',
                    'rfq_requests.currency',
                    'rfq_requests.notes',
                    DB::raw('COUNT(DISTINCT rfq_items.id) as item_count'),
                    DB::raw('SUM(COALESCE(rfq_items.quantity, 0)) as total_quantity')
                )
                ->groupBy(
                    'rfq_assignments.id', 'rfq_assignments.rfq_id', 'rfq_assignments.vendor_id',
                    'rfq_assignments.status', 'rfq_assignments.invited_at', 'rfq_assignments.deadline_at',
                    'rfq_assignments.admin_notes', 'rfq_assignments.created_at', 'rfq_assignments.updated_at',
                    'rfq_requests.rfq_number', 'rfq_requests.status', 'rfq_requests.company_name',
                    'rfq_requests.contact_name', 'rfq_requests.currency', 'rfq_requests.notes'
                )
                ->orderByDesc('rfq_assignments.created_at')
                ->paginate(20)
                ->withQueryString()
            : new LengthAwarePaginator([], 0, 20);

        return view('seller.rfqs', compact('v', 'assignments'));
    }

    /**
     * Quotation management listing.
     */
    public function quotations(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $vendorId = $v->id;

        $quotations = Schema::hasTable('quotations')
            ? DB::table('quotations')
                ->leftJoin('rfq_requests', 'rfq_requests.id', '=', 'quotations.rfq_request_id')
                ->leftJoin('quotation_items', 'quotation_items.quotation_id', '=', 'quotations.id')
                ->where('quotations.created_by', $v->user_id)
                ->select(
                    'quotations.*',
                    'rfq_requests.rfq_number',
                    'rfq_requests.company_name',
                    DB::raw('COUNT(DISTINCT quotation_items.id) as item_count')
                )
                ->groupBy(
                    'quotations.id', 'quotations.quote_number', 'quotations.rfq_request_id',
                    'quotations.user_id', 'quotations.currency', 'quotations.status',
                    'quotations.subtotal', 'quotations.tax_total', 'quotations.shipping_total',
                    'quotations.grand_total', 'quotations.valid_until', 'quotations.sent_at',
                    'quotations.accepted_at', 'quotations.created_by', 'quotations.notes',
                    'quotations.meta', 'quotations.created_at', 'quotations.updated_at',
                    'rfq_requests.rfq_number', 'rfq_requests.company_name'
                )
                ->orderByDesc('quotations.id')
                ->paginate(20)
                ->withQueryString()
            : new LengthAwarePaginator([], 0, 20);

        return view('seller.quotations', compact('v', 'quotations'));
    }

    /**
     * Earnings overview calculated from vendor orders.
     */
    public function earnings(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $vendorId = $v->id;

        if (Schema::hasTable('vendor_orders')) {
            $totals = DB::table('vendor_orders')
                ->where('vendor_id', $vendorId)
                ->selectRaw('COALESCE(SUM(vendor_net_total), 0) as total_earned')
                ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('pending','processing') THEN vendor_net_total ELSE 0 END), 0) as pending_earning")
                ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('fulfilled','delivered','shipped') THEN vendor_net_total ELSE 0 END), 0) as completed_earning")
                ->first();

            $payoutTotals = Schema::hasTable('vendor_payouts')
                ? DB::table('vendor_payouts')
                    ->where('vendor_id', $vendorId)
                    ->selectRaw("COALESCE(SUM(CASE WHEN status = 'paid' THEN net_amount ELSE 0 END), 0) as paid_out")
                    ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN net_amount ELSE 0 END), 0) as pending_payout")
                    ->first()
                : (object) ['paid_out' => 0, 'pending_payout' => 0];

            $recentOrders = DB::table('vendor_orders')
                ->where('vendor_id', $vendorId)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        } else {
            $totals = (object) ['total_earned' => 0, 'pending_earning' => 0, 'completed_earning' => 0];
            $payoutTotals = (object) ['paid_out' => 0, 'pending_payout' => 0];
            $recentOrders = collect();
        }

        return view('seller.earnings', compact('v', 'totals', 'payoutTotals', 'recentOrders'));
    }

    /**
     * Financial statements (payout history).
     */
    public function statements(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $vendorId = $v->id;

        $statements = Schema::hasTable('vendor_payouts')
            ? DB::table('vendor_payouts')
                ->where('vendor_id', $vendorId)
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString()
            : new LengthAwarePaginator([], 0, 20);

        $summary = Schema::hasTable('vendor_payouts')
            ? DB::table('vendor_payouts')
                ->where('vendor_id', $vendorId)
                ->selectRaw("COALESCE(SUM(CASE WHEN status = 'paid' THEN net_amount ELSE 0 END), 0) as total_paid")
                ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN net_amount ELSE 0 END), 0) as total_pending")
                ->selectRaw('COUNT(*) as total_count')
                ->first()
            : (object) ['total_paid' => 0, 'total_pending' => 0, 'total_count' => 0];

        return view('seller.statements', compact('v', 'statements', 'summary'));
    }

    /**
     * Account settings.
     */
    public function settings(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $vendorId = $v->id;

        // Notification preferences from seller_notifications table
        $notificationPreferences = [];
        if (Schema::hasTable('seller_notifications')) {
            $events = DB::table('seller_notifications')
                ->where('vendor_id', $vendorId)
                ->distinct()
                ->pluck('event')
                ->toArray();
            $notificationPreferences = $events;
        }

        return view('seller.settings', compact('v', 'notificationPreferences'));
    }

    /**
     * Generic handler for seller portal pages under development.
     */
    public function page(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $path = $r->path();
        $section = str_replace(['seller/', '/'], ['', ' '], $path);
        $titleMap = [
            'products add' => 'Add Product',
            'products match' => 'Match Existing MPN',
            'products import' => 'Bulk Import',
            'products drafts' => 'Drafts',
            'products rejected' => 'Rejected Products',
            'inventory warehouse' => 'Warehouse Stock',
            'inventory movements' => 'Stock Movements',
            'inventory reservations' => 'Reservations',
            'inventory alerts' => 'Low Stock Alerts',
            'inventory import' => 'Inventory Import',
            'returns' => 'Returns',
            'cancellations' => 'Cancellations',
            'messages' => 'Customer Messages',
            'dispatch' => 'Dispatch',
            'shipments' => 'Shipments',
            'pickups' => 'Pickup Requests',
            'freight' => 'Freight',
            'tracking' => 'Tracking',
            'commissions' => 'Commissions',
            'taxes' => 'Taxes & Invoices',
            'marketplace' => 'Marketplace Access',
            'pricing' => 'Regional Pricing',
            'performance' => 'Performance',
            'compliance' => 'Compliance',
            'documents' => 'Documents',
            'team' => 'Team Members',
        ];
        $pageTitle = $titleMap[trim($section)] ?? ucwords(str_replace('-', ' ', trim($section)));

        return view('seller.placeholder', compact('v', 'pageTitle'));
    }
}
