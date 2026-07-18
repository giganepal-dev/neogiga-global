<?php

namespace App\Http\Controllers\Web\Seller;

use App\Http\Controllers\Controller;
use App\Services\Seller\SellerContextService;
use App\Services\Seller\SellerDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Seller web portal (session auth): login + dashboard + own products/orders.
 * Reuses the seller API's context resolution (SellerContextService) and
 * dashboard aggregates (SellerDashboardService) — one truth for both surfaces.
 * Every data query is scoped to the vendor resolved by EnsureSellerWeb.
 */
class SellerPortalController extends Controller
{
    public function showLogin(SellerContextService $context): View|RedirectResponse
    {
        if (Auth::check() && $context->vendorFor(Auth::user())) {
            return redirect('/seller');
        }

        return view('seller.login');
    }

    public function login(Request $request, SellerContextService $context): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        if (! $context->vendorFor(Auth::user())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'No seller account is linked to this login. Apply via Sell on NeoGiga.']);
        }

        $request->session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended('/seller');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/seller/login');
    }

    public function dashboard(Request $request, SellerDashboardService $dashboard): View
    {
        $vendor = $request->attributes->get('vendor');

        return view('seller.dashboard', [
            'vendor' => $vendor,
            'overview' => $dashboard->overview($vendor),
        ]);
    }

    public function products(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');

        $products = DB::table('products')
            ->where('vendor_id', $vendor->id) // hard vendor scope — isolation invariant
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'ilike', "%{$term}%")
                ->orWhere('sku', 'ilike', "%{$term}%")
                ->orWhere('mpn', 'ilike', "%{$term}%")))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('seller.products', [
            'vendor' => $vendor,
            'products' => $products,
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'status' => (string) $request->query('status', ''),
            ],
        ]);
    }

    public function orders(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');

        $orders = Schema::hasTable('vendor_orders')
            ? DB::table('vendor_orders')
                ->where('vendor_id', $vendor->id) // hard vendor scope
                ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString()
            : null;

        return view('seller.orders', [
            'vendor' => $vendor,
            'orders' => $orders,
            'filters' => ['status' => (string) $request->query('status', '')],
        ]);
    }
}
