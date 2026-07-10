<?php

namespace App\Services\Pricing;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceProductPrice;
use App\Models\Marketplace\MarketplaceSetting;
use App\Models\Marketplace\PriceCalculationLog;
use App\Models\Marketplace\RegionalPriceHistory;
use Illuminate\Support\Facades\DB;

/**
 * Central pricing engine v1 (see CENTRAL_PRICING_ENGINE_GUIDE.md).
 *
 * Spec formula, restricted to the data that exists today:
 *   local_cost    = base_cost_usd * exchange_rate
 *   pre_tax_price = local_cost + margin           (duty/freight = 0 in v1 —
 *                                                  no HS-code or rate-card
 *                                                  tables exist yet)
 *   final_price   = pre_tax_price + tax            (existing tax_rules table)
 *
 * calculate() only computes and logs; it never touches live prices.
 * apply() creates a marketplace_product_prices row ONLY when none exists —
 * every existing row is treated as manually managed and is never overwritten.
 */
class CentralPricingService
{
    public const VERSION = 'v1';

    public function __construct(private readonly ExchangeRateService $rates)
    {
    }

    /**
     * Compute the regional price breakdown and append a
     * price_calculation_logs row. Returns null (and logs nothing) when a
     * required input is missing — a base cost in the base currency, or a
     * fresh exchange rate — rather than inventing one.
     */
    public function calculate(int $productId, Marketplace $marketplace): ?PriceCalculationLog
    {
        $currencyCode = strtoupper((string) $marketplace->currency?->code);
        if ($currencyCode === '') {
            return null;
        }

        $baseCost = $this->baseCostUsd($productId);
        if ($baseCost === null) {
            return null;
        }

        $base = $this->rates->baseCurrency();
        if ($currencyCode === $base) {
            $exchangeRate = 1.0;
        } else {
            $fresh = $this->rates->freshRate($base, $currencyCode);
            if (! $fresh) {
                return null;
            }
            $exchangeRate = (float) $fresh->rate;
        }

        $localCost = $baseCost * $exchangeRate;
        $marginPercent = $this->marginPercent($marketplace);
        $marginAmount = round($localCost * $marginPercent / 100, 4);
        $preTax = $localCost + $marginAmount;

        $taxRate = $this->taxRatePercent($marketplace);
        $taxAmount = round($preTax * $taxRate / 100, 4);

        return PriceCalculationLog::create([
            'product_id' => $productId,
            'marketplace_id' => $marketplace->id,
            'base_cost_usd' => $baseCost,
            'exchange_rate' => $exchangeRate,
            'duty_amount' => 0,
            'tax_amount' => $taxAmount,
            'freight_amount' => 0,
            'margin_amount' => $marginAmount,
            'final_price' => round($preTax + $taxAmount, 2),
            'currency_code' => $currencyCode,
            'calculation_version' => self::VERSION,
        ]);
    }

    /**
     * Materialize a calculation as the marketplace's live price — but only
     * when no price row exists yet for the product+marketplace. Existing
     * rows (manual or otherwise) are never overwritten; returns null so the
     * caller knows nothing changed. Every write is recorded in
     * regional_price_history.
     */
    public function apply(PriceCalculationLog $calculation): ?MarketplaceProductPrice
    {
        $exists = MarketplaceProductPrice::query()
            ->where('product_id', $calculation->product_id)
            ->where('marketplace_id', $calculation->marketplace_id)
            ->whereNull('product_variant_id')
            ->exists();

        if ($exists) {
            return null;
        }

        $taxRate = $calculation->final_price > 0 && $calculation->tax_amount > 0
            ? round($calculation->tax_amount / ($calculation->final_price - $calculation->tax_amount) * 100, 2)
            : null;

        $price = MarketplaceProductPrice::create([
            'product_id' => $calculation->product_id,
            'marketplace_id' => $calculation->marketplace_id,
            'base_price' => $calculation->final_price,
            'currency_code' => $calculation->currency_code,
            'is_tax_inclusive' => true,
            'tax_rate' => $taxRate,
            'is_active' => true,
        ]);

        RegionalPriceHistory::create([
            'marketplace_product_price_id' => $price->id,
            'product_id' => $calculation->product_id,
            'marketplace_id' => $calculation->marketplace_id,
            'old_base_price' => null,
            'new_base_price' => $calculation->final_price,
            'currency_code' => $calculation->currency_code,
            'reason' => 'central-pricing-' . self::VERSION,
        ]);

        return $price;
    }

    /**
     * Base cost in the base currency, from the global-fallback marketplace's
     * price row (cost_price preferred, base_price otherwise).
     */
    private function baseCostUsd(int $productId): ?float
    {
        $globalId = Marketplace::query()->where('global_fallback', true)->value('id');
        if (! $globalId) {
            return null;
        }

        $row = MarketplaceProductPrice::query()
            ->where('product_id', $productId)
            ->where('marketplace_id', $globalId)
            ->where('currency_code', $this->rates->baseCurrency())
            ->where('is_active', true)
            ->first();

        $cost = $row?->cost_price ?? $row?->base_price;

        return $cost !== null ? (float) $cost : null;
    }

    private function marginPercent(Marketplace $marketplace): float
    {
        $setting = MarketplaceSetting::query()
            ->where('marketplace_id', $marketplace->id)
            ->where('key', 'pricing.margin_percent')
            ->value('value');

        return $setting !== null
            ? (float) $setting
            : (float) config('pricing.default_margin_percent', 0);
    }

    /**
     * Most specific active percentage tax rule: marketplace match first,
     * then country match. Fixed-amount and compound rules are out of scope
     * for v1.
     */
    private function taxRatePercent(Marketplace $marketplace): float
    {
        $today = now()->toDateString();

        $rule = DB::table('tax_rules')
            ->where('tax_type', 'percentage')
            ->where('is_active', true)
            ->whereIn('applies_to', ['all', 'products'])
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_until')->orWhere('effective_until', '>=', $today);
            })
            ->where(function ($q) use ($marketplace) {
                $q->where('marketplace_id', $marketplace->id);
                if ($marketplace->country_id) {
                    $q->orWhere(function ($inner) use ($marketplace) {
                        $inner->whereNull('marketplace_id')->where('country_id', $marketplace->country_id);
                    });
                }
            })
            ->orderByRaw('marketplace_id is null')
            ->first();

        return $rule ? (float) $rule->tax_rate : 0.0;
    }
}
