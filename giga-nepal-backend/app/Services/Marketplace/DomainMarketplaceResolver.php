<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DomainMarketplaceResolver
{
    public function resolve(Request $request): ?Marketplace
    {
        return $this->byDomain($request->getHost());
    }

    public function byDomain(string $domain): ?Marketplace
    {
        $domain = strtolower(parse_url('//' . $domain, PHP_URL_HOST) ?: $domain);

        return Cache::remember("marketplace:domain:{$domain}", 3600, function () use ($domain) {
            return Marketplace::query()
                ->with(['country', 'currency', 'domains'])
                ->whereHas('domains', fn ($query) => $query->where('domain', $domain)->where('is_active', true))
                ->where('is_active', true)
                ->first();
        });
    }
}
