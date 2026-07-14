<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;

/**
 * Builds a canonical URL for a marketplace, honoring a temporary live-host
 * override while a separately hosted branded domain awaits migration.
 */
class MarketplaceUrlGenerator
{
    public function forMarketplace(Marketplace $marketplace, string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        $path = $path === '/' ? '' : $path;

        $domain = $this->canonicalHostOverride($marketplace)
            ?: $marketplace->canonical_domain
            ?: $marketplace->domain;

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

    public function canonicalHostOverride(Marketplace $marketplace): ?string
    {
        $overrides = (array) config('neogiga_global.canonical_host_overrides', []);
        $domain = trim((string) ($overrides[strtoupper((string) $marketplace->code)] ?? ''));
        if ($domain === '') {
            return null;
        }

        $domain = strtolower((string) preg_replace('#^https?://#i', '', $domain));
        $domain = trim($domain, '/');

        return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            ? $domain
            : null;
    }
}
