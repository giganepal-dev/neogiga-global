<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the server-rendered admin console (web/session guard).
 * Requires an authenticated user whose role is an administrative one.
 * API/admin-token auth is separate (EnsureAdminToken); this protects the UI.
 */
class EnsureAdminWeb
{
    private const ADMIN_ROLES = ['super_admin', 'admin'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/admin/login');
        }

        if (!in_array($user->role->name ?? null, self::ADMIN_ROLES, true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/admin/login')->withErrors(['email' => 'This account does not have admin access.']);
        }

        return $next($request);
    }
}
