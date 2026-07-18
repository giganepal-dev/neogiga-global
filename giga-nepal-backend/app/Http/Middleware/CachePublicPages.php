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
        // Skip: admin, API, cart, checkout, login, register, account pages,
        // authenticated users, and non-GET requests.
        if ($request->isMethod('GET')
            && ! auth()->check()
            && ! $request->is('admin*', 'api*', 'cart*', 'checkout*', 'login*', 'register*', 'account*', 'logout*')) {
            // ponytail: sha1(host + fullUrl) prevents cross-marketplace cache collision.
            // Version stamp allows instant invalidation when catalog data changes.
            $version = Cache::get('catalog:page-cache-version', '1');
            $key = 'page:' . $version . ':' . sha1($request->getHost() . $request->fullUrl());

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
