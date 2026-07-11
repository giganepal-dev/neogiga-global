<?php

namespace App\Http\Middleware;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Host-header allow-list guard against host-header spoofing (codex §6).
 *
 * FLAG-GATED OFF by default (config marketplace.host_guard_enabled) and
 * FAIL-OPEN: any internal error, or the flag being off, lets the request
 * through untouched — a bug in this guard can never take the site down. Only
 * when an operator explicitly enables it (after verifying the allow-list) does
 * a request whose normalized Host is not in the data-driven allow-list get a
 * 404. The allow-list is built from every marketplace domain plus the
 * configured hosts, and is cached for 5 minutes.
 */
class EnsureAllowedHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $blocked = false;

        // Decide inside the try so any error fails OPEN. The abort() is
        // deliberately OUTSIDE the try — otherwise its HttpException (a
        // Throwable) would be swallowed and the guard would never block.
        try {
            if (config('marketplace.host_guard_enabled', false)) {
                $host = $this->normalize($request->getHost());
                $blocked = $host !== '' && ! in_array($host, $this->allowList(), true);
            }
        } catch (\Throwable) {
            $blocked = false;
        }

        if ($blocked) {
            abort(404);
        }

        return $next($request);
    }

    private function normalize(?string $host): string
    {
        $host = strtolower(trim((string) $host));

        return (string) preg_replace('/:\d+$/', '', $host); // strip port
    }

    /** @return list<string> */
    private function allowList(): array
    {
        return Cache::remember('marketplace:host-allowlist', 300, function () {
            $hosts = array_merge(
                (array) config('marketplace.always_allow', []),
                (array) config('marketplace.allowed_hosts', []),
            );

            if ($appHost = parse_url((string) config('app.url'), PHP_URL_HOST)) {
                $hosts[] = $appHost;
            }

            if (Schema::hasTable('marketplaces')) {
                foreach (Marketplace::query()->get(['domain', 'generated_domain', 'canonical_domain']) as $m) {
                    foreach (['domain', 'generated_domain', 'canonical_domain'] as $col) {
                        if (! empty($m->{$col})) {
                            $hosts[] = $m->{$col};
                        }
                    }
                }
            }
            if (Schema::hasTable('marketplace_domains')) {
                foreach (MarketplaceDomain::query()->pluck('domain') as $d) {
                    if (! empty($d)) {
                        $hosts[] = $d;
                    }
                }
            }

            // Normalize + add www/non-www variants so both resolve.
            $out = [];
            foreach ($hosts as $h) {
                $h = strtolower(trim((string) $h));
                $h = (string) preg_replace('#^[a-z]+://#', '', $h);
                $h = explode('/', $h)[0];
                $h = (string) preg_replace('/:\d+$/', '', $h);
                if ($h === '') {
                    continue;
                }
                $out[$h] = true;
                $out[str_starts_with($h, 'www.') ? substr($h, 4) : 'www.' . $h] = true;
            }

            return array_keys($out);
        });
    }
}
