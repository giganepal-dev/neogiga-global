<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GoogleMerchantFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_emits_priced_products_with_valid_google_attributes_and_skips_unpriced(): void
    {
        $marketplace = $this->marketplace();

        $priced = $this->product('Priced Sensor');
        $this->imageFor($priced);
        $this->priceFor($priced, $marketplace, 12.34);

        // Published + imaged but with NO price → Google would reject it, so the
        // feed must skip it rather than emit a priceless item.
        $unpriced = $this->product('Unpriced Sensor');
        $this->imageFor($unpriced);

        $response = $this->get('/feeds/google-merchant.xml');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $body = $response->getContent();
        $this->assertNotFalse(simplexml_load_string($body), 'feed must be valid XML');
        $this->assertStringContainsString('xmlns:g="http://base.google.com/ns/1.0"', $body);

        // Priced product present with parity-correct, Google-valid attributes.
        $this->assertStringContainsString('<g:id>'.$priced->sku.'</g:id>', $body);
        $this->assertStringContainsString('<g:price>12.34 USD</g:price>', $body);
        $this->assertStringContainsString('/en/products/'.$priced->slug, $body);
        $this->assertStringContainsString('<g:condition>new</g:condition>', $body);
        $this->assertStringContainsString('<g:availability>out_of_stock</g:availability>', $body);
        // Has an MPN (helper sets MPN-100) so identifier_exists must NOT be emitted.
        $this->assertStringContainsString('<g:mpn>MPN-100</g:mpn>', $body);

        // Unpriced product must be absent.
        $this->assertStringNotContainsString('/en/products/'.$unpriced->slug, $body);
    }

    private function product(string $name): Product
    {
        $category = ProductCategory::firstOrCreate(['slug' => 'engineering-sensors'], [
            'name' => 'Engineering Sensors',
            'is_active' => true,
        ]);

        return Product::create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'sku' => 'NG-'.str($name)->slug('-')->upper()->toString(),
            'mpn' => 'MPN-100',
            'category_id' => $category->id,
            'description' => 'A complete published technical product for feed verification.',
            'status' => 'approved',
            'track_inventory' => true,
            'stock_quantity' => 0,
        ]);
    }

    private function imageFor(Product $product): void
    {
        ProductImage::create([
            'product_id' => $product->id,
            'file_path' => 'https://cdn.example.com/'.$product->slug.'.jpg',
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    private function priceFor(Product $product, Marketplace $marketplace, float $amount): void
    {
        DB::table('marketplace_product_prices')->insert([
            'product_id' => $product->id,
            'marketplace_id' => $marketplace->id,
            'base_price' => $amount,
            'sale_price' => null,
            'cost_price' => 0,
            'currency_code' => 'USD',
            'is_tax_inclusive' => false,
            'tax_rate' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function marketplace(): Marketplace
    {
        $country = Country::firstOrCreate(['iso_code_2' => 'US'], [
            'name' => 'United States',
            'iso_code_3' => 'USA',
            'is_active' => true,
        ]);
        $currency = Currency::firstOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'exchange_rate' => 1,
        ]);

        return Marketplace::create([
            'name' => 'Global',
            'code' => 'GLOBAL',
            'country_id' => $country->id,
            'currency_id' => $currency->id,
            'timezone' => 'UTC',
            'locale' => 'en',
            'is_active' => true,
            'is_visible' => true,
            'indexable' => true,
        ]);
    }
}
