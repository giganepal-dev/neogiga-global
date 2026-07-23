<?php

namespace Tests\Feature;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Services\Bom\BomComponentMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomComponentMatcherTest extends TestCase
{
    use RefreshDatabase;

    private BomComponentMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = app(BomComponentMatcher::class);
    }

    public function test_normalize_mpn_basic(): void
    {
        $this->assertEquals('STM32F103', BomComponentMatcher::normalizeMpn('STM32F103'));
    }

    public function test_normalize_mpn_lowercase(): void
    {
        $this->assertEquals('STM32F103', BomComponentMatcher::normalizeMpn('stm32f103'));
    }

    public function test_normalize_mpn_with_spaces(): void
    {
        $this->assertEquals('STM32F103', BomComponentMatcher::normalizeMpn('STM 32F 103'));
    }

    public function test_normalize_mpn_null(): void
    {
        $this->assertEquals('', BomComponentMatcher::normalizeMpn(null));
    }

    public function test_match_single_exact(): void
    {
        // Create a product
        $brand = ProductProductBrand::create(['name' => 'STMicroelectronics', 'slug' => 'stmicro']);
        $product = Product::create([
            'name' => 'STM32F103C8T6',
            'slug' => 'stm32f103c8t6',
            'sku' => 'STM32F103C8T6',
            'mpn' => 'STM32F103C8T6',
            'brand_id' => $brand->id,
            'status' => 'approved',
        ]);

        $result = $this->matcher->matchSingle('STM32F103C8T6');

        $this->assertEquals('exact', $result['match_status']);
        $this->assertEquals(100, $result['match_confidence']);
        $this->assertEquals($product->id, $result['matched_product_id']);
    }

    public function test_match_single_no_match(): void
    {
        $result = $this->matcher->matchSingle('UNKNOWN_PART_123');

        $this->assertEquals('none', $result['match_status']);
        $this->assertEquals(0, $result['match_confidence']);
        $this->assertNull($result['matched_product_id']);
    }

    public function test_match_batch(): void
    {
        // Create products
        $brand = ProductProductBrand::create(['name' => 'STMicroelectronics', 'slug' => 'stmicro']);
        Product::create([
            'name' => 'STM32F103',
            'slug' => 'stm32f103',
            'sku' => 'STM32F103',
            'mpn' => 'STM32F103',
            'brand_id' => $brand->id,
            'status' => 'approved',
        ]);

        $lines = [
            ['line_no' => 1, 'mpn' => 'STM32F103', 'manufacturer' => 'STMicroelectronics'],
            ['line_no' => 2, 'mpn' => 'UNKNOWN_PART', 'manufacturer' => null],
        ];

        $results = $this->matcher->match($lines);

        $this->assertCount(2, $results);
        $this->assertEquals('exact', $results[1]['match_status']);
        $this->assertEquals('none', $results[2]['match_status']);
    }

    public function test_match_with_manufacturer_disambiguation(): void
    {
        // Create two products with same MPN but different brands
        $brand1 = ProductBrand::create(['name' => 'STMicroelectronics', 'slug' => 'stmicro']);
        $brand2 = ProductBrand::create(['name' => 'Texas Instruments', 'slug' => 'ti']);

        Product::create([
            'name' => 'LM358 (ST)',
            'slug' => 'lm358-st',
            'sku' => 'LM358-ST',
            'mpn' => 'LM358',
            'brand_id' => $brand1->id,
            'status' => 'approved',
        ]);

        Product::create([
            'name' => 'LM358 (TI)',
            'slug' => 'lm358-ti',
            'sku' => 'LM358-TI',
            'mpn' => 'LM358',
            'brand_id' => $brand2->id,
            'status' => 'approved',
        ]);

        // Match with manufacturer hint
        $result = $this->matcher->matchSingle('LM358', 'Texas Instruments');

        $this->assertEquals('exact', $result['match_status']);
        $this->assertNotNull($result['matched_product_id']);
    }

    public function test_match_empty_mpn(): void
    {
        $result = $this->matcher->matchSingle('');

        $this->assertEquals('none', $result['match_status']);
    }

    public function test_match_batch_empty(): void
    {
        $results = $this->matcher->match([]);

        $this->assertCount(0, $results);
    }
}
