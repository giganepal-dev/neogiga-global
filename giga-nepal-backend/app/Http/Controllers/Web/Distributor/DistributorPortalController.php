<?php

namespace App\Http\Controllers\Web\Distributor;

use App\Http\Controllers\Controller;
use App\Services\Distributor\DistributorContextService;
use App\Services\Distributor\DistributorDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DistributorPortalController extends Controller
{
    public function showLogin(DistributorContextService $c): View|RedirectResponse
    {
        if (Auth::check() && $c->distributorFor(Auth::user())) {
            return redirect('/distributor');
        }

        return view('distributor.login');
    }

    public function login(Request $r, DistributorContextService $c): RedirectResponse
    {
        $r->validate(['email' => 'required|email|max:190', 'password' => 'required|string|max:120']);
        if (! Auth::attempt($r->only('email', 'password'), $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        if (! $c->distributorFor(Auth::user())) {
            Auth::logout();

            return back()->withErrors(['email' => 'No distributor account linked.']);
        }

        return redirect()->intended('/distributor');
    }

    public function logout(Request $r): RedirectResponse
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/distributor/login');
    }

    public function dashboard(Request $r, DistributorDashboardService $dashboard): View
    {
        $d = $r->attributes->get('distributor');
        $stats = [
            'product_count' => DB::table('products')->where('distributor_id', $d->id)->count(),
            'order_count' => DB::table('orders')->where('distributor_id', $d->id)->count(),
            'revenue' => DB::table('orders')->where('distributor_id', $d->id)->whereIn('status', ['completed', 'shipped', 'delivered'])->sum('grand_total') ?? 0,
        ];

        return view('distributor.dashboard', compact('d', 'stats'));
    }

    public function profile(Request $r): View
    {
        return view('distributor.profile', ['d' => $r->attributes->get('distributor')]);
    }

    public function updateProfile(Request $r): RedirectResponse
    {
        $d = $r->attributes->get('distributor');
        $meta = json_decode($d->metadata ?? '{}', true) ?: [];
        $meta['website'] = $r->input('website', $meta['website'] ?? '');
        $meta['description'] = $r->input('description', $meta['description'] ?? '');
        DB::table('distributors')->where('id', $d->id)->update([
            'name' => $r->input('name'), 'phone' => $r->input('phone'),
            'country_id' => $r->input('country_id') ?: null,
            'metadata' => json_encode($meta), 'updated_at' => now(),
        ]);

        return back()->with('status', 'Profile updated.');
    }

    public function products(Request $r): View
    {
        $d = $r->attributes->get('distributor');
        $products = DB::table('products')->leftJoin('product_categories as c', 'c.id', '=', 'products.category_id')
            ->select('products.*', 'c.name as category_name')->where('distributor_id', $d->id)
            ->orderByDesc('id')->paginate(20);

        return view('distributor.products', compact('d', 'products'));
    }

    public function orders(Request $r): View
    {
        $d = $r->attributes->get('distributor');
        $orders = DB::table('orders')->where('distributor_id', $d->id)->orderByDesc('created_at')->paginate(20);

        return view('distributor.orders', compact('d', 'orders'));
    }
}
