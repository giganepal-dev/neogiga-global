<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Pricing\MarginFloorRule;
use App\Models\Pricing\PriceFloorRule;
use App\Models\Pricing\PricingRule;
use App\Services\Pricing\ExchangeRateService;
use App\Services\Pricing\PriceSimulator;
use App\Services\Pricing\PricingContext;
use App\Services\Pricing\PricingRuleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage for the configurable pricing-rule engine: the four calculation
 * methods, deterministic scope precedence, priority, stacking, stop_processing,
 * price-floor and margin-floor blocking, currency conversion of fixed amounts,
 * marketplace isolation, and the simulator's no-write guarantee.
 */
class PricingRuleEngineTest extends TestCase
{
    use RefreshDatabase;

    private Marketplace $global;

    private Marketplace $nepal;

    private int $productId = 501;

    private int $categoryId = 42;

    private function seedBaseline(): void
    {
        $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
        $npr = Currency::firstOrCreate(['code' => 'NPR'], ['name' => 'Nepalese Rupee', 'symbol' => 'Rs', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
        $np = Country::firstOrCreate(['iso_code_2' => 'NP'], ['name' => 'Nepal', 'iso_code_3' => 'NPL', 'currency_code' => 'NPR', 'is_active' => true]);

        $this->global = Marketplace::firstOrCreate(['code' => 'GLOBAL'], ['name' => 'NeoGiga Global', 'country_id' => $np->id, 'currency_id' => $usd->id, 'timezone' => 'UTC', 'locale' => 'en', 'is_active' => true, 'is_default' => true, 'global_fallback' => true]);
        $this->nepal = Marketplace::firstOrCreate(['code' => 'NEPAL'], ['name' => 'GigaNepal', 'country_id' => $np->id, 'currency_id' => $npr->id, 'timezone' => 'Asia/Kathmandu', 'locale' => 'en', 'is_active' => true]);
    }

    private function rule(array $overrides): PricingRule
    {
        static $n = 0;
        $n++;

        return PricingRule::create(array_merge([
            'name' => "Rule {$n}",
            'code' => "rule-{$n}",
            'owner_type' => 'global_admin',
            'scope_type' => 'global',
            'cost_basis' => 'landed_unit',
            'action_type' => 'percentage_markup',
            'action_value' => 0,
            'priority' => 0,
            'condition_operator' => 'and',
            'stackable' => false,
            'stop_processing' => false,
            'active' => true,
            'approval_status' => 'approved',
            'version' => 1,
        ], $overrides));
    }

    private function ctx(float $cost = 1.0, string $currency = 'USD', array $extra = []): PricingContext
    {
        return new PricingContext(
            productId: $extra['productId'] ?? $this->productId,
            marketplace: $extra['marketplace'] ?? $this->global,
            costBasisAmount: $cost,
            currencyCode: $currency,
            quantity: $extra['quantity'] ?? 1,
            customerSegment: $extra['customerSegment'] ?? null,
            categoryId: $extra['categoryId'] ?? null,
            sellerId: $extra['sellerId'] ?? null,
            countryId: $extra['countryId'] ?? null,
        );
    }

    private function resolver(): PricingRuleResolver
    {
        return app(PricingRuleResolver::class);
    }

    public function test_percentage_markup(): void
    {
        $this->seedBaseline();
        $this->rule(['action_type' => 'percentage_markup', 'action_value' => 25]);

        $r = $this->resolver()->price($this->ctx(1.0));
        $this->assertEqualsWithDelta(1.25, $r['final_price'], 0.0001);
    }

    public function test_fixed_markup(): void
    {
        $this->seedBaseline();
        $this->rule(['action_type' => 'fixed_markup', 'action_value' => 0.40, 'action_currency' => 'USD']);

        $r = $this->resolver()->price($this->ctx(1.0));
        $this->assertEqualsWithDelta(1.40, $r['final_price'], 0.0001);
    }

    public function test_fixed_selling_price_override(): void
    {
        $this->seedBaseline();
        $this->rule(['action_type' => 'fixed_selling_price', 'action_value' => 1.75, 'action_currency' => 'USD']);

        $r = $this->resolver()->price($this->ctx(1.0));
        $this->assertEqualsWithDelta(1.75, $r['final_price'], 0.0001);
    }

    public function test_target_margin_is_not_markup(): void
    {
        $this->seedBaseline();
        // 30% target MARGIN => 1 / (1 - 0.30) = 1.4286, NOT 1.30
        $this->rule(['action_type' => 'margin_target', 'action_value' => 30]);

        $r = $this->resolver()->price($this->ctx(1.0));
        $this->assertEqualsWithDelta(1.4286, $r['final_price'], 0.0005);
        $this->assertNotEqualsWithDelta(1.30, $r['final_price'], 0.01);
    }

    public function test_product_scope_beats_category_and_global(): void
    {
        $this->seedBaseline();
        $this->rule(['scope_type' => 'global', 'action_type' => 'percentage_markup', 'action_value' => 10]);
        $this->rule(['scope_type' => 'category', 'scope_category_id' => $this->categoryId, 'action_type' => 'percentage_markup', 'action_value' => 20]);
        $this->rule(['scope_type' => 'product', 'scope_product_id' => $this->productId, 'action_type' => 'percentage_markup', 'action_value' => 50]);

        $r = $this->resolver()->price($this->ctx(1.0, 'USD', ['categoryId' => $this->categoryId]));
        // product rule (50%) wins, non-stackable => others skipped
        $this->assertEqualsWithDelta(1.50, $r['final_price'], 0.0001);
        $skipped = array_filter($r['trace'], fn ($t) => $t['applied'] === false);
        $this->assertNotEmpty($skipped, 'lower-precedence rules must appear as skipped in the trace');
    }

    public function test_priority_breaks_ties_within_same_scope(): void
    {
        $this->seedBaseline();
        $this->rule(['scope_type' => 'global', 'action_type' => 'percentage_markup', 'action_value' => 10, 'priority' => 1]);
        $this->rule(['scope_type' => 'global', 'action_type' => 'percentage_markup', 'action_value' => 80, 'priority' => 9]);

        $r = $this->resolver()->price($this->ctx(1.0));
        $this->assertEqualsWithDelta(1.80, $r['final_price'], 0.0001);
    }

    public function test_stackable_primaries_compound(): void
    {
        $this->seedBaseline();
        $this->rule(['scope_type' => 'global', 'action_type' => 'percentage_markup', 'action_value' => 10, 'priority' => 5, 'stackable' => true]);
        $this->rule(['scope_type' => 'global', 'action_type' => 'percentage_markup', 'action_value' => 10, 'priority' => 1, 'stackable' => true]);

        $r = $this->resolver()->price($this->ctx(1.0));
        // 1.0 * 1.10 * 1.10 = 1.21
        $this->assertEqualsWithDelta(1.21, $r['final_price'], 0.0001);
    }

    public function test_stop_processing_halts(): void
    {
        $this->seedBaseline();
        $this->rule(['scope_type' => 'global', 'action_type' => 'percentage_markup', 'action_value' => 10, 'priority' => 5, 'stackable' => true, 'stop_processing' => true]);
        $this->rule(['scope_type' => 'global', 'action_type' => 'percentage_markup', 'action_value' => 90, 'priority' => 1, 'stackable' => true]);

        $r = $this->resolver()->price($this->ctx(1.0));
        $this->assertEqualsWithDelta(1.10, $r['final_price'], 0.0001);
    }

    public function test_minimum_price_modifier_raises_floor(): void
    {
        $this->seedBaseline();
        $this->rule(['scope_type' => 'global', 'action_type' => 'percentage_markup', 'action_value' => 10]);
        $this->rule(['scope_type' => 'global', 'action_type' => 'minimum_price', 'action_value' => 2.0, 'action_currency' => 'USD']);

        $r = $this->resolver()->price($this->ctx(1.0));
        $this->assertEqualsWithDelta(2.0, $r['final_price'], 0.0001);
    }

    public function test_price_floor_rule_blocks_below_absolute_minimum(): void
    {
        $this->seedBaseline();
        $this->rule(['scope_type' => 'global', 'action_type' => 'fixed_selling_price', 'action_value' => 0.50, 'action_currency' => 'USD']);
        PriceFloorRule::create(['scope_type' => 'global', 'min_absolute_price' => 1.00, 'currency_code' => 'USD', 'is_active' => true]);

        $r = $this->resolver()->price($this->ctx(1.0));
        $this->assertTrue($r['blocked']);
        $this->assertNotEmpty($r['block_reasons']);
    }

    public function test_margin_floor_rule_blocks_thin_margin(): void
    {
        $this->seedBaseline();
        // 5% markup on cost 1.00 => 1.05, gross margin ~4.76% < 20% floor
        $this->rule(['scope_type' => 'global', 'action_type' => 'percentage_markup', 'action_value' => 5]);
        MarginFloorRule::create(['scope_type' => 'global', 'min_gross_margin_percent' => 20.0, 'is_active' => true]);

        $r = $this->resolver()->price($this->ctx(1.0));
        $this->assertTrue($r['blocked']);
    }

    public function test_fixed_amount_in_foreign_currency_is_converted(): void
    {
        $this->seedBaseline();
        app(ExchangeRateService::class)->record('USD', 'NPR', 133.0, 'test');
        // cost 100 NPR, +1 USD fixed markup => +133 NPR => 233 NPR
        $this->rule(['scope_type' => 'global', 'action_type' => 'fixed_markup', 'action_value' => 1.0, 'action_currency' => 'USD']);

        $r = $this->resolver()->price($this->ctx(100.0, 'NPR', ['marketplace' => $this->nepal]));
        $this->assertEqualsWithDelta(233.0, $r['final_price'], 0.01);
    }

    public function test_fixed_foreign_amount_without_rate_is_skipped_not_guessed(): void
    {
        $this->seedBaseline();
        // no USD->NPR rate recorded
        $this->rule(['scope_type' => 'global', 'action_type' => 'fixed_markup', 'action_value' => 1.0, 'action_currency' => 'USD']);

        $r = $this->resolver()->price($this->ctx(100.0, 'NPR', ['marketplace' => $this->nepal]));
        $this->assertEqualsWithDelta(100.0, $r['final_price'], 0.01, 'unconvertible fixed amount must be skipped, never guessed');
        $skipped = array_filter($r['trace'], fn ($t) => $t['applied'] === false && str_contains($t['reason'], 'exchange rate'));
        $this->assertNotEmpty($skipped);
    }

    public function test_marketplace_isolation(): void
    {
        $this->seedBaseline();
        // rule bound to NEPAL only
        $this->rule(['scope_type' => 'marketplace', 'marketplace_id' => $this->nepal->id, 'action_type' => 'percentage_markup', 'action_value' => 50]);

        $r = $this->resolver()->price($this->ctx(1.0)); // global context
        $this->assertEqualsWithDelta(1.0, $r['final_price'], 0.0001, 'a NEPAL rule must not affect the GLOBAL marketplace');
    }

    public function test_quantity_tier_gating(): void
    {
        $this->seedBaseline();
        $this->rule(['scope_type' => 'quantity_tier', 'min_quantity' => 10, 'action_type' => 'fixed_selling_price', 'action_value' => 0.90, 'action_currency' => 'USD']);

        $below = $this->resolver()->price($this->ctx(1.0, 'USD', ['quantity' => 5]));
        $this->assertEqualsWithDelta(1.0, $below['final_price'], 0.0001, 'qty 5 below tier min 10 => tier rule must not apply');

        $at = $this->resolver()->price($this->ctx(1.0, 'USD', ['quantity' => 12]));
        $this->assertEqualsWithDelta(0.90, $at['final_price'], 0.0001, 'qty 12 meets tier => tier price applies');
    }

    public function test_draft_and_unapproved_rules_are_ignored(): void
    {
        $this->seedBaseline();
        $this->rule(['scope_type' => 'global', 'action_type' => 'percentage_markup', 'action_value' => 99, 'approval_status' => 'draft']);

        $r = $this->resolver()->price($this->ctx(1.0));
        $this->assertEqualsWithDelta(1.0, $r['final_price'], 0.0001, 'draft rules must never affect price');
    }

    public function test_simulator_never_writes(): void
    {
        $this->seedBaseline();
        $this->rule(['scope_type' => 'global', 'action_type' => 'percentage_markup', 'action_value' => 25]);

        $rulesBefore = PricingRule::count();
        $result = app(PriceSimulator::class)->simulate($this->ctx(1.0));

        $this->assertFalse($result['persisted']);
        $this->assertTrue($result['simulated']);
        $this->assertEqualsWithDelta(1.25, $result['final_price'], 0.0001);
        $this->assertSame($rulesBefore, PricingRule::count(), 'simulation must not create/alter any rows');
    }
}
