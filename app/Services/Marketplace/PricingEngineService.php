<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\PricingRule;
use App\Models\Marketplace\TaxRule;
use App\Models\Marketplace\Currency;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PricingEngineService
{
    protected const BASE_CURRENCY = 'USD';

    /**
     * Calculate final price for a product in a specific marketplace
     */
    public function calculatePrice(
        Product $product,
        Marketplace $marketplace,
        ?int $quantity = 1,
        ?string $warehouseId = null
    ): array {
        $cacheKey = "price_{$product->id}_{$marketplace->id}_{$warehouseId}_{$quantity}";
        
        return Cache::remember($cacheKey, 300, function () use ($product, $marketplace, $quantity, $warehouseId) {
            // Get base cost in USD
            $baseCostUsd = $this->getBaseCostUsd($product, $warehouseId);
            
            if (!$baseCostUsd) {
                return ['error' => 'No base cost found'];
            }

            // Get currency and exchange rate
            $currency = Currency::where('code', $marketplace->currency_code)->first();
            $exchangeRate = $currency?->exchange_rate_to_usd ?? 1.0;

            // Convert to local currency
            $priceInLocalCurrency = $baseCostUsd * $exchangeRate;

            // Apply pricing rules (in priority order)
            $markups = $this->applyPricingRules($product, $marketplace, $priceInLocalCurrency);
            $priceAfterMarkups = $markups['price'];

            // Calculate taxes
            $taxes = $this->calculateTaxes($priceAfterMarkups, $marketplace->country_code, $product);
            
            // Calculate duty if cross-border
            $duty = $this->calculateDuty($baseCostUsd, $marketplace->country_code, $product);

            // Final price
            $finalPrice = $priceAfterMarkups + $taxes['total_tax'] + $duty;

            // Apply quantity breaks
            $priceBreaks = $this->getQuantityBreaks($product, $marketplace, $finalPrice);
            $applicableBreak = $this->findApplicableBreak($priceBreaks, $quantity);
            
            if ($applicableBreak) {
                $finalPrice = $applicableBreak['price'];
            }

            // Round according to marketplace rules
            $finalPrice = $this->roundPrice($finalPrice, $marketplace);

            return [
                'product_id' => $product->id,
                'marketplace_id' => $marketplace->id,
                'base_cost_usd' => round($baseCostUsd, 4),
                'exchange_rate' => $exchangeRate,
                'price_before_markups' => round($priceInLocalCurrency, 2),
                'markups_applied' => $markups['details'],
                'price_after_markups' => round($priceAfterMarkups, 2),
                'duty_amount' => round($duty, 2),
                'taxes' => $taxes['details'],
                'total_tax' => round($taxes['total_tax'], 2),
                'final_price' => round($finalPrice, 2),
                'currency_code' => $marketplace->currency_code,
                'currency_symbol' => $currency?->symbol ?? '$',
                'quantity' => $quantity,
                'price_breaks' => $priceBreaks,
                'moq' => $product->moq ?? 1,
                'order_multiple' => $product->order_multiple ?? 1,
            ];
        });
    }

    protected function getBaseCostUsd(Product $product, ?string $warehouseId): ?float
    {
        // Try warehouse-specific cost first
        if ($warehouseId) {
            $inventory = DB::table('inventory_warehouse')
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->first();
            
            if ($inventory && $inventory->last_cost) {
                return (float) $inventory->last_cost;
            }
        }

        // Fallback to product base cost
        return $product->cost_usd ?? null;
    }

    protected function applyPricingRules(
        Product $product,
        Marketplace $marketplace,
        float $price
    ): array {
        $details = [];
        
        // Get active pricing rules for this marketplace
        $rules = PricingRule::where('marketplace_id', $marketplace->id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($rules as $rule) {
            $applicable = false;

            // Check if rule applies to this product
            if ($rule->rule_type === 'category_markup' && $rule->target_type === 'category') {
                if ($product->category_id === $rule->target_id) {
                    $applicable = true;
                    $details[] = "Category markup: {$rule->percentage_markup}%";
                }
            } elseif ($rule->rule_type === 'brand_markup' && $rule->target_type === 'brand') {
                if ($product->brand_id === $rule->target_id) {
                    $applicable = true;
                    $details[] = "Brand markup: {$rule->percentage_markup}%";
                }
            } elseif ($rule->rule_type === 'country_markup') {
                $applicable = true;
                $details[] = "Country markup: {$rule->percentage_markup}%";
            }

            if ($applicable) {
                $percentageAmount = $price * ($rule->percentage_markup / 100);
                $price += $percentageAmount + $rule->fixed_markup;
                
                // Ensure minimum margin
                if ($rule->min_margin > 0) {
                    $currentMargin = (($price - $price) / $price) * 100;
                    if ($currentMargin < $rule->min_margin) {
                        $price = $price / (1 - ($rule->min_margin / 100));
                    }
                }
            }
        }

        return ['price' => $price, 'details' => $details];
    }

    protected function calculateTaxes(
        float $price,
        string $countryCode,
        Product $product
    ): array {
        $details = [];
        $totalTax = 0;

        $taxRules = TaxRule::where('country_code', $countryCode)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', now())
            ->where(function ($q) use ($product) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', now());
            })
            ->get();

        foreach ($taxRules as $rule) {
            // Check if tax applies to this product category
            $exemptCategories = $rule->exempt_categories ?? [];
            if (in_array($product->category_id, $exemptCategories)) {
                continue;
            }

            $taxAmount = $price * ($rule->rate / 100);
            
            if ($rule->is_compound) {
                $taxAmount = ($price + $totalTax) * ($rule->rate / 100);
            }

            $totalTax += $taxAmount;
            $details[] = [
                'name' => $rule->tax_name,
                'type' => $rule->tax_type,
                'rate' => $rule->rate,
                'amount' => round($taxAmount, 2),
            ];
        }

        return ['total_tax' => $totalTax, 'details' => $details];
    }

    protected function calculateDuty(
        float $baseCostUsd,
        string $countryCode,
        Product $product
    ): float {
        // Simplified duty calculation based on HS code
        // In production, this would use a comprehensive duty table
        $hsCode = $product->hs_code ?? null;
        
        if (!$hsCode) {
            return 0;
        }

        // Example duty rates by HS code prefix (simplified)
        $dutyRates = [
            '85' => 0.05, // Electronics
            '90' => 0.03, // Instruments
            '84' => 0.04, // Machinery
        ];

        $prefix = substr($hsCode, 0, 2);
        $rate = $dutyRates[$prefix] ?? 0;

        return $baseCostUsd * $rate;
    }

    protected function getQuantityBreaks(
        Product $product,
        Marketplace $marketplace,
        float $basePrice
    ): array {
        // Check for stored price breaks
        $priceData = DB::table('product_marketplace_prices')
            ->where('product_id', $product->id)
            ->where('marketplace_id', $marketplace->id)
            ->first();

        if ($priceData && $priceData->price_breaks) {
            return json_decode($priceData->price_breaks, true) ?? [];
        }

        // Default breaks if none specified
        return [
            ['min_qty' => 1, 'price' => $basePrice],
            ['min_qty' => 10, 'price' => $basePrice * 0.95],
            ['min_qty' => 100, 'price' => $basePrice * 0.90],
            ['min_qty' => 1000, 'price' => $basePrice * 0.85],
        ];
    }

    protected function findApplicableBreak(array $breaks, int $quantity): ?array
    {
        $applicable = null;
        
        foreach ($breaks as $break) {
            $minQty = $break['min_qty'] ?? $break['qty'] ?? 1;
            if ($quantity >= $minQty) {
                $applicable = $break;
            }
        }

        return $applicable;
    }

    protected function roundPrice(float $price, Marketplace $marketplace): float
    {
        // Get rounding rules from marketplace settings
        $settings = $marketplace->settings ?? [];
        $rounding = $settings['price_rounding'] ?? 'nearest_09';

        switch ($rounding) {
            case 'nearest_00':
                return ceil($price);
            case 'nearest_05':
                return ceil($price * 2) / 2;
            case 'nearest_09':
                return ceil($price) - 0.01;
            case 'nearest_99':
                return ceil($price) - 0.01;
            default:
                return round($price, 2);
        }
    }

    /**
     * Clear price cache for a product
     */
    public function clearPriceCache(string $productId): void
    {
        Cache::tags(["price_{$productId}"])->flush();
    }

    /**
     * Clear all price cache for a marketplace
     */
    public function clearMarketplacePriceCache(string $marketplaceId): void
    {
        Cache::tags(["price_{$marketplaceId}"])->flush();
    }
}
