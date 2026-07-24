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

    // Readiness & Onboarding
    public function readiness(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $profile = DB::table('vendor_profiles')->where('vendor_id', $v->id)->first();
        $marketplaces = DB::table('vendor_marketplace_approvals')->where('vendor_id', $v->id)->get();
        $warehouses = DB::table('vendor_warehouses')->where('vendor_id', $v->id)->get();
        
        $steps = [
            'business_profile' => (bool) $profile,
            'documents' => DB::table('vendor_documents')->where('vendor_id', $v->id)->count() > 0,
            'tax_registration' => $profile && !empty($profile->tax_id),
            'bank_details' => $profile && !empty($profile->bank_account),
            'warehouse' => $warehouses->where('status', 'approved')->count() > 0,
            'marketplace' => $marketplaces->where('status', 'approved')->count() > 0,
        ];
        
        $readinessPercentage = round((collect($steps)->filter()->count() / count($steps)) * 100);
        
        return view('seller.readiness', compact('v', 'steps', 'readinessPercentage', 'marketplaces', 'warehouses'));
    }

    // Notifications
    public function notifications(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $notifications = Schema::hasTable('seller_notifications')
            ? DB::table('seller_notifications')
                ->where('seller_id', $v->id)
                ->orderByDesc('created_at')
                ->paginate(50)
            : new LengthAwarePaginator([], 0, 50);
        
        return view('seller.notifications.index', compact('v', 'notifications'));
    }

    public function markNotificationsRead(Request $r)
    {
        $v = $r->attributes->get('vendor');
        if (Schema::hasTable('seller_notifications')) {
            DB::table('seller_notifications')
                ->where('seller_id', $v->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }
        return back()->with('status', 'Notifications marked as read.');
    }

    // Products - additional routes
    public function addProduct(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.products.add', compact('v'));
    }

    public function matchMpn(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.products.match', compact('v'));
    }

    public function importProducts(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.products.import', compact('v'));
    }

    public function draftProducts(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $products = DB::table('products')->where('vendor_id', $v->id)->where('status', 'draft')
            ->orderByDesc('updated_at')->paginate(20);
        return view('seller.products.drafts', compact('v', 'products'));
    }

    public function rejectedProducts(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $products = DB::table('products')->where('vendor_id', $v->id)->where('status', 'rejected')
            ->orderByDesc('updated_at')->paginate(20);
        return view('seller.products.rejected', compact('v', 'products'));
    }

    // Inventory - additional routes
    public function warehouseStock(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $warehouses = DB::table('vendor_warehouses')->where('vendor_id', $v->id)->get();
        return view('seller.inventory.warehouse', compact('v', 'warehouses'));
    }

    public function stockMovements(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $movements = Schema::hasTable('seller_inventory_movements')
            ? DB::table('seller_inventory_movements')
                ->where('seller_id', $v->id)
                ->orderByDesc('created_at')
                ->paginate(50)
            : new LengthAwarePaginator([], 0, 50);
        return view('seller.inventory.movements', compact('v', 'movements'));
    }

    public function reservations(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $reservations = collect([]); // Placeholder for future implementation
        return view('seller.inventory.reservations', compact('v', 'reservations'));
    }

    public function lowStockAlerts(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $alerts = collect([]); // Placeholder for future implementation
        return view('seller.inventory.alerts', compact('v', 'alerts'));
    }

    public function inventoryImport(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.inventory.import', compact('v'));
    }

    // Sales - RFQs, Quotations, Returns, Cancellations, Messages
    public function rfqs(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $rfqs = collect([]); // Placeholder for future implementation
        return view('seller.rfqs', compact('v', 'rfqs'));
    }

    public function quotations(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $quotations = collect([]); // Placeholder for future implementation
        return view('seller.quotations', compact('v', 'quotations'));
    }

    public function returns(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $returns = collect([]); // Placeholder for future implementation
        return view('seller.returns', compact('v', 'returns'));
    }

    public function cancellations(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $cancellations = collect([]); // Placeholder for future implementation
        return view('seller.cancellations', compact('v', 'cancellations'));
    }

    public function messages(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $messages = collect([]); // Placeholder for future implementation
        return view('seller.messages', compact('v', 'messages'));
    }

    // Logistics - Warehouses, Dispatch, Shipments, Pickups, Freight, Tracking
    public function warehouses(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $warehouses = DB::table('vendor_warehouses')->where('vendor_id', $v->id)->get();
        return view('seller.warehouses.index', compact('v', 'warehouses'));
    }

    public function dispatch(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.dispatch', compact('v'));
    }

    public function shipments(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $shipments = Schema::hasTable('seller_shipments')
            ? DB::table('seller_shipments')->where('seller_id', $v->id)->orderByDesc('created_at')->paginate(20)
            : new LengthAwarePaginator([], 0, 20);
        return view('seller.shipments', compact('v', 'shipments'));
    }

    public function pickups(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.pickups', compact('v'));
    }

    public function freight(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.freight', compact('v'));
    }

    public function tracking(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.tracking', compact('v'));
    }

    // Finance - Earnings, Statements, Commissions, Taxes
    public function earnings(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.earnings', compact('v'));
    }

    public function statements(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.statements', compact('v'));
    }

    public function commissions(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.commissions', compact('v'));
    }

    public function taxes(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.taxes', compact('v'));
    }

    // Marketplace - Access, Pricing, Offers, Performance, Compliance
    public function marketplaceAccess(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $applications = DB::table('vendor_marketplace_approvals')->where('vendor_id', $v->id)->get();
        return view('seller.marketplace', compact('v', 'applications'));
    }

    public function regionalPricing(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.pricing', compact('v'));
    }

    public function sellerOffers(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $offers = DB::table('seller_offers')->where('seller_id', $v->id)->orderByDesc('created_at')->paginate(20);
        return view('seller.offers', compact('v', 'offers'));
    }

    public function performance(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.performance', compact('v'));
    }

    public function compliance(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $complianceItems = collect([]); // Placeholder for future implementation
        return view('seller.compliance', compact('v', 'complianceItems'));
    }

    // Account - Documents, Team, Settings
    public function documents(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $documents = DB::table('vendor_documents')->where('vendor_id', $v->id)->get();
        return view('seller.documents', compact('v', 'documents'));
    }

    public function teamMembers(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        $members = Schema::hasTable('vendor_team_members')
            ? DB::table('vendor_team_members')->where('vendor_id', $v->id)->get()
            : collect([]);
        return view('seller.team', compact('v', 'members'));
    }

    public function settings(Request $r): View
    {
        $v = $r->attributes->get('vendor');
        return view('seller.settings', compact('v'));
    }
}
