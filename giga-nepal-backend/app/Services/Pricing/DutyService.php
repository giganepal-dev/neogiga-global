<?php

namespace App\Services\Pricing;

use App\Models\Marketplace\Marketplace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Import-duty lookup against the existing import_duty_rules table (see
 * TAX_AND_DUTY_ENGINE_GUIDE.md). Percentage rules only in this version;
 * returns 0 whenever no active rule matches, so duty is inert until an
 * operator enters real rules — never invented in code.
 */
class DutyService
{
    /**
     * Duty percent for a product's HS code shipped into a marketplace's
     * country. Most specific active rule wins: an exact hs_code match beats
     * a catch-all (null hs_code) country rule; a marketplace-scoped rule
     * beats a country-only rule. Returns 0.0 when nothing matches.
     */
    public function dutyPercent(?string $hsCode, Marketplace $marketplace): float
    {
        if (! Schema::hasTable('import_duty_rules') || ! $marketplace->country_id) {
            return 0.0;
        }

        $today = now()->toDateString();

        $rule = DB::table('import_duty_rules')
            ->where('is_active', true)
            ->where('duty_type', 'percentage')
            ->where('country_id', $marketplace->country_id)
            ->where(function ($q) use ($marketplace) {
                $q->whereNull('marketplace_id')->orWhere('marketplace_id', $marketplace->id);
            })
            ->where(function ($q) use ($hsCode) {
                $q->whereNull('hs_code');
                if ($hsCode !== null && $hsCode !== '') {
                    $q->orWhere('hs_code', $hsCode);
                }
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_until')->orWhere('effective_until', '>=', $today);
            })
            // Prefer an exact hs_code match, then a marketplace-scoped rule.
            ->orderByRaw('hs_code is null')
            ->orderByRaw('marketplace_id is null')
            ->first();

        return $rule ? (float) $rule->duty_rate : 0.0;
    }

    /**
     * Best-effort HS code for a product from product_countries_of_origin.
     * Guarded because the country-of-origin table is optional and its name
     * is not guaranteed present in every environment.
     */
    public function hsCodeForProduct(int $productId): ?string
    {
        if (! Schema::hasTable('product_countries_of_origin')) {
            return null;
        }

        $hsCode = DB::table('product_countries_of_origin')
            ->where('product_id', $productId)
            ->whereNotNull('hs_code')
            ->orderByDesc('id')
            ->value('hs_code');

        return $hsCode ?: null;
    }
}
