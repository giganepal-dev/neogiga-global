<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PcbPortalAuthController extends Controller
{
    public function login(): View|RedirectResponse
    {
        return Auth::check()
            ? redirect('/en/projects')
            : view('pcb.auth', ['mode' => 'login']);
    }

    public function register(): View|RedirectResponse
    {
        return Auth::check()
            ? redirect('/en/projects')
            : view('pcb.auth', ['mode' => 'register']);
    }

    public function authenticate(Request $request): RedirectResponse
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

        return redirect()->intended('/en/projects');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'max:120', 'confirmed'],
            'terms' => ['accepted'],
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'customer'],
            [
                'display_name' => 'Customer',
                'description' => 'Default NeoGiga customer account',
                'permissions' => ['cart.manage', 'checkout.create', 'orders.view', 'pcb.projects.manage'],
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

        return redirect('/en/projects')->with('status', 'Your secure PCB workspace is ready.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/en')->with('status', 'You have signed out.');
    }
}
