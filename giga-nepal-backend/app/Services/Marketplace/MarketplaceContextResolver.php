<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use Illuminate\Http\Request;

class MarketplaceContextResolver
{
    public function __construct(
        private readonly MarketplacePathResolver $paths,
        private readonly MarketplacePreferenceService $preferences,
        private readonly DomainMarketplaceResolver $domains,
        private readonly CountryResolver $countries,
    ) {
    }

    /**
     * Global Commerce Stage 1 resolution order:
     * 1. URL path prefix (/in, /np, ...)
     * 2. active domain/subdomain marketplace rules always win on their own
     *    host; the global root is the only host where a cookie can override
     * 3. cookie preference
     * 4. authenticated user preference (inert until users.marketplace_id
     *    exists — reading an undefined attribute is a safe null in Eloquent)
     * 5. domain rules
     * 6. global fallback
     */
    public function resolve(Request $request): ?Marketplace
    {
        if ($prefixMarketplace = $this->paths->resolve($request)) {
            return $prefixMarketplace;
        }

        $domainMarketplace = $this->domains->resolve($request);
        $host = strtolower(parse_url('//' . $request->getHost(), PHP_URL_HOST) ?: $request->getHost());

        if ($domainMarketplace && ! in_array($host, ['neogiga.com', 'www.neogiga.com'], true)) {
            return $domainMarketplace ?: $this->fallback();
        }

        return $this->preferences->preferredMarketplace($request)
            ?: $this->authenticatedPreference($request)
            ?: $domainMarketplace
            ?: $this->fallback();
    }

    private function authenticatedPreference(Request $request): ?Marketplace
    {
        $user = $request->user();
        $marketplaceId = $user?->getAttribute('marketplace_id');

        if (! $marketplaceId) {
            return null;
        }

        return Marketplace::query()
            ->with(['country', 'currency', 'domains'])
            ->where('id', $marketplaceId)
            ->where('is_active', true)
            ->first();
    }

    public function recommended(Request $request, ?Marketplace $current): ?Marketplace
    {
        if ($this->countries->isCrawler($request)) {
            return $current;
        }

        $code = $this->countries->recommendationCode($request);
        if (! $code) {
            return $current;
        }

        return Marketplace::query()
            ->with(['country', 'currency', 'domains'])
            ->whereRaw('LOWER(code) = ?', [$code])
            ->where('is_active', true)
            ->first() ?: $current;
    }

    public function fallback(): ?Marketplace
    {
        return Marketplace::query()
            ->with(['country', 'currency', 'domains'])
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();
    }
}
