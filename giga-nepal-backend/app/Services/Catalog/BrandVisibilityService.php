<?php

namespace App\Services\Catalog;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BrandVisibilityService
{
    private const PUBLIC_PRODUCT_STATUSES = ['active', 'approved', 'published'];

    /** @return Collection<int, ProductBrand> */
    public function visibleFor(?Marketplace $marketplace, bool $menuOnly = true, bool $mobile = false, ?string $placement = null): Collection
    {
        $version = (string) Cache::get('catalog:brand-menu-version', '1');
        $key = implode(':', ['catalog', 'brands', $version, $marketplace?->id ?: 'global', $menuOnly ? 'menu' : 'catalog', $mobile ? 'mobile' : 'desktop', $placement ?: 'all']);

        return Cache::remember($key, now()->addMinutes(10), function () use ($marketplace, $menuOnly, $mobile, $placement) {
            return ProductBrand::query()
                ->where('is_active', true)
                ->when($menuOnly, fn ($query) => $query->where('is_menu_visible', true))
                ->when($placement, fn ($query, $value) => $query->where('menu_placement', $value))
                ->when($mobile, fn ($query) => $query->where('display_mobile', true), fn ($query) => $query->where('display_desktop', true))
                ->where(fn ($query) => $query->whereNull('publication_starts_at')->orWhere('publication_starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('publication_ends_at')->orWhere('publication_ends_at', '>=', now()))
                ->when($marketplace?->country_id, fn ($query, $countryId) => $query->where(fn ($inner) => $inner->whereNull('country_id')->orWhere('country_id', $countryId)))
                ->where(function ($query) {
                    $query->where('hide_when_unavailable', false)
                        ->orWhereHas('products', fn ($products) => $products->whereIn('status', self::PUBLIC_PRODUCT_STATUSES));
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->filter(fn (ProductBrand $brand) => $this->matchesContext($brand, $marketplace))
                ->values();
        });
    }

    public function clear(): void
    {
        Cache::put('catalog:brand-menu-version', (string) now()->getTimestamp(), now()->addYear());
    }

    private function matchesContext(ProductBrand $brand, ?Marketplace $marketplace): bool
    {
        return $this->matchesVisibility((array) $brand->marketplace_visibility, $marketplace?->id, $marketplace?->code)
            && $this->matchesVisibility((array) $brand->country_visibility, $marketplace?->country_id, $marketplace?->country?->iso_code_2);
    }

    /** @param array<int|string, mixed> $visibility */
    private function matchesVisibility(array $visibility, ?int $id, ?string $code): bool
    {
        if ($visibility === []) {
            return true;
        }

        $values = $visibility['ids'] ?? $visibility['marketplace_ids'] ?? $visibility['country_ids'] ?? $visibility['codes'] ?? $visibility;
        $values = is_array($values) ? $values : [$values];
        if ($values === []) {
            return true;
        }

        $normalized = collect($values)->map(fn ($value) => strtolower(trim((string) $value)))->filter();

        return ($id !== null && $normalized->contains((string) $id))
            || ($code !== null && $normalized->contains(strtolower($code)));
    }
}
