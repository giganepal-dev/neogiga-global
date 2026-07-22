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
            $key = 'page:'.$version.':'.sha1($request->getHost().$request->fullUrl());

            $cached = Cache::get($key);
            if ($cached) {
                if ($this->containsSessionToken((string) $cached)) {
                    Cache::forget($key);
                } else {
                    return response($cached, 200, [
                        'X-Page-Cache' => 'HIT',
                        'Content-Type' => 'text/html; charset=UTF-8',
                        'Cache-Control' => 'public, max-age=0, s-maxage=300, stale-while-revalidate=600',
                    ]);
                }
            }

            // Crawlers never trigger UNCACHED faceted-listing renders — the
            // facet count queries behind them are this box's documented DB
            // storm (2026-07-19 incident: ~95% bot traffic, load 64), and
            // filtered listing URLs are noindex anyway. Cached copies above
            // still serve bots normally; clean URLs stay fully crawlable.
            if ($request->getQueryString()
                && $request->is('*products*', '*categories*', '*brands*', '*compare*', '*search*')
                && preg_match('/bot|crawl|spider|slurp/i', (string) $request->userAgent())) {
                return response('Service busy — retry later.', 503, ['Retry-After' => '3600']);
            }

            $response = $next($request);

            $content = (string) $response->getContent();
            $cacheable = $response->isSuccessful()
                && ! $response->isRedirection()
                && ! $this->containsSessionToken($content);

            if ($cacheable) {
                Cache::put($key, $content, 300); // 5 min
                $response->headers->set('X-Page-Cache', 'MISS');
                $response->headers->set('Cache-Control', 'public, max-age=0, s-maxage=300, stale-while-revalidate=600');
            } else {
                // Forms and redirects remain private even when their route was
                // not anticipated by the path denylist. This protects every
                // partner login and CSRF-backed workflow from shared caching.
                $response->headers->set('X-Page-Cache', 'BYPASS');
                $response->headers->set('Cache-Control', 'no-cache, private');
            }

            return $response;
        }

        return $next($request);
    }

    private function containsSessionToken(string $content): bool
    {
        return str_contains($content, 'name="_token"') || str_contains($content, 'csrf-token');
    }
}
