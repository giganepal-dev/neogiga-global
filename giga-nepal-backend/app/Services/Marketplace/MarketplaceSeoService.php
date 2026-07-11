<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;

/**
 * Marketplace SEO auto-fill (see MARKETPLACE_DOMAIN_SEO_AUDIT.md). Generates
 * editable suggestions from marketplace/country data. NEVER overwrites a field
 * listed in seo_manual_override_fields, and robots follows the marketplace's
 * real visibility/index state so inactive/hidden markets are noindex.
 */
class MarketplaceSeoService
{
    private const BRAND = 'NeoGiga';

    /**
     * Suggested SEO values (does not save). {Country} is filled from the
     * marketplace's country name (falling back to its brand/name).
     */
    public function suggest(Marketplace $marketplace): array
    {
        $country = $this->countryName($marketplace);
        $domain = $this->primaryDomain($marketplace);

        return [
            'seo_title' => "Electronic Components, Robotics & IoT Marketplace in {$country} | " . self::BRAND,
            'seo_description' => "Buy electronic components, semiconductors, sensors, batteries, robotics, IoT products and development boards in {$country} through " . self::BRAND . '.',
            'seo_keywords' => "electronic components, semiconductors, sensors, robotics, IoT, development boards, {$country}",
            'seo_h1' => "Electronic Components and Robotics Marketplace in {$country}",
            'seo_og_title' => self::BRAND . " {$country} – Components, Robotics and IoT Marketplace",
            'seo_og_description' => "Shop electronic components, robotics and IoT products in {$country} on " . self::BRAND . '.',
            'seo_twitter_title' => self::BRAND . " {$country} – Components & Robotics",
            'seo_twitter_description' => "Electronic components, robotics and IoT in {$country}.",
            'seo_canonical_url' => $domain ? "https://{$domain}/" : null,
            'seo_robots' => $this->robotsFor($marketplace),
        ];
    }

    /**
     * Robots directive from real state: only an active, publicly visible,
     * indexable marketplace is index,follow. Everything else is noindex,nofollow.
     */
    public function robotsFor(Marketplace $marketplace): string
    {
        return ($marketplace->is_active && $marketplace->is_visible && $marketplace->indexable)
            ? 'index,follow'
            : 'noindex,nofollow';
    }

    /**
     * Apply suggestions and save. Fields listed in seo_manual_override_fields are
     * never touched. When $onlyEmpty is true, only currently-empty fields are
     * filled (regenerate-empty-only). seo_robots is always recomputed from state.
     *
     * @return list<string> the field names that were written
     */
    public function apply(Marketplace $marketplace, bool $onlyEmpty = false): array
    {
        $manual = (array) ($marketplace->seo_manual_override_fields ?? []);
        $suggested = $this->suggest($marketplace);
        $written = [];

        foreach ($suggested as $field => $value) {
            if ($field === 'seo_robots') {
                // robots always reflects current visibility, unless manually overridden
                if (in_array($field, $manual, true)) {
                    continue;
                }
                $marketplace->seo_robots = $value;
                $written[] = $field;
                continue;
            }

            if (in_array($field, $manual, true)) {
                continue; // respect manual override
            }
            if ($onlyEmpty && ! empty($marketplace->{$field})) {
                continue; // keep existing content
            }
            $marketplace->{$field} = $value;
            $written[] = $field;
        }

        $marketplace->seo_is_auto_generated = true;
        $marketplace->seo_last_generated_at = now();
        $marketplace->save();

        return $written;
    }

    private function countryName(Marketplace $marketplace): string
    {
        return $marketplace->country?->name
            ?: ($marketplace->regional_brand_name ?: $marketplace->name);
    }

    private function primaryDomain(Marketplace $marketplace): ?string
    {
        return $marketplace->domain
            ?: ($marketplace->canonical_domain ?: $marketplace->generated_domain);
    }
}
