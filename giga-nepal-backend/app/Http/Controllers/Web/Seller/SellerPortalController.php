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

    public function profile(Request $r): View
    {
        return view('seller.profile', ['v' => $r->attributes->get('vendor')]);
    }

    public function updateProfile(Request $r): RedirectResponse
    {
        $v = $r->attributes->get('vendor');
        DB::table('vendors')->where('id', $v->id)->update(['name' => $r->input('name'), 'email' => $r->input('email'), 'phone' => $r->input('phone'), 'website' => $r->input('website'), 'description' => $r->input('description'), 'updated_at' => now()]);

        return back()->with('status', 'Profile updated.');
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
}
