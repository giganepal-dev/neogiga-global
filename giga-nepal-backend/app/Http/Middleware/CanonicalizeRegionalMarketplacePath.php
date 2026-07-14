<?php

namespace App\Http\Middleware;

use App\Services\Marketplace\DomainMarketplaceResolver;
use App\Services\Marketplace\MarketplaceUrlGenerator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep each dedicated regional host on one locale-prefixed URL tree.
 *
 * Marketplace prefixes remain valid on the global host, but a regional host
 * must not publish the same page below /np, /in, /bd, and every other edition
 * prefix. Those aliases permanently converge on the regional host's /en URL.
 */
class CanonicalizeRegionalMarketplacePath
{
    public function __construct(
        private readonly DomainMarketplaceResolver $domains,
        private readonly MarketplaceUrlGenerator $urls,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $marketplace = $this->domains->resolve($request);
        if (! $marketplace || strtoupper((string) $marketplace->code) === 'GLOBAL') {
            return $next($request);
        }

        $requestedPrefix = strtolower((string) $request->segment(1));
        $defaultPrefix = strtolower(trim((string) config('neogiga_global.default_prefix', 'en'), '/')) ?: 'en';

        if ($requestedPrefix === $defaultPrefix) {
            return $next($request);
        }

        $segments = array_values(array_filter(explode('/', trim($request->path(), '/')), static fn (string $segment): bool => $segment !== ''));
        $segments[0] = $defaultPrefix;
        $canonicalPath = '/'.implode('/', $segments);
        $target = $this->urls->forMarketplace($marketplace, $canonicalPath);

        if ($query = $request->getQueryString()) {
            $target .= '?'.$query;
        }

        return redirect()->away($target, 301);
    }
}
