<?php

namespace App\Http\Middleware;

use App\Services\Distributor\DistributorContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the server-rendered distributor portal (web/session guard).
 * Requires an authenticated user linked to a distributor record.
 */
class EnsureDistributorWeb
{
    public function __construct(private readonly DistributorContextService $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect('/distributor/login');
        }

        $distributor = $this->context->distributorFor($user);

        if (! $distributor) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/distributor/login')->withErrors(['email' => 'No distributor account is linked to this login.']);
        }

        $request->attributes->set('distributor', $distributor);

        return $next($request);
    }
}
