<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves a marketplace from the leading URL path segment (e.g. /in, /np).
 * Only matches ACTIVE marketplaces — preview marketplaces are informational
 * (see the /{prefix} landing route) but never become the governing "current"
 * marketplace context for catalog/pricing until launch_status flips to active.
 */
class MarketplacePathResolver
{
    public function resolve(Request $request): ?Marketplace
    {
        $segment = strtolower((string) $request->segment(1));

        if ($segment === '' || ! preg_match('/^[a-z]{2,8}$/', $segment)) {
            return null;
        }

        return $this->byPrefix($segment, activeOnly: true);
    }

    public function byPrefix(string $prefix, bool $activeOnly = false): ?Marketplace
    {
        $prefix = strtolower($prefix);
        $cacheKey = "marketplace:prefix:{$prefix}:" . ($activeOnly ? 'active' : 'any');

        return Cache::remember($cacheKey, 3600, function () use ($prefix, $activeOnly) {
            return Marketplace::query()
                ->with(['country', 'currency', 'domains'])
                ->where('url_prefix', $prefix)
                ->when($activeOnly, fn ($query) => $query->where('is_active', true))
                ->first();
        });
    }

    public function clearCache(string $prefix): void
    {
        Cache::forget("marketplace:prefix:{$prefix}:active");
        Cache::forget("marketplace:prefix:{$prefix}:any");
    }
}
