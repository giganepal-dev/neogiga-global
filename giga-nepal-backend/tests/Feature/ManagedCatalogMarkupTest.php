<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceProductPrice;
use App\Models\Marketplace\Product;
use App\Services\Pricing\ManagedCatalogMarkupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ManagedCatalogMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reprices_managed_rows_creates_missing_regional_rows_and_preserves_manual_prices(): void
    {
        [$global, $nepal] = $this->marketplaces();
        $sourceBacked = $this->product('managed-markup-source');
        $missingRegional = $this->product('managed-markup-missing-regional');

        $this->price($sourceBacked, $global, 100, 105, 'jlcpcb_parts_database');
        $this->price($sourceBacked, $nepal, 13_000, 13_650, 'regional_bootstrap');
        $this->price($missingRegional, $global, 10, 10.5, 'jlcpcb_parts_database');
        $manual = $this->price($missingRegional, $nepal, 1_000, 1_500, 'manual_price_entry');

        $backup = storage_path('framework/testing-markup-backup');
        File::ensureDirectoryExists($backup);
        $service = app(ManagedCatalogMarkupService::class);
        $plan = $service->plan(20, 50);
        $result = $service->apply(20, $plan['plan_hash'], $backup, 50);

        $this->assertSame(3, $result['result']['rows_to_update']);
        $this->assertSame(0, $result['result']['rows_to_create']);
        $this->assertEqualsWithDelta(120, (float) MarketplaceProductPrice::where('product_id', $sourceBacked->id)->where('marketplace_id', $global->id)->value('base_price'), 0.00001);
        $this->assertEqualsWithDelta(15_600, (float) MarketplaceProductPrice::where('product_id', $sourceBacked->id)->where('marketplace_id', $nepal->id)->value('base_price'), 0.00001);
        $this->assertEqualsWithDelta(12, (float) MarketplaceProductPrice::where('product_id', $missingRegional->id)->where('marketplace_id', $global->id)->value('base_price'), 0.00001);
        $this->assertEqualsWithDelta(1_500, (float) $manual->fresh()->base_price, 0.00001);
    }

    public function test_it_creates_a_regional_row_only_when_no_manual_row_exists(): void
    {
        [$global, $nepal] = $this->marketplaces();
        $product = $this->product('managed-markup-create-regional');
        $this->price($product, $global, 10, 10.5, 'jlcpcb_parts_database');

        $backup = storage_path('framework/testing-markup-create-backup');
        File::ensureDirectoryExists($backup);
        $service = app(ManagedCatalogMarkupService::class);
        $plan = $service->plan(20, 50);
        $result = $service->apply(20, $plan['plan_hash'], $backup, 50);

        $this->assertSame(1, $result['result']['rows_to_create']);
        $regional = MarketplaceProductPrice::where('product_id', $product->id)->where('marketplace_id', $nepal->id)->firstOrFail();
        $this->assertSame('regional_markup_plan', $regional->source_name);
        $this->assertEqualsWithDelta(1_300, (float) $regional->cost_price, 0.00001);
        $this->assertEqualsWithDelta(1_560, (float) $regional->base_price, 0.00001);
    }

    private function marketplaces(): array
    {
        $country = Country::firstOrCreate(['iso_code_2' => 'NP'], ['name' => 'Nepal', 'iso_code_3' => 'NPL', 'is_active' => true]);
        $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1]);
        $npr = Currency::firstOrCreate(['code' => 'NPR'], ['name' => 'Nepalese Rupee', 'symbol' => 'Rs', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 130]);
        $global = Marketplace::create(['name' => 'Global', 'code' => 'GLOBAL', 'country_id' => $country->id, 'currency_id' => $usd->id, 'timezone' => 'UTC', 'locale' => 'en', 'is_active' => true, 'global_fallback' => true]);
        $nepal = Marketplace::create(['name' => 'Nepal', 'code' => 'NEPAL', 'country_id' => $country->id, 'currency_id' => $npr->id, 'timezone' => 'Asia/Kathmandu', 'locale' => 'en', 'is_active' => true]);

        return [$global, $nepal];
    }

    private function product(string $suffix): Product
    {
        return Product::create(['name' => $suffix, 'slug' => $suffix, 'sku' => 'NG-'.strtoupper($suffix), 'status' => 'active']);
    }

    private function price(Product $product, Marketplace $marketplace, float $cost, float $price, string $source): MarketplaceProductPrice
    {
        return MarketplaceProductPrice::create([
            'product_id' => $product->id,
            'marketplace_id' => $marketplace->id,
            'cost_price' => $cost,
            'base_price' => $price,
            'currency_code' => $marketplace->currency->code,
            'is_active' => true,
            'source_name' => $source,
            'pricing_rule' => str_contains($source, 'jlcpcb') ? 'source_cost_plus_5_percent_exact' : null,
        ]);
    }
}
