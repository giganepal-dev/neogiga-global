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
    public function showLogin(ResellerContextService $context): View|RedirectResponse
    {
        if (Auth::check() && $context->resellerFor(Auth::user())) {
            return redirect('/reseller');
        }
        return view('reseller.login');
    }

    public function login(Request $request, ResellerContextService $context): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'password' => ['required', 'string', 'max:120'],
        ]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        if (! $context->resellerFor(Auth::user())) {
            Auth::logout();
            return back()->withErrors(['email' => 'No reseller account linked.']);
        }
        return redirect()->intended('/reseller');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/reseller/login');
    }

    public function dashboard(Request $request): View
    {
        return view('reseller.dashboard', ['reseller' => $request->attributes->get('reseller')]);
    }

    public function products(Request $request): View
    {
        $reseller = $request->attributes->get('reseller');
        $products = DB::table('products')->where('reseller_id', $reseller->id)->orderByDesc('id')->paginate(20);
        return view('reseller.products', compact('reseller', 'products'));
    }

    public function orders(Request $request): View
    {
        $reseller = $request->attributes->get('reseller');
        $orders = DB::table('orders')->where('reseller_id', $reseller->id)->orderByDesc('created_at')->paginate(20);
        return view('reseller.orders', compact('reseller', 'orders'));
    }
}
