<?php

namespace App\Http\Middleware;

use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\MarketplaceContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceMarketplaceRecommendationRedirect
{
    public function __construct(
        private readonly MarketplaceContextResolver $resolver,
        private readonly GlobalMarketplaceContextService $context,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('neogiga_global.features.geo_recommendation_redirect', false) || ! $this->isEligible($request)) {
            return $next($request);
        }

        $current = $this->resolver->resolve($request) ?: $this->resolver->fallback();
        $recommended = $this->resolver->recommended($request, $current);

        if (! $current || ! $recommended || (int) $current->id === (int) $recommended->id) {
            return $next($request);
        }

        $edition = $this->context->editions()->firstWhere('id', $recommended->id);
        if (! $edition) {
            return $next($request);
        }

        $target = $this->targetUrl($request, $edition);
        if (! $target || rtrim($target, '/') === rtrim($request->fullUrl(), '/')) {
            return $next($request);
        }

        return redirect()->away($target, 302);
    }

    private function isEligible(Request $request): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        if ($request->cookie(GlobalMarketplaceContextService::PREFERENCE_COOKIE)
            || $request->cookie(GlobalMarketplaceContextService::SEEN_COOKIE)) {
            return false;
        }

        if ($this->resolver->recommended($request, null) === null) {
            return false;
        }

        $path = '/' . ltrim($request->path(), '/');
        $path = $path === '/.' ? '/' : $path;

        foreach ($this->excludedPrefixes() as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return false;
            }
        }

        return ! preg_match('/(^|\/)(sitemap|robots\.txt|llms\.txt|favicon\.ico)/i', $path);
    }

    private function targetUrl(Request $request, array $edition): ?string
    {
        $path = $this->pathWithoutMarketplacePrefix('/' . ltrim($request->path(), '/'));
        $query = $request->getQueryString();
        $pathWithQuery = $path . ($query ? '?' . $query : '');

        if (! empty($edition['domain'])) {
            // Dedicated regional domains currently run independent storefronts;
            // do not force users into path-for-path URLs that may not exist there.
            return 'https://' . $edition['domain'] . '/' . ($query ? '?' . $query : '');
        }

        $prefix = trim((string) ($edition['url_prefix'] ?: config('neogiga_global.default_prefix', 'en')), '/');
        if ($prefix === '') {
            return $request->getSchemeAndHttpHost() . $pathWithQuery;
        }

        return $request->getSchemeAndHttpHost() . '/' . $prefix . ($path === '/' ? '' : $path) . ($query ? '?' . $query : '');
    }

    private function pathWithoutMarketplacePrefix(string $path): string
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn ($segment) => $segment !== ''));
        $knownPrefixes = array_keys(config('neogiga_global.prefixes', []));

        if ($segments && in_array(strtolower($segments[0]), $knownPrefixes, true)) {
            array_shift($segments);
        }

        return $segments ? '/' . implode('/', $segments) : '/';
    }

    private function excludedPrefixes(): array
    {
        return [
            '/admin',
            '/api',
            '/backend',
            '/cart',
            '/checkout',
            '/health',
            '/marketplace/preference',
            '/storage',
            '/up',
        ];
    }
}
