<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogGlobalCommerceSeedCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_seeds_source_backed_global_price_stock_warehouses_and_delivery_rules(): void
    {
        $this->seedPrerequisites();

        $this->artisan('catalog:seed-global-commerce', [
            '--apply' => true,
            '--regional-sample-size' => 1,
            '--regional-sample-quantity' => 2,
        ])->assertExitCode(0);

        $globalId = DB::table('marketplaces')->where('code', 'GLOBAL')->value('id');
        $productId = DB::table('products')->where('sku', 'NG-COMMERCE-TEST')->value('id');

        $this->assertDatabaseHas('marketplace_product_prices', [
            'product_id' => $productId,
            'marketplace_id' => $globalId,
            'cost_price' => 10,
            'base_price' => 10.5,
            'source_name' => 'jlcpcb_parts_database',
            'pricing_rule' => 'source_minimum_quantity_1_price_x_1_05',
        ]);
        $this->assertDatabaseHas('warehouses', ['code' => 'NG-SHENZHEN-CN', 'marketplace_id' => $globalId]);
        $this->assertDatabaseHas('inventory_stocks', ['product_id' => $productId, 'warehouse_id' => DB::table('warehouses')->where('code', 'NG-SHENZHEN-CN')->value('id'), 'quantity_available' => 10]);
        $this->assertDatabaseHas('inventory_stocks', ['product_id' => $productId, 'warehouse_id' => DB::table('warehouses')->where('code', 'NG-KATHMANDU-NP')->value('id'), 'quantity_available' => 2]);

        if (Schema::hasTable('delivery_zones')) {
            $this->assertDatabaseHas('delivery_zones', ['code' => 'GLOBAL-ECONOMY', 'base_fee' => 5, 'is_active' => true]);
            $this->assertDatabaseHas('delivery_zones', ['code' => 'INDIA-EXPRESS', 'base_fee' => 100, 'is_active' => true]);
            $this->assertDatabaseHas('delivery_zones', ['code' => 'NEPAL', 'base_fee' => 150, 'is_active' => true]);
        }
    }

    private function seedPrerequisites(): void
    {
        $usd = $this->currency('USD', 'US Dollar');
        $inr = $this->currency('INR', 'Indian Rupee');
        $npr = $this->currency('NPR', 'Nepalese Rupee');
        $globalCountry = $this->country('Global', 'GL', 'GLB', 'USD');
        $india = $this->country('India', 'IN', 'IND', 'INR');
        $nepal = $this->country('Nepal', 'NP', 'NPL', 'NPR');
        $this->marketplace('NeoGiga Global', 'GLOBAL', $globalCountry, $usd);
        $this->marketplace('NeoGiga India', 'INDIA', $india, $inr);
        $this->marketplace('GigaNepal', 'NEPAL', $nepal, $npr);

        $productId = DB::table('products')->insertGetId([
            'name' => 'Commerce Seed Product', 'slug' => 'commerce-seed-product', 'sku' => 'NG-COMMERCE-TEST',
            'type' => 'simple', 'status' => 'approved', 'base_price' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sourceId = DB::table('catalog_sources')->insertGetId([
            'code' => 'jlcpcb_parts_database', 'name' => 'JLCPCB/LCSC', 'source_url' => 'https://github.com/CDFER/jlcpcb-parts-database', 'license_notes' => 'MIT repository', 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertNotNull($sourceId);
        DB::table('catalog_distributor_offers')->insert([
            'product_id' => $productId, 'distributor' => 'LCSC/JLCPCB', 'sku' => 'C-TEST-1', 'currency' => 'USD',
            'price_breaks' => json_encode([['qFrom' => 1, 'qTo' => 9, 'price' => 10.0], ['qFrom' => 10, 'qTo' => null, 'price' => 9.0]]),
            'fetched_at' => now(), 'review_status' => 'pending_review', 'metadata' => json_encode(['source' => 'jlcpcb_parts_database']), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function currency(string $code, string $name): int
    {
        return DB::table('currencies')->insertGetId(['name' => $name, 'code' => $code, 'symbol' => $code, 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function country(string $name, string $iso2, string $iso3, string $currency): int
    {
        return DB::table('countries')->insertGetId(['name' => $name, 'iso_code_2' => $iso2, 'iso_code_3' => $iso3, 'currency_code' => $currency, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function marketplace(string $name, string $code, int $countryId, int $currencyId): void
    {
        DB::table('marketplaces')->insert(['name' => $name, 'code' => $code, 'country_id' => $countryId, 'currency_id' => $currencyId, 'timezone' => 'UTC', 'locale' => 'en', 'is_active' => true, 'is_default' => $code === 'GLOBAL', 'allow_vendor_registration' => true, 'require_vendor_approval' => false, 'tax_rate' => 0, 'created_at' => now(), 'updated_at' => now()]);
    }
}
