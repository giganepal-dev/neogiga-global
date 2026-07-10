<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;

/**
 * Builds a canonical URL for a marketplace: prefers an active branded domain
 * (the 3 existing domain-based marketplaces) and falls back to a path-prefix
 * URL on the global domain (the 22 new path-based marketplaces).
 */
class MarketplaceUrlGenerator
{
    public function forMarketplace(Marketplace $marketplace, string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        $path = $path === '/' ? '' : $path;

        $domain = $marketplace->relationLoaded('domains')
            ? $marketplace->domains->firstWhere('is_active', true)?->domain
            : $marketplace->domains()->where('is_active', true)->orderByDesc('is_primary')->value('domain');

        if ($domain) {
            return 'https://' . $domain . $path;
        }

        if ($marketplace->url_prefix) {
            return 'https://neogiga.com/' . $marketplace->url_prefix . $path;
        }

        return 'https://neogiga.com' . $path;
    }
}
