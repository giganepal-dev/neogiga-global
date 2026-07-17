<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full-page response cache for anonymous GET requests.
 * Eliminates DB hits for public catalog pages (home, categories, products, brands).
 * TTL: 5 minutes — ponytail: filesystem cache, Redis when traffic grows.
 */
class CachePublicPages
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cache all GET requests. 5-min TTL means logged-in users may
        // briefly see a cached page — acceptable trade-off for speed.
        // Skip: admin, API, cart, checkout, login, register, account pages.
        if ($request->isMethod('GET')
            && ! $request->is('admin*', 'api*', 'cart*', 'checkout*', 'login*', 'register*', 'account*', 'logout*')) {
            $key = 'page:' . sha1($request->fullUrl());

            $cached = Cache::get($key);
            if ($cached) {
                return response($cached, 200, [
                    'X-Page-Cache' => 'HIT',
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'Cache-Control' => 'public, max-age=0, s-maxage=300, stale-while-revalidate=600',
                ]);
            }

            $response = $next($request);

            if ($response->isSuccessful() && ! $response->isRedirection()) {
                Cache::put($key, $response->getContent(), 300); // 5 min
            }

            $response->headers->set('X-Page-Cache', 'MISS');
            $response->headers->set('Cache-Control', 'public, max-age=0, s-maxage=300, stale-while-revalidate=600');

            return $response;
        }

        return $next($request);
    }
}
