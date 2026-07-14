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
     * 1. A resolved regional domain wins so /en remains a locale path on
     *    regional hosts instead of incorrectly selecting the global edition.
     * 2. URL marketplace prefix (/in, /np, ...) on the global host.
     * 3. Cookie preference.
     * 4. Authenticated user preference (inert until users.marketplace_id
     *    exists — reading an undefined attribute is a safe null in Eloquent)
     * 5. Global domain rule.
     * 6. Global fallback.
     */
    public function resolve(Request $request): ?Marketplace
    {
        $domainMarketplace = $this->domains->resolve($request);

        if ($domainMarketplace && strtoupper((string) $domainMarketplace->code) !== 'GLOBAL') {
            return $domainMarketplace;
        }

        if ($prefixMarketplace = $this->paths->resolve($request)) {
            return $prefixMarketplace;
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
