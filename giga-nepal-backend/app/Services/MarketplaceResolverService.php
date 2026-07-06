<?php

namespace App\Services;

use App\Models\Marketplace\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves the current marketplace from the request host.
 *
 * Domain strategy (Blueprint §17): neogiga.com = global master,
 * neogiga.in = India, giganepal.com = Nepal. No forced geo-redirects —
 * resolution only selects catalog/pricing scope for the response.
 */
class MarketplaceResolverService
{
    protected ?Marketplace $currentMarketplace = null;

    public function resolve(Request $request): ?Marketplace
    {
        if ($this->currentMarketplace) {
            return $this->currentMarketplace;
        }

        $marketplace = $this->getByDomain($request->getHost())
            ?? $this->getGlobalMarketplace();

        $this->currentMarketplace = $marketplace;

        return $marketplace;
    }

    public function getCurrent(): ?Marketplace
    {
        return $this->currentMarketplace;
    }

    public function setCurrent(Marketplace $marketplace): void
    {
        $this->currentMarketplace = $marketplace;
    }

    public function getByDomain(string $domain): ?Marketplace
    {
        // Strip port if present ("localhost:8000" → "localhost").
        $domain = strtolower(parse_url('//' . $domain, PHP_URL_HOST) ?: $domain);

        return Cache::remember("marketplace:domain:{$domain}", 3600, function () use ($domain) {
            return Marketplace::whereHas('domains', function ($query) use ($domain) {
                $query->where('domain', $domain)
                    ->where('is_active', true);
            })
                ->where('is_active', true)
                ->first();
        });
    }

    public function getGlobalMarketplace(): ?Marketplace
    {
        return Cache::remember('marketplace:global', 3600, function () {
            return Marketplace::where('code', 'global')
                ->where('is_active', true)
                ->first();
        });
    }

    public function clearCache(string $domain): void
    {
        Cache::forget('marketplace:domain:' . strtolower($domain));
        Cache::forget('marketplace:global');
    }
}
