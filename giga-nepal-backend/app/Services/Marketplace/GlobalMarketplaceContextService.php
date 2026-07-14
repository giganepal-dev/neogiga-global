<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GlobalMarketplaceContextService
{
    public const PREFERENCE_COOKIE = 'ng_marketplace_choice';
    public const SEEN_COOKIE = 'ng_marketplace_recommendation_seen';

    public function __construct(
        private readonly MarketplaceContextResolver $resolver,
        private readonly MarketplaceUrlGenerator $urlGenerator,
    ) {
    }

    public function context(Request $request): array
    {
        $current = $this->resolver->resolve($request) ?: $this->fallbackMarketplace();
        $editions = $this->editions();
        $recommended = $this->marketplaceToEdition($this->resolver->recommended($request, $current), $editions);
        $preference = strtolower((string) $request->cookie(self::PREFERENCE_COOKIE, ''));
        $seen = (bool) $request->cookie(self::SEEN_COOKIE, false);

        return [
            'current' => $current,
            'recommended' => $recommended,
            'editions' => $editions,
            'preference' => $preference,
            'show_recommendation' => $this->shouldShowRecommendation($request, $current, $recommended, $seen),
            'currency_code' => $current?->currency?->code ?? 'USD',
            'country_id' => $current?->country_id,
            'country_code' => strtoupper((string) ($current?->country?->iso_code_2 ?? '')),
            'locale' => $current?->locale ?: 'en',
            'hreflang' => $this->hreflangLinks($request, $editions),
        ];
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
            'is_visible' => (bool) $marketplace->is_visible,
            'indexable' => (bool) $marketplace->indexable,
            'hreflang_enabled' => (bool) ($marketplace->hreflang_enabled ?? true),
            'launch_status' => $marketplace->launch_status ?? 'preview',
            'checkout_enabled' => (bool) $marketplace->checkout_enabled,
        ];
    }

    public function marketplaceForPreference(string $code): ?array
    {
        $normalized = strtolower($code);

        return $this->editions()->firstWhere('code', $normalized);
    }

    private function marketplaceToEdition(?Marketplace $marketplace, Collection $editions): ?array
    {
        return $marketplace ? $editions->firstWhere('id', $marketplace->id) : null;
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
            if (! ($edition['url'] ?? null)
                || ! ($edition['is_visible'] ?? true)
                || ! ($edition['indexable'] ?? true)
                || ! ($edition['hreflang_enabled'] ?? true)) {
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
        return $this->resolver->fallback();
    }
}
