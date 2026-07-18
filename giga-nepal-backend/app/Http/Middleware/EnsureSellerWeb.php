<?php

namespace App\Http\Middleware;

use App\Services\Seller\SellerContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the server-rendered seller portal (web/session guard).
 * Requires an authenticated user with a linked vendor (owner via
 * vendors.user_id or active vendor_staff row — same resolution the seller API
 * uses through SellerContextService). The resolved vendor is attached to the
 * request so controllers never re-resolve, and every portal query scopes to it.
 */
class EnsureSellerWeb
{
    public function __construct(private readonly SellerContextService $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect('/seller/login');
        }

        $vendor = $this->context->vendorFor($user);

        if (! $vendor) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/seller/login')->withErrors(['email' => 'No seller account is linked to this login.']);
        }

        $request->attributes->set('vendor', $vendor);

        return $next($request);
    }
}
