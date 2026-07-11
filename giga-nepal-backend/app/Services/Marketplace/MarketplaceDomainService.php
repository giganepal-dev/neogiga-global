<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceDomain;

/**
 * Country-domain generation + hostname safety (see MARKETPLACE_DOMAIN_SEO_AUDIT.md).
 *
 * This service only SUGGESTS and SANITIZES — it never auto-saves a generated
 * domain over an existing custom domain, and a generated domain never implies
 * DNS/SSL is configured (ssl_status stays 'pending' until real verification).
 */
class MarketplaceDomainService
{
    private const ROOT = 'neogiga.com';

    /**
     * ISO alpha-2 → generated subdomain suggestion. GLOBAL stays on the root.
     * Returns null when there is no ISO code to build from. Never persists.
     */
    public function suggestGeneratedDomain(Marketplace $marketplace): ?string
    {
        if (strtoupper((string) $marketplace->code) === 'GLOBAL') {
            return self::ROOT;
        }

        $iso2 = strtolower((string) ($marketplace->country_iso2 ?: $marketplace->country?->iso_code_2 ?: ''));
        if ($iso2 === '' || ! preg_match('/^[a-z]{2}$/', $iso2)) {
            return null;
        }

        return "{$iso2}." . self::ROOT;
    }

    /**
     * Normalize a hostname: lowercase, strip scheme/path/port/whitespace.
     * Returns null if the input cannot yield a valid hostname.
     */
    public function sanitizeHostname(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $host = trim(strtolower($raw));
        $host = preg_replace('#^[a-z]+://#', '', $host); // strip scheme
        $host = explode('/', $host)[0];                  // strip path
        $host = explode('?', $host)[0];
        $host = explode('#', $host)[0];
        $host = preg_replace('/:\d+$/', '', $host);      // strip port
        $host = trim($host);

        return $this->isValidHostname($host) ? $host : null;
    }

    /**
     * Production-safe hostname check: rejects spaces, wildcards, localhost, raw
     * IPs, malformed labels, and anything not a dotted DNS name.
     */
    public function isValidHostname(string $host): bool
    {
        if ($host === '' || str_contains($host, ' ') || str_contains($host, '*')) {
            return false;
        }
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }
        // reject IPv4/IPv6 literals
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }
        if (! str_contains($host, '.')) {
            return false; // must be a dotted domain
        }
        // valid DNS labels: letters/digits/hyphen, no leading/trailing hyphen
        return (bool) preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $host);
    }

    /**
     * Is this hostname already used by another marketplace (row domain/generated
     * or marketplace_domains)? excludeId lets a marketplace keep its own domain.
     */
    public function isDuplicateDomain(string $host, ?int $excludeMarketplaceId = null): bool
    {
        $host = strtolower($host);

        $inRows = Marketplace::query()
            ->when($excludeMarketplaceId, fn ($q) => $q->where('id', '!=', $excludeMarketplaceId))
            ->where(fn ($q) => $q->where('domain', $host)->orWhere('generated_domain', $host)->orWhere('canonical_domain', $host))
            ->exists();

        $inDomains = MarketplaceDomain::query()
            ->when($excludeMarketplaceId, fn ($q) => $q->where('marketplace_id', '!=', $excludeMarketplaceId))
            ->whereRaw('LOWER(domain) = ?', [$host])
            ->exists();

        return $inRows || $inDomains;
    }

    /**
     * Fill generated_domain for a marketplace WITHOUT touching an existing
     * custom domain. Returns the value set (or the preserved custom one).
     * Never activates the marketplace; never marks the domain verified.
     */
    public function backfillGeneratedDomain(Marketplace $marketplace): ?string
    {
        $suggestion = $this->suggestGeneratedDomain($marketplace);
        if ($suggestion === null) {
            return $marketplace->generated_domain;
        }

        // Only set if empty or unchanged — never clobber a custom/locked domain.
        if ($marketplace->is_domain_locked) {
            return $marketplace->generated_domain;
        }
        if (empty($marketplace->generated_domain)) {
            $marketplace->generated_domain = $suggestion;
            $marketplace->save();
        }

        return $marketplace->generated_domain;
    }
}
