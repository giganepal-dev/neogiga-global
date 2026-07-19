<?php

namespace App\Services\Pricing;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;

/**
 * Calculates the landed cost and final regional selling price for a product.
 *
 * Internal formula (private — never exposed publicly):
 *   base_supplier_cost
 *   + freight_allocation
 *   + insurance
 *   + customs_duty
 *   + import_tariff
 *   + import_vat_gst (if non-recoverable)
 *   + clearance_handling
 *   + warehouse_allocation
 *   + currency_risk_adjustment
 *   = landed_cost
 *   + marketplace_margin
 *   + seller_margin
 *   - approved_discount
 *   + regional_sales_tax
 *   = final_regional_selling_price
 */
class LandedCostCalculator
{
    public function __construct(
        private readonly RegionalTaxResolver $taxResolver,
    ) {}

    /**
     * @return array{landed_cost:float, selling_price:float, tax_amount:float, duty_amount:float, margin_amount:float}
     */
    public function calculate(
        Product $product,
        Marketplace $marketplace,
        ?float $supplierCost = null,
        ?string $hsCode = null,
        ?string $originCountry = null
    ): array {
        $baseCost = $supplierCost ?? (float) ($product->cost_price ?? $product->base_price ?? 0);
        $hsCode ??= $product->hs_code;
        $originCountry ??= null;

        $tax = $this->taxResolver->resolve(
            $product->id,
            $product->category_id,
            $marketplace,
            $hsCode,
            $originCountry
        );

        $duty = $hsCode ? $this->taxResolver->importDuty($hsCode, $marketplace, $originCountry, $product->category_id) : null;
        $dutyRate = $duty['rate'] ?? 0;

        // Configurable allocations (ponytail: flat rates, per-marketplace config later)
        $freight = $baseCost * 0.03; // 3% freight estimate
        $insurance = $baseCost * 0.01; // 1% insurance
        $clearance = 5.00; // flat clearance fee
        $warehouse = $baseCost * 0.02; // 2% warehouse
        $currencyRisk = $baseCost * 0.01; // 1% currency risk

        // Landed cost = supplier + freight + insurance + duty + clearance + warehouse + currency
        $dutyAmount = $dutyRate > 0 ? $baseCost * ($dutyRate / 100) : 0;
        $landedCost = $baseCost + $freight + $insurance + $dutyAmount + $clearance + $warehouse + $currencyRisk;

        // Import VAT on landed cost (if non-recoverable)
        if ($tax['tax_rate'] > 0 && ! $tax['is_inclusive']) {
            $importTaxAmount = $landedCost * ($tax['tax_rate'] / 100);
        } else {
            $importTaxAmount = 0;
        }

        $taxableBase = $landedCost + $importTaxAmount;

        // Margin (configurable per marketplace)
        $marginPercent = (float) ($marketplace->settings['pricing']['margin_percent'] ?? config('pricing.default_margin_percent', 0));
        $marginAmount = $taxableBase * ($marginPercent / 100);

        // Final selling price before output tax
        $priceBeforeTax = $taxableBase + $marginAmount;

        // Output tax (sales tax/VAT on final price)
        if ($tax['is_inclusive']) {
            // Tax-inclusive: price already includes tax
            $outputTaxAmount = $priceBeforeTax - ($priceBeforeTax / (1 + $tax['tax_rate'] / 100));
            $finalPrice = $priceBeforeTax;
        } else {
            // Tax-exclusive: add tax on top
            $outputTaxAmount = $priceBeforeTax * ($tax['tax_rate'] / 100);
            $finalPrice = $priceBeforeTax + $outputTaxAmount;
        }

        return [
            'landed_cost' => round($landedCost, 2),
            'selling_price' => round($finalPrice, 2),
            'tax_amount' => round($importTaxAmount + $outputTaxAmount, 2),
            'duty_amount' => round($dutyAmount, 2),
            'margin_amount' => round($marginAmount, 2),
            'tax_rate' => $tax['tax_rate'],
            'tax_type' => $tax['tax_type'],
            'tax_inclusive' => $tax['is_inclusive'],
            'duty_rate' => $dutyRate,
        ];
    }

    /** Public-safe: returns only the final selling price and tax status. */
    public function publicPrice(Product $product, Marketplace $marketplace): array
    {
        $calc = $this->calculate($product, $marketplace);

        return [
            'price' => $calc['selling_price'],
            'currency' => $marketplace->currency?->code ?? 'USD',
            'tax_inclusive' => $calc['tax_inclusive'],
            'tax_type' => $calc['tax_type'],
        ];
    }
}
