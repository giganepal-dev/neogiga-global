<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductVariant;
use App\Services\Marketplace\ProductAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_variant_availability_never_uses_parent_product_stock(): void
    {
        $product = $this->product('variant-parent');
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'NG-VARIANT-001', 'name' => 'Variant', 'options' => ['package' => 'SMD'], 'price' => 10, 'is_active' => true]);
        $warehouse = $this->warehouse();
        $this->stock($product->id, $warehouse, 100, null);
        $this->stock($product->id, $warehouse, 3, $variant->id);

        $availability = app(ProductAvailabilityService::class)->forProduct($product, null, $variant->id);

        $this->assertSame(3, $availability['available_stock']);
        $this->assertSame(3, $availability['global_stock']);
    }

    public function test_marketplace_overlay_price_and_assigned_stock_take_precedence(): void
    {
        $marketplace = $this->marketplace();
        $product = $this->product('marketplace-price');
        $warehouse = $this->warehouse($marketplace->id, $marketplace->country_id);
        $this->stock($product->id, $warehouse, 12, null, $marketplace->id);
        DB::table('marketplace_product_prices')->insert(['product_id' => $product->id, 'marketplace_id' => $marketplace->id, 'base_price' => 25, 'sale_price' => 20, 'currency_code' => 'USD', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        $availability = app(ProductAvailabilityService::class)->forProduct($product, $marketplace);

        $this->assertSame(12, $availability['available_stock']);
        $this->assertSame(12, $availability['local_stock']);
        $this->assertSame('marketplace_product_prices', $availability['price_source']);
        $this->assertSame(20.0, $availability['price']['selling_price']);
    }

    public function test_public_availability_endpoint_returns_a_compact_resolved_payload(): void
    {
        $product = $this->product('availability-api');
        $warehouse = $this->warehouse();
        $this->stock($product->id, $warehouse, 8);

        $this->getJson('/api/v1/products/'.$product->slug.'/availability')
            ->assertOk()
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.available_stock', 8)
            ->assertJsonPath('data.can_purchase', true)
            ->assertJsonStructure(['data' => ['currency', 'price', 'price_source', 'local_stock', 'regional_stock', 'global_stock', 'available_stock', 'can_purchase']]);
    }

    private function product(string $slug): Product
    {
        $id = DB::table('products')->insertGetId(['name' => ucfirst(str_replace('-', ' ', $slug)), 'slug' => $slug, 'sku' => 'NG-'.strtoupper(str_replace('-', '', $slug)), 'type' => 'simple', 'status' => 'approved', 'base_price' => 10, 'created_at' => now(), 'updated_at' => now()]);

        return Product::findOrFail($id);
    }

    private function marketplace(): Marketplace
    {
        $country = Country::create(['name' => 'Availability Country', 'iso_code_2' => 'AC', 'iso_code_3' => 'AVC', 'is_active' => true]);
        $currency = Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'is_active' => true]);

        return Marketplace::create(['name' => 'Availability Marketplace', 'code' => 'availability', 'country_id' => $country->id, 'currency_id' => $currency->id, 'is_active' => true]);
    }

    private function warehouse(?int $marketplaceId = null, ?int $countryId = null): int
    {
        return DB::table('warehouses')->insertGetId(['marketplace_id' => $marketplaceId, 'country_id' => $countryId, 'name' => 'Availability Warehouse', 'code' => 'NG-AVAIL-'.uniqid(), 'address_line1' => 'Warehouse Road', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function stock(int $productId, int $warehouseId, int $quantity, ?int $variantId = null, ?int $marketplaceId = null): void
    {
        DB::table('inventory_stocks')->insert(['product_id' => $productId, 'variant_id' => $variantId, 'warehouse_id' => $warehouseId, 'marketplace_id' => $marketplaceId, 'sku' => 'NG-STOCK-'.uniqid(), 'quantity_available' => $quantity, 'quantity_reserved' => 0, 'quantity_damaged' => 0, 'quantity_incoming' => 0, 'reorder_point' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }
}
