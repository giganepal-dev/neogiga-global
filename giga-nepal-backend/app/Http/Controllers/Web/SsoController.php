<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Auth\MarketplaceSsoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SsoController extends Controller
{
    public function start(Request $request, MarketplaceSsoService $sso): RedirectResponse
    {
        $data = $request->validate([
            'marketplace' => ['required', 'string', 'max:40'],
            'return_path' => ['nullable', 'string', 'max:500'],
        ]);

        $target = $sso->issue(
            $request->user(),
            $data['marketplace'],
            $request,
            (string) ($data['return_path'] ?? '/')
        );

        if (! $target) {
            return back()->with('error', 'Target marketplace is not available for SSO.');
        }

        return redirect()->away($target);
    }

    public function consume(Request $request, MarketplaceSsoService $sso): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:40', 'max:160'],
        ]);

        $returnPath = $sso->returnPathForToken($data['token']);
        $user = $sso->consume($data['token'], $request);

        if (! $user) {
            return redirect('/admin/login')->withErrors(['email' => 'SSO handoff expired or invalid. Please sign in again.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect($returnPath)->with('status', 'Signed in with NeoGiga SSO.');
    }
}
