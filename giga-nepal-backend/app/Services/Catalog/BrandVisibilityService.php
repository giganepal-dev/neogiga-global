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
                ->reject(fn (ProductBrand $brand) => $this->looksInvalid((string) $brand->name))
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

    /**
     * True when a brand name is really imported junk — an MPN, SKU, order/package
     * code, numeric-only value or parenthesized fragment — that must never appear
     * publicly as a manufacturer (it stays in the DB for product FK integrity and
     * an admin review queue). Deliberately conservative: real brands like "3M",
     * "M5Stack" or "TE Connectivity" pass. Examples that must fail: 3-794619-4,
     * 01550900DR, 2311766-1, (PUYA).
     */
    public function looksInvalid(string $name): bool
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) < 2) {
            return true;
        }
        if (preg_match('/^\(.*\)$/', $name)) {
            return true; // parenthesized fragment e.g. (PUYA)
        }
        if (! preg_match('/\p{L}/u', $name)) {
            return true; // numeric-only or symbols-only
        }
        if (preg_match('/^\d[\d\-]{3,}$/', $name)) {
            return true; // dash-separated digit codes e.g. 3-794619-4, 2311766-1
        }
        if (preg_match('/^\d{5,}[A-Za-z]{0,3}$/', $name)) {
            return true; // long digit run + short suffix e.g. 01550900DR
        }

        $letters = preg_match_all('/\p{L}/u', $name);
        $digits = preg_match_all('/\d/', $name);
        // Short, digit-dominant alphanumeric codes (MPN-like) with few letters.
        if ($digits > 0 && $letters > 0 && $digits >= $letters * 2 && mb_strlen($name) <= 12) {
            return true;
        }

        return false;
    }

    /**
     * Canonical grouping key for duplicate-brand detection ("SparkFun" ==
     * "SparkFun Electronics"). Shared by the public directory display and the
     * brand:audit report so they always agree.
     */
    public function normalizeName(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = preg_replace('/\b(electronics?|incorporated|inc|corporation|corp|company|co|technologies|technology|semiconductors?|international|limited|ltd|llc|gmbh|group)\b/u', '', $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/u', '', (string) $normalized);

        return $normalized !== '' ? $normalized : mb_strtolower(trim($name));
    }

    private function matches(mixed $visibility, ?int $id, ?string $code): bool
    {
        $visibility = is_string($visibility) ? json_decode($visibility, true) : $visibility;
        if (! is_array($visibility) || $visibility === []) {
            return true; // No visibility restriction → visible everywhere
        }

        // "global": true means visible to all marketplaces unless explicitly excluded
        $hasGlobal = ! empty($visibility['global']);

        // Check boolean-based visibility: {"global": true, "nepal": false}
        // The keys are marketplace codes/ids; truthy = allowed, falsy = excluded
        $codeKey = $code ? strtolower($code) : null;
        $idKey = $id ? (string) $id : null;

        foreach ($visibility as $key => $value) {
            $keyLower = strtolower(trim((string) $key));
            if (($codeKey && $keyLower === $codeKey) || ($idKey && $keyLower === $idKey)) {
                return (bool) $value; // Explicit allow/deny
            }
        }

        // Not explicitly listed → visible if global is true, hidden otherwise
        return $hasGlobal;
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
