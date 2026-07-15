<?php

namespace App\Services\Catalog;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\ProductBrand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class BrandVisibilityService
{
    private const CACHE_VERSION = 'v2';

    /** @return Collection<int, ProductBrand> */
    public function visibleFor(?Marketplace $marketplace, bool $menuOnly = false, ?string $locale = null, ?string $host = null): Collection
    {
        $locale ??= app()->getLocale() ?: 'en';
        $host ??= request()->getHost();
        $version = (string) Cache::get('catalog:brand-version', '1');
        $identity = sha1(implode('|', [self::CACHE_VERSION, $host, $locale, $marketplace?->id ?: 'global', $menuOnly ? 'menu' : 'directory', $this->dataFingerprint()]));

        return Cache::remember("catalog:brands:{$version}:{$identity}", now()->addMinutes(10), function () use ($marketplace, $menuOnly) {
            return ProductBrand::query()
                ->with(['manufacturer', 'country'])
                ->withCount(['products as public_products_count' => fn ($query) => $query->published()])
                ->where('is_active', true)
                ->when(Schema::hasColumn('product_brands', 'landing_page_enabled'), fn ($query) => $query->where('landing_page_enabled', true))
                ->when($menuOnly && Schema::hasColumn('product_brands', 'is_menu_visible'), fn ($query) => $query->where('is_menu_visible', true))
                ->when(Schema::hasColumn('product_brands', 'publication_starts_at'), fn ($query) => $query->where(fn ($publication) => $publication->whereNull('publication_starts_at')->orWhere('publication_starts_at', '<=', now())))
                ->when(Schema::hasColumn('product_brands', 'publication_ends_at'), fn ($query) => $query->where(fn ($publication) => $publication->whereNull('publication_ends_at')->orWhere('publication_ends_at', '>=', now())))
                ->when($marketplace?->country_id, fn ($query, $countryId) => $query->where(fn ($inner) => $inner->whereNull('country_id')->orWhere('country_id', $countryId)))
                ->when(Schema::hasColumn('product_brands', 'hide_when_unavailable'), fn ($query) => $query->where(function ($availability) {
                    $availability->where('hide_when_unavailable', false)
                        ->orWhereHas('products', fn ($products) => $products->published());
                }))
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->filter(fn (ProductBrand $brand) => $this->matches($brand->marketplace_visibility, $marketplace?->id, $marketplace?->code)
                    && $this->matches($brand->country_visibility, $marketplace?->country_id, $marketplace?->country?->iso_code_2))
                ->values();
        });
    }

    public function findVisible(string $slug, ?Marketplace $marketplace, ?string $locale = null, ?string $host = null): ?ProductBrand
    {
        $locale ??= app()->getLocale() ?: 'en';
        $host ??= request()->getHost();
        $version = (string) Cache::get('catalog:brand-version', '1');
        $key = 'catalog:brand:'.$version.':'.sha1(implode('|', [self::CACHE_VERSION, $host, $locale, $marketplace?->id ?: 'global', strtolower($slug), $this->dataFingerprint()]));

        return Cache::remember($key, now()->addMinutes(10), fn () => $this->visibleFor($marketplace, false, $locale, $host)->firstWhere('slug', $slug));
    }

    public function clear(): void
    {
        Cache::forever('catalog:brand-version', (string) now()->getTimestampMs());
        Cache::forever('seo:sitemap-version', (string) now()->getTimestampMs());
    }

    private function matches(mixed $visibility, ?int $id, ?string $code): bool
    {
        $visibility = is_string($visibility) ? json_decode($visibility, true) : $visibility;
        if (! is_array($visibility) || $visibility === []) {
            return true;
        }

        $values = $visibility['ids'] ?? $visibility['marketplace_ids'] ?? $visibility['country_ids'] ?? $visibility['codes'] ?? $visibility;
        $values = is_array($values) ? $values : [$values];
        $normalized = collect($values)->map(fn ($value) => strtolower(trim((string) $value)))->filter();

        return ($id !== null && $normalized->contains((string) $id))
            || ($code !== null && $normalized->contains(strtolower($code)));
    }

    private function dataFingerprint(): string
    {
        if (! Schema::hasTable('product_brands')) {
            return 'unprovisioned';
        }

        $state = ProductBrand::query()->selectRaw('count(*) as aggregate_count, max(id) as max_id, max(updated_at) as latest_update')->first();

        return implode(':', [(int) ($state?->aggregate_count ?? 0), (int) ($state?->max_id ?? 0), (string) ($state?->latest_update ?? '')]);
    }
}
