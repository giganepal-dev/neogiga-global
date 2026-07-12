<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegionalCommerceBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_regional_product_page_does_not_expose_foreign_price_or_stock(): void
    {
        $regional = $this->marketplace('regional-boundary.test', checkoutEnabled: false);
        $foreign = $this->marketplace('foreign-boundary.test', checkoutEnabled: true);
        $product = $this->product();

        $this->stock($product, $regional, 'Regional Warehouse', 7);
        $this->stock($product, $foreign, 'Foreign Warehouse', 99);
        DB::table('marketplace_product_prices')->insert([
            'product_id' => $product['id'],
            'marketplace_id' => $foreign['id'],
            'base_price' => 999.00,
            'currency_code' => $foreign['currency_code'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withServerVariables(['HTTP_HOST' => $regional['domain']])
            ->get('/en/products/'.$product['slug'])
            ->assertOk()
            ->assertSee('RFQ pricing')
            ->assertSee('Regional Warehouse')
            ->assertDontSee('Foreign Warehouse')
            ->assertDontSee('999.00');
    }

    public function test_checkout_disabled_marketplace_redirects_to_rfq_without_creating_an_order(): void
    {
        $regional = $this->marketplace('rfq-boundary.test', checkoutEnabled: false);
        $product = $this->product();
        DB::table('marketplace_product_prices')->insert([
            'product_id' => $product['id'],
            'marketplace_id' => $regional['id'],
            'base_price' => 25.00,
            'currency_code' => $regional['currency_code'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $server = ['HTTP_HOST' => $regional['domain']];
        $this->withServerVariables($server)
            ->post('/cart/items', ['product_id' => $product['id'], 'quantity' => 1])
            ->assertRedirect('/cart');

        $ordersBefore = DB::table('orders')->count();

        $this->withServerVariables($server)
            ->post('/checkout')
            ->assertRedirect('/rfq')
            ->assertSessionHas('error');

        $this->assertSame($ordersBefore, DB::table('orders')->count());
    }

    public function test_checkout_enabled_flag_still_requires_real_regional_commerce_data(): void
    {
        $regional = $this->marketplace('configured-flag-boundary.test', checkoutEnabled: true);
        $product = $this->product();
        DB::table('marketplace_product_prices')->insert([
            'product_id' => $product['id'],
            'marketplace_id' => $regional['id'],
            'base_price' => 25.00,
            'currency_code' => $regional['currency_code'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $server = ['HTTP_HOST' => $regional['domain']];
        $this->withServerVariables($server)
            ->post('/cart/items', ['product_id' => $product['id'], 'quantity' => 1])
            ->assertRedirect('/cart');

        $ordersBefore = DB::table('orders')->count();
        $this->withServerVariables($server)
            ->post('/checkout')
            ->assertRedirect('/rfq')
            ->assertSessionHas('error');

        $this->assertSame($ordersBefore, DB::table('orders')->count());
    }

    /** @return array{id:int,country_id:int,currency_code:string,domain:string} */
    private function marketplace(string $domain, bool $checkoutEnabled): array
    {
        $suffix = strtolower(Str::random(6));
        $currencyCode = strtoupper(substr($suffix, 0, 3));
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Boundary '.$suffix,
            'iso_code_2' => strtoupper(substr($suffix, 0, 2)),
            'iso_code_3' => $currencyCode,
            'currency_code' => $currencyCode,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $currencyId = DB::table('currencies')->insertGetId([
            'name' => 'Boundary Currency '.$suffix,
            'code' => $currencyCode,
            'symbol' => $currencyCode,
            'decimal_places' => 2,
            'is_active' => true,
            'exchange_rate' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $marketplaceId = DB::table('marketplaces')->insertGetId([
            'name' => 'Boundary Marketplace '.$suffix,
            'code' => 'boundary-'.$suffix,
            'country_id' => $countryId,
            'currency_id' => $currencyId,
            'timezone' => 'UTC',
            'locale' => 'en',
            'is_active' => true,
            'is_default' => false,
            'checkout_enabled' => $checkoutEnabled,
            'allow_vendor_registration' => true,
            'require_vendor_approval' => false,
            'tax_rate' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('marketplace_domains')->insert([
            'marketplace_id' => $marketplaceId,
            'domain' => $domain,
            'is_primary' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Cache::flush();

        return ['id' => $marketplaceId, 'country_id' => $countryId, 'currency_code' => $currencyCode, 'domain' => $domain];
    }

    /** @return array{id:int,slug:string,sku:string} */
    private function product(): array
    {
        $suffix = strtolower(Str::random(8));
        $slug = 'boundary-product-'.$suffix;
        $sku = 'NG-BOUNDARY-'.strtoupper($suffix);
        $id = DB::table('products')->insertGetId([
            'name' => 'Boundary Product '.$suffix,
            'slug' => $slug,
            'sku' => $sku,
            'type' => 'simple',
            'status' => 'approved',
            'base_price' => 20.00,
            'track_inventory' => true,
            'stock_quantity' => 100,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('id', 'slug', 'sku');
    }

    /** @param array{id:int,country_id:int,currency_code:string,domain:string} $marketplace @param array{id:int,slug:string,sku:string} $product */
    private function stock(array $product, array $marketplace, string $warehouseName, int $quantity): void
    {
        $warehouseId = DB::table('warehouses')->insertGetId([
            'marketplace_id' => $marketplace['id'],
            'country_id' => $marketplace['country_id'],
            'name' => $warehouseName,
            'code' => 'WH-'.strtoupper(Str::random(8)),
            'address_line1' => 'Boundary street',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_stocks')->insert([
            'product_id' => $product['id'],
            'warehouse_id' => $warehouseId,
            'marketplace_id' => $marketplace['id'],
            'sku' => $product['sku'],
            'quantity_available' => $quantity,
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
            'quantity_incoming' => 0,
            'reorder_point' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
