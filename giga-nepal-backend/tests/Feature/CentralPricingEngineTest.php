<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\ExchangeRate;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceProductPrice;
use App\Models\Marketplace\MarketplaceSetting;
use App\Models\Marketplace\PriceCalculationLog;
use App\Models\Marketplace\Product;
use App\Services\Pricing\CentralPricingService;
use App\Services\Pricing\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Stage 2 continuation coverage: append-only exchange rates with staleness
 * refusal, the v1 pricing formula, and the "never overwrite an existing
 * price row" guarantee (see CENTRAL_PRICING_ENGINE_GUIDE.md /
 * EXCHANGE_RATE_GUIDE.md).
 */
class CentralPricingEngineTest extends TestCase
{
    use RefreshDatabase;

    private Marketplace $global;

    private Marketplace $nepal;

    private Product $product;

    private function seedPricingBaseline(): void
    {
        $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
        $npr = Currency::firstOrCreate(['code' => 'NPR'], ['name' => 'Nepalese Rupee', 'symbol' => 'Rs', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
        $np = Country::firstOrCreate(['iso_code_2' => 'NP'], ['name' => 'Nepal', 'iso_code_3' => 'NPL', 'currency_code' => 'NPR', 'is_active' => true]);

        $this->global = Marketplace::firstOrCreate(['code' => 'GLOBAL'], ['name' => 'NeoGiga Global', 'country_id' => $np->id, 'currency_id' => $usd->id, 'timezone' => 'UTC', 'locale' => 'en', 'is_active' => true, 'is_default' => true, 'global_fallback' => true]);
        $this->nepal = Marketplace::firstOrCreate(['code' => 'NEPAL'], ['name' => 'GigaNepal', 'country_id' => $np->id, 'currency_id' => $npr->id, 'timezone' => 'Asia/Kathmandu', 'locale' => 'en', 'is_active' => true]);

        $this->product = Product::create(['name' => 'Test MCU', 'slug' => 'test-mcu', 'sku' => 'NG-TEST-0001']);

        MarketplaceProductPrice::create([
            'product_id' => $this->product->id,
            'marketplace_id' => $this->global->id,
            'base_price' => 120.0,
            'cost_price' => 100.0,
            'currency_code' => 'USD',
            'is_active' => true,
        ]);
    }

    public function test_latest_rate_returns_newest_fetch(): void
    {
        $this->seedPricingBaseline();
        $svc = app(ExchangeRateService::class);

        $svc->record('USD', 'NPR', 130.0, 'test', now()->subHours(2));
        $svc->record('USD', 'NPR', 133.0, 'test', now());

        $this->assertSame(2, ExchangeRate::count(), 'history must be append-only');
        $this->assertEqualsWithDelta(133.0, (float) $svc->latestRate('USD', 'NPR')->rate, 0.0001);
    }

    public function test_record_rejects_non_positive_rate(): void
    {
        $this->seedPricingBaseline();

        $this->expectException(InvalidArgumentException::class);
        app(ExchangeRateService::class)->record('USD', 'NPR', 0.0, 'test');
    }

    public function test_fresh_rate_refuses_stale_rate(): void
    {
        $this->seedPricingBaseline();
        $svc = app(ExchangeRateService::class);

        $svc->record('USD', 'NPR', 133.0, 'test', now()->subHours(72));

        $this->assertNotNull($svc->latestRate('USD', 'NPR'));
        $this->assertNull($svc->freshRate('USD', 'NPR'), 'a 72h-old rate must be refused at the default 48h threshold');
    }

    public function test_record_from_base_currency_updates_currencies_cache(): void
    {
        $this->seedPricingBaseline();

        app(ExchangeRateService::class)->record('USD', 'NPR', 133.0, 'test');

        $this->assertEqualsWithDelta(133.0, (float) Currency::where('code', 'NPR')->value('exchange_rate'), 0.0001);
        $this->assertNotNull(Currency::where('code', 'NPR')->value('exchange_rate_updated_at'));
    }

    public function test_record_from_non_base_currency_leaves_cache_alone(): void
    {
        $this->seedPricingBaseline();

        app(ExchangeRateService::class)->record('NPR', 'USD', 0.0075, 'test');

        $this->assertEqualsWithDelta(1.0, (float) Currency::where('code', 'USD')->value('exchange_rate'), 0.0001);
    }

    public function test_calculate_refuses_without_base_cost(): void
    {
        $this->seedPricingBaseline();
        MarketplaceProductPrice::query()->delete();
        app(ExchangeRateService::class)->record('USD', 'NPR', 133.0, 'test');

        $this->assertNull(app(CentralPricingService::class)->calculate($this->product->id, $this->nepal));
        $this->assertSame(0, PriceCalculationLog::count());
    }

    public function test_calculate_refuses_without_fresh_rate(): void
    {
        $this->seedPricingBaseline();
        app(ExchangeRateService::class)->record('USD', 'NPR', 133.0, 'test', now()->subHours(72));

        $this->assertNull(app(CentralPricingService::class)->calculate($this->product->id, $this->nepal));
        $this->assertSame(0, PriceCalculationLog::count());
    }

    public function test_calculate_applies_formula_and_logs(): void
    {
        $this->seedPricingBaseline();
        app(ExchangeRateService::class)->record('USD', 'NPR', 133.0, 'test');

        MarketplaceSetting::create(['marketplace_id' => $this->nepal->id, 'key' => 'pricing.margin_percent', 'value' => '10', 'type' => 'float']);
        DB::table('tax_rules')->insert([
            'marketplace_id' => $this->nepal->id,
            'tax_name' => 'VAT',
            'tax_type' => 'percentage',
            'tax_rate' => 13.00,
            'applies_to' => 'all',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = app(CentralPricingService::class)->calculate($this->product->id, $this->nepal);

        $this->assertNotNull($log);
        // cost 100 USD * 133 = 13300; margin 10% = 1330; pre-tax 14630; VAT 13% = 1901.90
        $this->assertEqualsWithDelta(100.0, (float) $log->base_cost_usd, 0.0001);
        $this->assertEqualsWithDelta(133.0, (float) $log->exchange_rate, 0.0001);
        $this->assertEqualsWithDelta(1330.0, (float) $log->margin_amount, 0.01);
        $this->assertEqualsWithDelta(1901.90, (float) $log->tax_amount, 0.01);
        $this->assertEqualsWithDelta(16531.90, (float) $log->final_price, 0.01);
        $this->assertSame('NPR', $log->currency_code);
        $this->assertSame('v1', $log->calculation_version);
        $this->assertSame(1, PriceCalculationLog::count(), 'every calculation must be logged');
    }

    public function test_calculate_in_base_currency_needs_no_rate_row(): void
    {
        $this->seedPricingBaseline();

        $log = app(CentralPricingService::class)->calculate($this->product->id, $this->global);

        $this->assertNotNull($log);
        $this->assertEqualsWithDelta(1.0, (float) $log->exchange_rate, 0.0001);
        $this->assertEqualsWithDelta(100.0, (float) $log->final_price, 0.01);
    }

    public function test_apply_creates_price_once_and_never_overwrites(): void
    {
        $this->seedPricingBaseline();
        app(ExchangeRateService::class)->record('USD', 'NPR', 133.0, 'test');
        $svc = app(CentralPricingService::class);

        $log = $svc->calculate($this->product->id, $this->nepal);
        $price = $svc->apply($log);

        $this->assertNotNull($price);
        $this->assertEqualsWithDelta(13300.0, (float) $price->base_price, 0.01);
        $this->assertSame(1, DB::table('regional_price_history')->count(), 'apply must be recorded in history');

        // Second apply — and any apply onto an existing row — must refuse.
        $secondLog = $svc->calculate($this->product->id, $this->nepal);
        $this->assertNull($svc->apply($secondLog));
        $this->assertSame(1, MarketplaceProductPrice::where('marketplace_id', $this->nepal->id)->count());
        $this->assertEqualsWithDelta(
            13300.0,
            (float) MarketplaceProductPrice::where('marketplace_id', $this->nepal->id)->value('base_price'),
            0.01,
            'the existing price row must be untouched'
        );
    }

    public function test_refresh_command_records_configured_manual_rates(): void
    {
        $this->seedPricingBaseline();
        config(['pricing.manual_rates' => ['NPR' => 133.0]]);

        $this->artisan('pricing:refresh-exchange-rates')->assertSuccessful();

        $rate = ExchangeRate::where('to_currency_code', 'NPR')->first();
        $this->assertNotNull($rate);
        $this->assertSame('manual-config', $rate->source);
        $this->assertEqualsWithDelta(133.0, (float) $rate->rate, 0.0001);
    }

    public function test_refresh_command_is_noop_with_empty_config(): void
    {
        $this->seedPricingBaseline();
        config(['pricing.manual_rates' => []]);

        $this->artisan('pricing:refresh-exchange-rates')->assertSuccessful();

        $this->assertSame(0, ExchangeRate::count());
    }
}
