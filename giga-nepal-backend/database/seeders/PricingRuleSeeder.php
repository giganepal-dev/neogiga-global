<?php

namespace Database\Seeders;

use App\Models\Pricing\PricingRule;
use App\Models\Pricing\PriceFloorRule;
use App\Models\Pricing\MarginFloorRule;
use App\Models\Pricing\PriceRoundingRule;
use Illuminate\Database\Seeder;

class PricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGlobalRules();
        $this->seedB2BDiscountRules();
        $this->seedQuantityTierRules();
        $this->seedFloorAndRoundingRules();
    }

    private function seedGlobalRules(): void
    {
        // Default 15% markup on all products (global scope)
        PricingRule::updateOrCreate(
            ['code' => 'global-default-markup'],
            [
                'name' => 'Global Default Markup (15%)',
                'scope_type' => 'global',
                'customer_segment' => null,
                'action_type' => 'percentage_markup',
                'action_value' => 15.0,
                'priority' => 10,
                'stackable' => false,
                'stop_processing' => false,
                'active' => true,
                'approval_status' => 'approved',
                'version' => 1,
            ]
        );

        // Exchange rate buffer of 2% to protect against FX fluctuation
        PricingRule::updateOrCreate(
            ['code' => 'global-fx-buffer'],
            [
                'name' => 'Global FX Buffer (2%)',
                'scope_type' => 'global',
                'customer_segment' => null,
                'action_type' => 'exchange_rate_buffer',
                'action_value' => 2.0,
                'priority' => 100,
                'stackable' => true,
                'stop_processing' => false,
                'active' => true,
                'approval_status' => 'approved',
                'version' => 1,
            ]
        );

        // Rounding to nearest 0.01
        PricingRule::updateOrCreate(
            ['code' => 'global-rounding'],
            [
                'name' => 'Global Rounding (nearest cent)',
                'scope_type' => 'global',
                'customer_segment' => null,
                'action_type' => 'rounding',
                'action_value' => 0.01,
                'priority' => 200,
                'stackable' => true,
                'stop_processing' => false,
                'active' => true,
                'approval_status' => 'approved',
                'version' => 1,
            ]
        );
    }

    private function seedB2BDiscountRules(): void
    {
        // B2B Account tier 1: 10% discount (customer_segment = 'b2b_tier1')
        PricingRule::updateOrCreate(
            ['code' => 'b2b-tier1-discount'],
            [
                'name' => 'B2B Tier 1 Discount (10% off)',
                'scope_type' => 'b2b_account',
                'customer_segment' => 'b2b_tier1',
                'action_type' => 'percentage_markup',
                'action_value' => -10.0,
                'priority' => 50,
                'stackable' => true,
                'stop_processing' => false,
                'active' => true,
                'approval_status' => 'approved',
                'version' => 1,
            ]
        );

        // B2B Account tier 2: 15% discount
        PricingRule::updateOrCreate(
            ['code' => 'b2b-tier2-discount'],
            [
                'name' => 'B2B Tier 2 Discount (15% off)',
                'scope_type' => 'b2b_account',
                'customer_segment' => 'b2b_tier2',
                'action_type' => 'percentage_markup',
                'action_value' => -15.0,
                'priority' => 51,
                'stackable' => true,
                'stop_processing' => false,
                'active' => true,
                'approval_status' => 'approved',
                'version' => 1,
            ]
        );

        // B2B Account tier 3: 20% discount
        PricingRule::updateOrCreate(
            ['code' => 'b2b-tier3-discount'],
            [
                'name' => 'B2B Tier 3 Discount (20% off)',
                'scope_type' => 'b2b_account',
                'customer_segment' => 'b2b_tier3',
                'action_type' => 'percentage_markup',
                'action_value' => -20.0,
                'priority' => 52,
                'stackable' => true,
                'stop_processing' => false,
                'active' => true,
                'approval_status' => 'approved',
                'version' => 1,
            ]
        );

        // B2B minimum margin floor: 5%
        MarginFloorRule::updateOrCreate(
            ['code' => 'b2b-min-margin'],
            [
                'name' => 'B2B Minimum Margin (5%)',
                'min_gross_margin_percent' => 5.0,
                'is_active' => true,
            ]
        );
    }

    private function seedQuantityTierRules(): void
    {
        // Bulk discount: 5% off for 10-49 units
        PricingRule::updateOrCreate(
            ['code' => 'qty-tier-10-49'],
            [
                'name' => 'Bulk Discount 10-49 units (5% off)',
                'scope_type' => 'quantity_tier',
                'min_quantity' => 10,
                'max_quantity' => 49,
                'action_type' => 'percentage_markup',
                'action_value' => -5.0,
                'priority' => 60,
                'stackable' => true,
                'stop_processing' => false,
                'active' => true,
                'approval_status' => 'approved',
                'version' => 1,
            ]
        );

        // Bulk discount: 10% off for 50-99 units
        PricingRule::updateOrCreate(
            ['code' => 'qty-tier-50-99'],
            [
                'name' => 'Bulk Discount 50-99 units (10% off)',
                'scope_type' => 'quantity_tier',
                'min_quantity' => 50,
                'max_quantity' => 99,
                'action_type' => 'percentage_markup',
                'action_value' => -10.0,
                'priority' => 61,
                'stackable' => true,
                'stop_processing' => false,
                'active' => true,
                'approval_status' => 'approved',
                'version' => 1,
            ]
        );

        // Bulk discount: 15% off for 100+ units
        PricingRule::updateOrCreate(
            ['code' => 'qty-tier-100+'],
            [
                'name' => 'Bulk Discount 100+ units (15% off)',
                'scope_type' => 'quantity_tier',
                'min_quantity' => 100,
                'max_quantity' => null,
                'action_type' => 'percentage_markup',
                'action_value' => -15.0,
                'priority' => 62,
                'stackable' => true,
                'stop_processing' => false,
                'active' => true,
                'approval_status' => 'approved',
                'version' => 1,
            ]
        );
    }

    private function seedFloorAndRoundingRules(): void
    {
        // Global price floor: $0.01 minimum
        PriceFloorRule::updateOrCreate(
            ['code' => 'global-min-price'],
            [
                'name' => 'Global Minimum Price ($0.01)',
                'min_absolute_price' => 0.01,
                'currency_code' => 'USD',
                'is_active' => true,
            ]
        );

        // Price rounding: nearest $0.01
        PriceRoundingRule::updateOrCreate(
            ['code' => 'global-rounding'],
            [
                'name' => 'Global Price Rounding (nearest cent)',
                'increment' => 0.01,
                'strategy' => 'nearest',
                'is_active' => true,
            ]
        );
    }
}
