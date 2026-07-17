<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended('/en');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
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

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/en')->with('status', 'Welcome to NeoGiga! Your account has been created.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/en');
    }
}
