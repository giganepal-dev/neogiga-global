<?php

namespace Tests\Feature;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpnAutocompleteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_autocomplete_requires_query(): void
    {
        $response = $this->getJson('/api/v1/products/autocomplete');

        $response->assertStatus(422);
    }

    public function test_autocomplete_search(): void
    {
        // Create a product
        $brand = ProductBrand::create(['name' => 'STMicroelectronics', 'slug' => 'stmicro']);
        Product::create([
            'name' => 'STM32F103C8T6',
            'slug' => 'stm32f103c8t6',
            'sku' => 'STM32F103C8T6',
            'mpn' => 'STM32F103C8T6',
            'brand_id' => $brand->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/products/autocomplete?q=STM32');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'query' => 'STM32',
                ],
            ]);
    }

    public function test_normalize_mpn(): void
    {
        $response = $this->postJson('/api/v1/products/mpn/normalize', [
            'mpn' => 'stm32f103c8t6',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'normalized' => 'STM32F103C8T6',
                ],
            ]);
    }

    public function test_normalize_mpn_requires_mpn(): void
    {
        $response = $this->postJson('/api/v1/products/mpn/normalize', []);

        $response->assertStatus(422);
    }

    public function test_detect_manufacturer(): void
    {
        $response = $this->postJson('/api/v1/products/mpn/detect-manufacturer', [
            'mpn' => 'STM32F103',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'manufacturer' => 'STMicroelectronics',
                    'confidence' => 'high',
                ],
            ]);
    }

    public function test_resolve_manufacturer(): void
    {
        $response = $this->postJson('/api/v1/products/mpn/resolve-manufacturer', [
            'manufacturer' => 'STM',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'resolved' => 'STMicroelectronics',
                    'is_alias' => true,
                ],
            ]);
    }

    public function test_match_mpn(): void
    {
        $response = $this->postJson('/api/v1/products/mpn/match', [
            'mpn' => 'STM32F103',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'match_status' => 'none',
                ],
            ]);
    }

    public function test_parse_passive(): void
    {
        $response = $this->postJson('/api/v1/products/mpn/parse-passive', [
            'description' => '10kΩ 1% 0402',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'value' => 10000,
                    'tolerance' => '1%',
                    'package' => '0402',
                ],
            ]);
    }

    public function test_normalize_batch(): void
    {
        $response = $this->postJson('/api/v1/products/mpn/normalize-batch', [
            'mpns' => ['STM32F103', 'lm358', 'ESP32'],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'stats' => [
                        'total' => 3,
                        'normalized' => 3,
                    ],
                ],
            ]);
    }

    public function test_get_alternatives(): void
    {
        $brand = ProductBrand::create(['name' => 'STMicroelectronics', 'slug' => 'stmicro']);
        $product = Product::create([
            'name' => 'STM32F103',
            'slug' => 'stm32f103',
            'sku' => 'STM32F103',
            'mpn' => 'STM32F103',
            'brand_id' => $brand->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}/alternatives");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_store_alias_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/products/mpn/alias', [
            'product_id' => 1,
            'alias_mpn' => 'TEST_ALIAS',
        ]);

        $response->assertStatus(401);
    }

    public function test_store_alias(): void
    {
        $user = User::factory()->create();
        $brand = ProductBrand::create(['name' => 'STMicroelectronics', 'slug' => 'stmicro']);
        $product = Product::create([
            'name' => 'STM32F103',
            'slug' => 'stm32f103',
            'sku' => 'STM32F103',
            'mpn' => 'STM32F103',
            'brand_id' => $brand->id,
            'status' => 'approved',
        ]);

        // Create a token for the user
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/products/mpn/alias', [
                'product_id' => $product->id,
                'alias_mpn' => 'STM32F103-ALIAS',
                'alias_type' => 'cross_reference',
            ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'alias_mpn' => 'STM32F103-ALIAS',
                ],
            ]);
    }
}
