<?php

namespace App\Http\Middleware;

use App\Services\Reseller\ResellerContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureResellerWeb
{
    public function __construct(private readonly ResellerContextService $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            return redirect('/reseller/login');
        }
        $reseller = $this->context->resellerFor($user);
        if (! $reseller) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/reseller/login')->withErrors(['email' => 'No reseller account linked.']);
        }
        $request->attributes->set('reseller', $reseller);
        return $next($request);
    }
}
