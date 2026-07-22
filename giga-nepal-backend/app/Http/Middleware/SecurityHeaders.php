<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers for every response (Blueprint §40, SEC-07).
 *
 * CSP uses per-request nonces (shared via View) so inline critical CSS and
 * GTM/GA bootstrap scripts function without 'unsafe-inline'.
 * 'strict-dynamic' lets GTM load further scripts without allow-listing each
 * origin; the GTM + GA origins remain as fallback for browsers that don't
 * support CSP Level 3.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(32);

        // Share with Blade so <style nonce="..."> and <script nonce="..."> match
        view()->share('csp_nonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (!$request->is('api/*')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; "
                . "img-src 'self' data: https:; "
                . "style-src 'self' 'nonce-{$nonce}'; "
                . "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic' https://www.googletagmanager.com https://www.google-analytics.com; "
                . "font-src 'self'; "
                . "connect-src 'self' https://backend.neogiga.com https://pk.neogiga.com https://np.neogiga.com https://in.neogiga.com; "
                . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'; "
                . "manifest-src 'self'; "
                . "upgrade-insecure-requests"
            );
        }

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
