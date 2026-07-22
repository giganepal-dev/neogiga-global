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

    private const DEFAULT_DESCRIPTION = 'Discover electronics, semiconductors, IoT, robotics and industrial components on NeoGiga. Browse verified products or request bulk quotes.';

    public function __construct(
        private readonly MarketplaceSeoService $seo,
        private readonly MarketplaceUrlGenerator $urls,
    ) {
    }

    /**
     * @return array{title:string,description:string,canonical:string,robots:string,og_title:string,og_description:string,og_image:?string,twitter_title:string,twitter_description:string,twitter_image:?string,schema_json:?string,favicon:?string}
     */
    public function tags(?Marketplace $marketplace, string $currentUrl): array
    {
        $title = $marketplace?->seo_title ?: self::DEFAULT_TITLE;
        $description = $marketplace?->seo_description ?: self::DEFAULT_DESCRIPTION;
        $canonical = $this->canonicalUrl($marketplace, $currentUrl);

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

    /**
     * Treat marketplace SEO canonical values as a canonical host, not a
     * page-level URL. Appending the current clean path prevents every catalog
     * page from collapsing to the marketplace homepage.
     */
    private function canonicalUrl(?Marketplace $marketplace, string $currentUrl): string
    {
        $path = parse_url($currentUrl, PHP_URL_PATH) ?: '/';
        $path = '/' . ltrim($path, '/');

        if (! $marketplace) {
            $scheme = parse_url($currentUrl, PHP_URL_SCHEME) ?: 'https';
            $host = parse_url($currentUrl, PHP_URL_HOST);

            return $host ? $scheme . '://' . $host . ($path === '/' ? '/' : $path) : $currentUrl;
        }

        if ($this->hasDedicatedRegionalOrigin($marketplace)) {
            $path = $this->canonicalRegionalPath($path);
        }

        $overrideHost = $this->urls->canonicalHostOverride($marketplace);
        $configured = trim((string) ($marketplace->seo_canonical_url ?: ''));
        if ($overrideHost) {
            $origin = 'https://'.$overrideHost;
        } elseif ($configured !== '' && parse_url($configured, PHP_URL_HOST)) {
            $scheme = parse_url($configured, PHP_URL_SCHEME) ?: 'https';
            $origin = $scheme . '://' . parse_url($configured, PHP_URL_HOST);
        } else {
            $generated = $this->urls->forMarketplace($marketplace);
            $scheme = parse_url($generated, PHP_URL_SCHEME) ?: 'https';
            $origin = $scheme . '://' . parse_url($generated, PHP_URL_HOST);
        }

        return rtrim($origin, '/') . ($path === '/' ? '/' : $path);
    }

    private function hasDedicatedRegionalOrigin(Marketplace $marketplace): bool
    {
        if (strtoupper((string) $marketplace->code) === 'GLOBAL') {
            return false;
        }

        if ($marketplace->canonical_domain || $marketplace->domain || $marketplace->generated_domain) {
            return true;
        }

        $configuredHost = strtolower((string) parse_url((string) $marketplace->seo_canonical_url, PHP_URL_HOST));

        return $configuredHost !== '' && ! in_array($configuredHost, ['neogiga.com', 'www.neogiga.com'], true);
    }

    private function canonicalRegionalPath(string $path): string
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn (string $segment): bool => $segment !== ''));
        if ($segments === []) {
            return '/';
        }

        $requestedPrefix = strtolower($segments[0]);
        $defaultPrefix = strtolower(trim((string) config('neogiga_global.default_prefix', 'en'), '/')) ?: 'en';
        $knownPrefixes = array_map('strtolower', array_keys(config('neogiga_global.prefixes', [])));
        if ($requestedPrefix !== $defaultPrefix && in_array($requestedPrefix, $knownPrefixes, true)) {
            $segments[0] = $defaultPrefix;
        }

        return '/'.implode('/', $segments);
    }
}
