<?php

namespace App\Http\Controllers\Web\B2B;

use App\Http\Controllers\Controller;
use App\Services\B2B\B2BContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class B2BPortalController extends Controller
{
    public function showLogin(B2BContextService $c): View|RedirectResponse
    {
        if (Auth::check() && $c->accountFor(Auth::user())) {
            return redirect('/b2b');
        }

return view('b2b.login');
    }

    public function login(Request $r, B2BContextService $c): RedirectResponse
    {
        $r->validate(['email' => 'required|email|max:190', 'password' => 'required|string|max:120']);
        if (! Auth::attempt($r->only('email', 'password'), $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        if (! $c->accountFor(Auth::user())) {
            Auth::logout();

            return back()->withErrors(['email' => 'No business account linked.']);
        }

        return redirect()->intended('/b2b');
    }

    public function logout(Request $r): RedirectResponse
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/b2b/login');
    }

    public function dashboard(Request $r): View
    {
        $a = $r->attributes->get('b2b_account');
        $stats = ['order_count' => DB::table('orders')->where('b2b_account_id', $a->id)->count(), 'rfq_count' => DB::table('rfq_requests')->where('b2b_account_id', $a->id)->count(), 'user_count' => DB::table('b2b_account_users')->where('b2b_account_id', $a->id)->where('is_active', true)->count()];

        return view('b2b.dashboard', compact('a', 'stats'));
    }

    public function orders(Request $r): View
    {
        $a = $r->attributes->get('b2b_account');
        $orders = DB::table('orders')->where('b2b_account_id', $a->id)->orderByDesc('created_at')->paginate(20);

        return view('b2b.orders', compact('a', 'orders'));
    }

    public function products(Request $r): View
    {
        $a = $r->attributes->get('b2b_account');
        $products = DB::table('b2b_price_list_items as i')
            ->join('b2b_price_lists as l', 'l.id', '=', 'i.b2b_price_list_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->where('l.b2b_account_id', $a->id)->where('l.is_active', true)
            ->select('p.*', 'i.unit_price', 'i.min_quantity')
            ->orderByDesc('p.id')->paginate(20);

        return view('b2b.products', compact('a', 'products'));
    }

    public function rfqs(Request $r): View
    {
        $a = $r->attributes->get('b2b_account');
        $rfqs = DB::table('rfq_requests')->where('b2b_account_id', $a->id)->orderByDesc('created_at')->paginate(20);

        return view('b2b.rfqs',compact('a','rfqs'));
    }
}
