<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;

/**
 * Pre-launch validation checklist (codex §4). Pure, read-only: it never
 * mutates the marketplace. Critical failures block activation; non-critical
 * failures are warnings. A generated-but-unverified domain (ssl_status
 * 'pending', no domain_verified_at) is a CRITICAL failure — a marketplace is
 * never activated on an unverified domain.
 */
class MarketplaceLaunchValidator
{
    public function __construct(private readonly MarketplaceDomainService $domains)
    {
    }

    /**
     * @return array{can_activate:bool, checklist:list<array{key:string,label:string,passed:bool,critical:bool,message:?string}>}
     */
    public function validate(Marketplace $marketplace): array
    {
        $primaryDomain = $marketplace->domain ?: $marketplace->generated_domain;
        $canonical = $marketplace->seo_canonical_url;

        $checks = [
            $this->check('country', 'Country assigned', (bool) $marketplace->country_id, true),
            $this->check('currency', 'Currency assigned', (bool) $marketplace->currency_id, true),
            $this->check('timezone', 'Timezone set', ! empty($marketplace->timezone), true),
            $this->check('language', 'Default language set', ! empty($marketplace->default_language ?: $marketplace->locale), true),
            $this->check('primary_domain', 'Primary domain present', ! empty($primaryDomain), true),
            $this->check(
                'domain_valid',
                'Domain is a valid hostname',
                ! empty($primaryDomain) && $this->domains->isValidHostname((string) $primaryDomain),
                true,
                empty($primaryDomain) ? 'no domain to validate' : null,
            ),
            $this->check(
                'domain_unique',
                'Domain is not used by another marketplace',
                empty($primaryDomain) || ! $this->domains->isDuplicateDomain((string) $primaryDomain, $marketplace->id),
                true,
            ),
            $this->check(
                'domain_verified',
                'Domain/SSL verified for production',
                in_array($marketplace->ssl_status, ['active', 'not_required'], true) || $marketplace->domain_verified_at !== null,
                true,
                'a generated domain is not verified until real DNS/HTTP verification succeeds',
            ),
            $this->check('seo_title', 'SEO title present', ! empty($marketplace->seo_title), true),
            $this->check('seo_description', 'SEO description present', ! empty($marketplace->seo_description), true),
            $this->check(
                'canonical_url',
                'Canonical URL is valid',
                ! empty($canonical) && filter_var($canonical, FILTER_VALIDATE_URL) !== false,
                false, // non-critical: derivable from the domain if missing
            ),
        ];

        $canActivate = collect($checks)->every(fn ($c) => ! $c['critical'] || $c['passed']);

        return ['can_activate' => $canActivate, 'checklist' => $checks];
    }

    /** @return array{key:string,label:string,passed:bool,critical:bool,message:?string} */
    private function check(string $key, string $label, bool $passed, bool $critical, ?string $message = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'passed' => $passed,
            'critical' => $critical,
            'message' => $passed ? null : ($message ?? "failed: {$label}"),
        ];
    }
}
