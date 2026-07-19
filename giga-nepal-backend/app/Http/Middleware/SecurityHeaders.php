<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers for every response (Blueprint §40, SEC-07).
 *
 * The CSP is intentionally strict: the landing page uses inline critical CSS
 * (allowed via 'unsafe-inline' on style-src only) and ships no scripts.
 * Tighten to nonces when a real asset pipeline lands.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (!$request->is('api/*')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; "
                . "img-src 'self' data: https:; "
                . "style-src 'self' 'unsafe-inline'; "
                . "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com; "
                . "font-src 'self'; "
                . "connect-src 'self' https://backend.neogiga.com https://pk.neogiga.com https://np.neogiga.com https://in.neogiga.com; "
                . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
            );
        }

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
