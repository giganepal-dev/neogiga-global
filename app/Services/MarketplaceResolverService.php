<?php

namespace App\Services;

use App\Models\Marketplace\Marketplace;
use Illuminate\Support\Facades\Cache;

class MarketplaceResolverService
{
    /**
     * Resolve the current marketplace based on the request host.
     * Falls back to the global marketplace if no specific domain is found.
     *
     * @param string|null $host
     * @return Marketplace
     */
    public function resolve(?string $host = null): Marketplace
    {
        if (!$host) {
            return $this->getGlobalMarketplace();
        }

        // Clean host (remove port if present)
        $host = parse_url($host, PHP_URL_HOST) ?: $host;

        return Cache::remember("marketplace:domain:{$host}", 3600, function () use ($host) {
            $marketplace = Marketplace::whereHas('domains', function ($query) use ($host) {
                $query->where('domain', $host);
            })
            ->with(['country', 'currency'])
            ->first();

            return $marketplace ?? $this->getGlobalMarketplace();
        });
    }

    /**
     * Get the global master marketplace.
     *
     * @return Marketplace
     */
    protected function getGlobalMarketplace(): Marketplace
    {
        return Cache::remember('marketplace:global', 3600, function () {
            return Marketplace::where('code', 'global')->firstOrFail();
        });
    }

    /**
     * Clear cache for a specific domain.
     *
     * @param string $domain
     * @return void
     */
    public function clearCache(string $domain): void
    {
        Cache::forget("marketplace:domain:{$domain}");
    }
}
