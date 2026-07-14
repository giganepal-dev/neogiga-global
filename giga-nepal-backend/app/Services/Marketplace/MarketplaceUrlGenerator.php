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

        // SEO URLs must use the configured canonical host. Active domain rows
        // may include aliases such as np.neogiga.com while canonical_domain is
        // the branded production host (for example giganepal.com).
        $domain = $marketplace->canonical_domain ?: $marketplace->domain;

        if (! $domain) {
            if ($marketplace->relationLoaded('domains')) {
                $activeDomains = $marketplace->domains->where('is_active', true);
                $domain = $activeDomains->firstWhere('is_primary', true)?->domain
                    ?: $activeDomains->first()?->domain;
            } else {
                $domain = $marketplace->domains()->where('is_active', true)->orderByDesc('is_primary')->value('domain');
            }
        }

        if ($domain) {
            $domain = preg_replace('#^https?://#i', '', trim((string) $domain));

            return 'https://' . rtrim((string) $domain, '/') . $path;
        }

        if ($marketplace->url_prefix) {
            return 'https://neogiga.com/' . $marketplace->url_prefix . $path;
        }

        return 'https://neogiga.com' . $path;
    }
}
