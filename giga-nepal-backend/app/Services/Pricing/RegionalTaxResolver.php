<?php

namespace App\Services\Pricing;

use App\Models\Marketplace\Marketplace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves applicable taxes, duties, and tariffs for a product in a
 * given marketplace context. Uses the existing tax_rules, tax_zones,
 * and import_duty_rules tables with priority-based resolution.
 */
class RegionalTaxResolver
{
    private const CACHE_TTL = 3600;

    /** @return array{tax_rate:float, tax_type:string, is_inclusive:bool, duty_rate:float, duty_type:string} */
    public function resolve(
        int $productId,
        ?int $categoryId,
        Marketplace $marketplace,
        ?string $hsCode = null,
        ?string $originCountry = null
    ): array {
        $cacheKey = "tax:resolve:{$productId}:{$marketplace->id}:{$hsCode}:{$originCountry}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($productId, $categoryId, $marketplace, $hsCode, $originCountry) {
            $countryId = $marketplace->country_id;

            // 1. Product-specific tax override
            $productTax = $this->productTaxRule($productId, $marketplace->id, $countryId);
            if ($productTax) return $productTax;

            // 2. Category-based tax
            if ($categoryId) {
                $categoryTax = $this->categoryTaxRule($categoryId, $marketplace->id, $countryId);
                if ($categoryTax) return $categoryTax;
            }

            // 3. Marketplace zone default
            $zoneTax = $this->zoneTax($marketplace->id, $countryId);
            if ($zoneTax) return $zoneTax;

            // 4. Global fallback
            return ['tax_rate' => 0, 'tax_type' => 'none', 'is_inclusive' => false, 'duty_rate' => 0, 'duty_type' => 'none'];
        });
    }

    /** @return array{rate:float, type:string}|null */
    public function importDuty(
        string $hsCode,
        Marketplace $marketplace,
        ?string $originCountry = null,
        ?int $categoryId = null
    ): ?array {
        if (! Schema::hasTable('import_duty_rules')) return null;

        return Cache::remember("duty:{$hsCode}:{$marketplace->id}:{$originCountry}", self::CACHE_TTL, function () use ($hsCode, $marketplace, $originCountry, $categoryId) {
            $rule = DB::table('import_duty_rules')
                ->where('country_id', $marketplace->country_id)
                ->where('is_active', true)
                ->where(function ($q) use ($hsCode, $categoryId) {
                    $q->where('hs_code', $hsCode)
                      ->orWhere(function ($inner) use ($hsCode) {
                          // Match by HS chapter (first 2 digits)
                          $chapter = substr($hsCode, 0, 2);
                          $inner->where('hs_code', 'like', "{$chapter}%");
                      });
                })
                ->when($originCountry, fn ($q) => $q->where('origin_country', $originCountry))
                ->when($categoryId, fn ($q) => $q->orWhereJsonContains('category_ids', (string) $categoryId))
                ->orderByRaw("CASE WHEN hs_code = ? THEN 0 WHEN origin_country = ? THEN 1 ELSE 2 END", [$hsCode, $originCountry])
                ->first();

            if (! $rule) return null;

            return ['rate' => (float) ($rule->duty_rate ?? 0), 'type' => $rule->duty_type ?? 'ad_valorem'];
        });
    }

    /** @return array{tax_rate:float, tax_type:string, is_inclusive:bool, duty_rate:float, duty_type:string}|null */
    private function productTaxRule(int $productId, ?int $marketplaceId, ?int $countryId): ?array
    {
        if (! Schema::hasTable('tax_rules')) return null;

        $rule = DB::table('tax_rules')
            ->where('is_active', true)
            ->where(function ($q) use ($productId, $marketplaceId, $countryId) {
                $q->whereJsonContains('product_ids', (string) $productId)
                  ->where(fn ($inner) => $inner->where('marketplace_id', $marketplaceId)->orWhere('country_id', $countryId));
            })
            ->orderBy('priority', 'desc')
            ->first();

        return $rule ? $this->formatRule($rule) : null;
    }

    /** @return array{tax_rate:float, tax_type:string, is_inclusive:bool, duty_rate:float, duty_type:string}|null */
    private function categoryTaxRule(int $categoryId, ?int $marketplaceId, ?int $countryId): ?array
    {
        if (! Schema::hasTable('tax_rules')) return null;

        $rule = DB::table('tax_rules')
            ->where('is_active', true)
            ->whereJsonContains('category_ids', (string) $categoryId)
            ->where(fn ($q) => $q->where('marketplace_id', $marketplaceId)->orWhere('country_id', $countryId))
            ->orderBy('priority', 'desc')
            ->first();

        return $rule ? $this->formatRule($rule) : null;
    }

    /** @return array{tax_rate:float, tax_type:string, is_inclusive:bool, duty_rate:float, duty_type:string}|null */
    private function zoneTax(?int $marketplaceId, ?int $countryId): ?array
    {
        if (! Schema::hasTable('tax_zones')) return null;

        $zone = DB::table('tax_zones')
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('marketplace_id', $marketplaceId)->orWhere('country_id', $countryId))
            ->orderBy('priority', 'desc')
            ->first();

        if (! $zone) return null;

        return [
            'tax_rate' => (float) ($zone->tax_rate ?? 0),
            'tax_type' => 'vat',
            'is_inclusive' => (bool) ($zone->is_inclusive ?? false),
            'duty_rate' => 0,
            'duty_type' => 'none',
        ];
    }

    private function formatRule(object $rule): array
    {
        return [
            'tax_rate' => (float) ($rule->tax_rate ?? 0),
            'tax_type' => $rule->tax_type ?? 'vat',
            'is_inclusive' => (bool) ($rule->is_inclusive ?? false),
            'duty_rate' => 0,
            'duty_type' => 'none',
        ];
    }

    public function clearCache(int $productId, int $marketplaceId): void
    {
        Cache::forget("tax:resolve:{$productId}:{$marketplaceId}");
    }
}
