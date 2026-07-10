<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use App\Services\MarketplaceResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GlobalMarketplaceContextService
{
    public const PREFERENCE_COOKIE = 'ng_marketplace_choice';
    public const SEEN_COOKIE = 'ng_marketplace_recommendation_seen';

    public function __construct(
        private readonly MarketplaceResolverService $resolver,
        private readonly MarketplacePathResolver $pathResolver,
        private readonly MarketplaceUrlGenerator $urlGenerator,
    ) {
    }

    /**
     * Resolution order (Global Commerce Stage 1):
     * 1. URL path prefix (/in, /np, ...)
     * 2. cookie preference (existing)
     * 3. authenticated user preference (inert no-op until a users.marketplace_id
     *    column exists — reading an undefined attribute is a safe null in Eloquent)
     * 4. domain rules (existing MarketplaceResolverService)
     * 5. GeoIP / edge-country signal (CF-IPCountry header — data-driven across
     *    all active editions, no hardcoded country list; see recommendedMarketplace())
     * 6. global fallback
     * No step in this method redirects; it only selects the resolved context.
     */
    public function context(Request $request): array
    {
        $preference = strtolower((string) $request->cookie(self::PREFERENCE_COOKIE, ''));
        $authPreferenceCode = $this->authenticatedPreference($request);

        $current = $this->pathResolver->resolve($request)
            ?? ($preference !== '' ? $this->marketplaceModelForCode($preference) : null)
            ?? ($authPreferenceCode ? $this->marketplaceModelForCode($authPreferenceCode) : null)
            ?? $this->resolver->resolve($request)
            ?? $this->fallbackMarketplace();

        $editions = $this->editions();
        $recommended = $this->recommendedMarketplace($request, $current, $editions);

        return [
            'current' => $current,
            'recommended' => $recommended,
            'editions' => $editions,
            'preference' => $preference,
            'show_recommendation' => $this->shouldShowRecommendation($request, $current, $recommended, (bool) $request->cookie(self::SEEN_COOKIE, false)),
            'currency_code' => $current?->currency?->code ?? 'USD',
            'country_id' => $current?->country_id,
            'country_code' => strtoupper((string) ($current?->country?->iso_code_2 ?? '')),
            'locale' => $current?->locale ?: 'en',
            'hreflang' => $this->hreflangLinks($request, $editions),
        ];
    }

    /**
     * All 25 marketplaces regardless of launch_status, for the country
     * selector — preview marketplaces are shown as "coming soon", never as a
     * functional storefront (see the /{prefix} landing route).
     */
    public function allEditions(): Collection
    {
        return Cache::remember('marketplace:all-editions', 3600, function () {
            return Marketplace::query()
                ->with(['country', 'currency', 'domains' => fn ($query) => $query->where('is_active', true)->orderByDesc('is_primary')])
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
                ->map(fn (Marketplace $marketplace) => $this->editionPayload($marketplace))
                ->values();
        });
    }

    private function authenticatedPreference(Request $request): ?string
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        // Forward-compatible: returns null today (no users.marketplace_id
        // column exists yet); starts working automatically if one is added.
        $marketplaceId = $user->getAttribute('marketplace_id');

        return $marketplaceId ? Marketplace::find($marketplaceId)?->code : null;
    }

    private function marketplaceModelForCode(string $code): ?Marketplace
    {
        return Marketplace::query()
            ->with(['country', 'currency', 'domains'])
            ->where('code', strtoupper($code))
            ->where('is_active', true)
            ->first();
    }

    public function editions(): Collection
    {
        return Cache::remember('marketplace:public-editions', 3600, function () {
            return Marketplace::query()
                ->with(['country', 'currency', 'domains' => fn ($query) => $query->where('is_active', true)->orderByDesc('is_primary')])
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get()
                ->map(fn (Marketplace $marketplace) => $this->editionPayload($marketplace))
                ->values();
        });
    }

    private function editionPayload(Marketplace $marketplace): array
    {
        $domain = $marketplace->domains->first()?->domain;
        $code = strtolower((string) $marketplace->code);
        $locale = $marketplace->locale ?: 'en';

        return [
            'id' => $marketplace->id,
            'code' => $code,
            'name' => $marketplace->name,
            'regional_brand_name' => $marketplace->regional_brand_name,
            'domain' => $domain,
            'url_prefix' => $marketplace->url_prefix,
            'url' => $this->urlGenerator->forMarketplace($marketplace),
            'locale' => $locale,
            'hreflang' => $this->hreflangFor($code, $locale, $marketplace->country?->iso_code_2),
            'country_id' => $marketplace->country_id,
            'country_code' => strtoupper((string) ($marketplace->country?->iso_code_2 ?? '')),
            'currency_code' => $marketplace->currency?->code ?? 'USD',
            'is_default' => (bool) $marketplace->is_default,
            'is_active' => (bool) $marketplace->is_active,
            'launch_status' => $marketplace->launch_status ?? 'preview',
            'checkout_enabled' => (bool) $marketplace->checkout_enabled,
        ];
    }

    public function marketplaceForPreference(string $code): ?array
    {
        $normalized = strtolower($code);

        return $this->editions()->firstWhere('code', $normalized);
    }

    /**
     * Data-driven country recommendation (no hardcoded country list): matches
     * the CF-IPCountry edge header, then the Accept-Language region subtag,
     * against whichever ACTIVE marketplaces exist for that country. Unsupported
     * countries fall through to the current/default edition — they are never
     * left without a recommendation, but they're also never forced anywhere.
     */
    private function recommendedMarketplace(Request $request, ?Marketplace $current, Collection $editions): ?array
    {
        $byCountry = $editions->filter(fn ($edition) => $edition['country_code'] !== '')
            ->keyBy('country_code');

        $country = strtoupper((string) $request->header('CF-IPCountry', ''));
        $match = $country !== '' ? $byCountry->get($country) : null;

        if (! $match) {
            $acceptLanguage = strtolower((string) $request->header('Accept-Language', ''));
            if (preg_match('/[a-z]{2}-([a-z]{2})/', $acceptLanguage, $regionMatch)) {
                $match = $byCountry->get(strtoupper($regionMatch[1]));
            }
        }

        if (! $match) {
            return $current ? $editions->firstWhere('id', $current->id) : $editions->firstWhere('is_default', true);
        }

        return $match;
    }

    private function shouldShowRecommendation(Request $request, ?Marketplace $current, ?array $recommended, bool $seen): bool
    {
        if ($seen || ! $current || ! $recommended || (int) $recommended['id'] === (int) $current->id) {
            return false;
        }

        $path = '/' . ltrim($request->path(), '/');

        return ! str_starts_with($path, '/admin')
            && ! str_starts_with($path, '/api')
            && ! str_starts_with($path, '/health');
    }

    private function hreflangLinks(Request $request, Collection $editions): array
    {
        $path = '/' . ltrim($request->path(), '/');
        $path = $path === '/.' ? '/' : $path;

        $links = [[
            'hreflang' => 'x-default',
            'url' => 'https://neogiga.com' . ($path === '/' ? '' : $path),
        ]];

        foreach ($editions as $edition) {
            if (! $edition['url']) {
                continue;
            }

            $links[] = [
                'hreflang' => strtolower($edition['hreflang']),
                'url' => rtrim($edition['url'], '/') . ($path === '/' ? '' : $path),
            ];
        }

        return $links;
    }

    private function hreflangFor(string $code, string $locale, ?string $country): string
    {
        if ($code === 'global') {
            return 'en';
        }

        $language = strtolower(strtok($locale, '-')) ?: 'en';
        $country = strtoupper((string) $country);

        return $country ? "{$language}-{$country}" : $language;
    }

    private function fallbackMarketplace(): ?Marketplace
    {
        return Marketplace::query()
            ->with(['country', 'currency', 'domains'])
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();
    }
}
