<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Marketing\AccountCommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        return Auth::check()
            ? redirect('/en')
            : view('frontend.auth.login');
    }

    public function showRegister(): View|RedirectResponse
    {
        return Auth::check()
            ? redirect('/en')
            : view('frontend.auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'password' => ['required', 'string', 'max:120'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The email or password is incorrect.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        // Role-based dashboard redirect
        $dashboard = '/en';
        if (DB::table('b2b_account_users')->where('user_id', $user->id)->where('is_active', true)->exists()) {
            $dashboard = '/b2b';
        } elseif (DB::table('manufacturers')->where('user_id', $user->id)->where('is_active', true)->exists()) {
            $dashboard = '/manufacturer';
        } elseif (DB::table('resellers')->where('user_id', $user->id)->exists()) {
            $dashboard = '/reseller';
        } elseif (DB::table('distributors')->where('user_id', $user->id)->exists()) {
            $dashboard = '/distributor';
        } elseif (DB::table('vendors')->where('user_id', $user->id)->exists()) {
            $dashboard = '/seller';
        } elseif ($user->is_admin ?? false) {
            $dashboard = '/admin';
        }

        return redirect()->intended($dashboard);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'company_name' => ['nullable', 'string', 'min:1', 'max:190'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:120', 'confirmed'],
            'terms' => ['accepted'],
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'customer'],
            [
                'display_name' => 'Customer',
                'description' => 'NeoGiga customer account',
                'permissions' => ['cart.manage', 'checkout.create', 'orders.view'],
                'is_active' => true,
            ],
        );

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role_id' => $role->id,
        ]);

        // Store company name in customer profile if provided
        if (! empty($data['company_name'])) {
            try {
                DB::table('customer_profiles')->updateOrInsert(
                    ['user_id' => $user->id],
                    ['company_name' => $data['company_name'], 'email' => $user->email, 'created_at' => now(), 'updated_at' => now()]
                );
            } catch (\Throwable) {
                // Non-critical — company name stored if table supports it
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        // Queue registration and verification emails (non-blocking)
        try {
            app(AccountCommunicationService::class)->registration($user);
        } catch (\Throwable) {
            // Email failure must not block registration
        }

        return redirect('/en/login')->with('registration_success', [
            'name' => $user->name,
            'email' => $user->email,
            'company_name' => $data['company_name'] ?? null,
            'verification_sent' => true,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/en');
    }
}
