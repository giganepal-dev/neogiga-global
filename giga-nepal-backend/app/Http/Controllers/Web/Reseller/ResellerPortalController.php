<?php

namespace App\Http\Controllers\Web\Reseller;

use App\Http\Controllers\Controller;
use App\Services\Reseller\ResellerContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResellerPortalController extends Controller
{
    public function showLogin(ResellerContextService $c): View|RedirectResponse
    {
        if (Auth::check() && $c->resellerFor(Auth::user())) {
            return redirect('/reseller');
        }

return view('reseller.login');
    }

    public function login(Request $r, ResellerContextService $c): RedirectResponse
    {
        $r->validate(['email' => 'required|email|max:190', 'password' => 'required|string|max:120']);
        if (! Auth::attempt($r->only('email', 'password'), $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        if (! $c->resellerFor(Auth::user())) {
            Auth::logout();

            return back()->withErrors(['email' => 'No reseller account linked.']);
        }

        return redirect()->intended('/reseller');
    }

    public function logout(Request $r): RedirectResponse
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/reseller/login');
    }

    public function dashboard(Request $r): View
    {
        $r2 = $r->attributes->get('reseller');
        $stats = ['product_count' => DB::table('products')->where('reseller_id', $r2->id)->count(), 'order_count' => DB::table('orders')->where('reseller_id', $r2->id)->count()];

        return view('reseller.dashboard', compact('r2', 'stats'));
    }

    public function profile(Request $r): View
    {
        return view('reseller.profile', ['r2' => $r->attributes->get('reseller')]);
    }

    public function updateProfile(Request $r): RedirectResponse
    {
        $r2 = $r->attributes->get('reseller');
        DB::table('resellers')->where('id', $r2->id)->update(['company_name' => $r->input('company_name'), 'trading_name' => $r->input('trading_name'), 'phone' => $r->input('phone'), 'website' => $r->input('website'), 'business_address' => $r->input('business_address'), 'updated_at' => now()]);

        return back()->with('status', 'Profile updated.');
    }

    public function products(Request $r): View
    {
        $r2 = $r->attributes->get('reseller');
        $products = DB::table('products')->leftJoin('product_categories as c', 'c.id', '=', 'products.category_id')->select('products.*', 'c.name as category_name')->where('reseller_id', $r2->id)->orderByDesc('products.id')->paginate(20);

        return view('reseller.products', compact('r2', 'products'));
    }

    public function orders(Request $r): View
    {
        $r2 = $r->attributes->get('reseller');
        $orders = DB::table('orders')->where('reseller_id', $r2->id)->orderByDesc('created_at')->paginate(20);

        return view('reseller.orders',compact('r2','orders'));
    }
}
