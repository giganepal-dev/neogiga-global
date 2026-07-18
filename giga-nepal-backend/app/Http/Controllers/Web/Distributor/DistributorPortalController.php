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
    public function showLogin(DistributorContextService $context): View|RedirectResponse
    {
        if (Auth::check()) {
            $distributor = $context->distributorFor(Auth::user());
            if ($distributor) {
                return redirect('/distributor');
            }
        }

        return view('distributor.login');
    }

    public function login(Request $request, DistributorContextService $context): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'password' => ['required', 'string', 'max:120'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (! $context->distributorFor(Auth::user())) {
            Auth::logout();
            return back()->withErrors(['email' => 'No distributor account is linked to this login.']);
        }

        return redirect()->intended('/distributor');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/distributor/login');
    }

    public function dashboard(Request $request, DistributorDashboardService $dashboard): View
    {
        $distributor = $request->attributes->get('distributor');
        $overview = $dashboard->overview($distributor);

        return view('distributor.dashboard', [
            'distributor' => $distributor,
            'overview' => $overview,
        ]);
    }

    public function products(Request $request): View
    {
        $distributor = $request->attributes->get('distributor');
        $products = DB::table('products')
            ->where('distributor_id', $distributor->id)
            ->orderByDesc('id')
            ->paginate(20);

        return view('distributor.products', [
            'distributor' => $distributor,
            'products' => $products,
        ]);
    }

    public function orders(Request $request): View
    {
        $distributor = $request->attributes->get('distributor');
        $orders = DB::table('orders')
            ->where('distributor_id', $distributor->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('distributor.orders', [
            'distributor' => $distributor,
            'orders' => $orders,
        ]);
    }
}
