<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;

/**
 * Produces the SEO tag payload for the frontend layout from the resolved
 * marketplace (codex §7). Fail-safe by design:
 *  - With no marketplace (unresolved context) it returns the NeoGiga defaults
 *    and robots "index, follow" — i.e. exactly the pre-existing behavior, so a
 *    page without marketplace context never regresses.
 *  - robots is noindex ONLY for a resolved marketplace that is not
 *    active+visible+indexable. The live marketplaces are aligned to
 *    indexable=true, so neogiga.com/.in/giganepal.com stay index,follow.
 * Every field falls back to a sensible default when the marketplace's seo_*
 * column is empty.
 */
class MarketplaceSeoRenderer
{
    private const DEFAULT_TITLE = 'NeoGiga — Global Engineering Marketplace';

    private const DEFAULT_DESCRIPTION = 'Semiconductors, electronics, IoT, robotics, batteries and engineering tools — sourced globally, delivered regionally.';

    public function __construct(private readonly MarketplaceSeoService $seo)
    {
    }

    /**
     * @return array{title:string,description:string,canonical:string,robots:string,og_title:string,og_description:string,og_image:?string,twitter_title:string,twitter_description:string,twitter_image:?string,schema_json:?string,favicon:?string}
     */
    public function tags(?Marketplace $marketplace, string $currentUrl): array
    {
        $title = $marketplace?->seo_title ?: self::DEFAULT_TITLE;
        $description = $marketplace?->seo_description ?: self::DEFAULT_DESCRIPTION;
        $canonical = $marketplace?->seo_canonical_url ?: $currentUrl;

        // No marketplace resolved => preserve legacy "index, follow".
        $robots = $marketplace ? $this->seo->robotsFor($marketplace) : 'index, follow';

        $schema = null;
        if ($marketplace && ! empty($marketplace->seo_schema_json)) {
            $schema = is_string($marketplace->seo_schema_json)
                ? $marketplace->seo_schema_json
                : json_encode($marketplace->seo_schema_json);
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og_title' => $marketplace?->seo_og_title ?: $title,
            'og_description' => $marketplace?->seo_og_description ?: $description,
            'og_image' => $marketplace?->seo_og_image,
            'twitter_title' => $marketplace?->seo_twitter_title ?: $title,
            'twitter_description' => $marketplace?->seo_twitter_description ?: $description,
            'twitter_image' => $marketplace?->seo_twitter_image,
            'schema_json' => $schema,
            'favicon' => $marketplace?->favicon,
        ];
    }
}
