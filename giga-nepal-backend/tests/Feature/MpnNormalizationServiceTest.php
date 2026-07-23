<?php

namespace Tests\Feature;

use App\Services\Product\MpnNormalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpnNormalizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private MpnNormalizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MpnNormalizationService::class);
    }

    public function test_normalize_basic_mpn(): void
    {
        $result = $this->service->normalize('STM32F103C8T6');

        $this->assertEquals('STM32F103C8T6', $result['normalized']);
        $this->assertEquals('STM32F103C8T6', $result['raw']);
        $this->assertEmpty($result['warnings']);
    }

    public function test_normalize_lowercase(): void
    {
        $result = $this->service->normalize('stm32f103c8t6');

        $this->assertEquals('STM32F103C8T6', $result['normalized']);
    }

    public function test_normalize_with_spaces(): void
    {
        $result = $this->service->normalize('STM 32F 103');

        $this->assertEquals('STM32F103', $result['normalized']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_normalize_with_quotes(): void
    {
        $result = $this->service->normalize('"NE555P"');

        $this->assertEquals('NE555P', $result['normalized']);
    }

    public function test_normalize_empty(): void
    {
        $result = $this->service->normalize('');

        $this->assertEquals('', $result['normalized']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_normalize_null(): void
    {
        $result = $this->service->normalize(null);

        $this->assertEquals('', $result['normalized']);
    }

    public function test_detect_manufacturer_stm32(): void
    {
        $manufacturer = $this->service->detectManufacturer('STM32F103C8T6');

        $this->assertEquals('STMicroelectronics', $manufacturer);
    }

    public function test_detect_manufacturer_lm(): void
    {
        $manufacturer = $this->service->detectManufacturer('LM358');

        $this->assertEquals('Texas Instruments', $manufacturer);
    }

    public function test_detect_manufacturer_esp32(): void
    {
        $manufacturer = $this->service->detectManufacturer('ESP32-WROOM-32E');

        $this->assertEquals('Espressif Systems', $manufacturer);
    }

    public function test_detect_manufacturer_unknown(): void
    {
        $manufacturer = $this->service->detectManufacturer('UNKNOWN_PART_123');

        $this->assertNull($manufacturer);
    }

    public function test_resolve_manufacturer_alias(): void
    {
        $resolved = $this->service->resolveManufacturer('STM');

        $this->assertEquals('STMicroelectronics', $resolved);
    }

    public function test_resolve_manufacturer_full_name(): void
    {
        $resolved = $this->service->resolveManufacturer('Texas Instruments');

        $this->assertEquals('Texas Instruments', $resolved);
    }

    public function test_are_equivalent_same_mpn(): void
    {
        $this->assertTrue($this->service->areEquivalent('STM32F103', 'STM32F103'));
    }

    public function test_are_equivalent_different_case(): void
    {
        $this->assertTrue($this->service->areEquivalent('stm32f103', 'STM32F103'));
    }

    public function test_are_equivalent_same_suffix(): void
    {
        // Same MPN with different case should be equivalent
        $this->assertTrue($this->service->areEquivalent('NE555P', 'ne555p'));
    }

    public function test_are_equivalent_different_mpn(): void
    {
        // Different MPNs should not be equivalent
        $this->assertFalse($this->service->areEquivalent('NE555P', 'LM358'));
    }

    public function test_search_variations(): void
    {
        $variations = $this->service->searchVariations('STM32F103C8T6');

        $this->assertContains('STM32F103C8T6', $variations);
        $this->assertGreaterThan(1, count($variations));
    }

    public function test_normalize_batch(): void
    {
        $mpns = ['STM32F103', 'lm358', 'ESP32'];
        $results = $this->service->normalizeBatch($mpns);

        $this->assertCount(3, $results);
        $this->assertEquals('STM32F103', $results[0]['normalized']);
        $this->assertEquals('LM358', $results[1]['normalized']);
        $this->assertEquals('ESP32', $results[2]['normalized']);
    }

    public function test_get_stats(): void
    {
        $mpns = ['STM32F103', 'lm358', '', 'ESP32'];
        $stats = $this->service->getStats($mpns);

        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(3, $stats['normalized']);
        $this->assertEquals(1, $stats['empty']);
        $this->assertEquals(3, $stats['unique']);
    }

    public function test_is_passive_component_resistor(): void
    {
        $this->assertTrue($this->service->isPassiveComponent('R0402'));
        $this->assertTrue($this->service->isPassiveComponent('ERJ-3GEJ'));
    }

    public function test_is_passive_component_capacitor(): void
    {
        $this->assertTrue($this->service->isPassiveComponent('C0402'));
        $this->assertTrue($this->service->isPassiveComponent('GRM155R'));
    }

    public function test_is_passive_component_ic(): void
    {
        $this->assertFalse($this->service->isPassiveComponent('STM32F103'));
    }

    public function test_parse_passive_description_resistor(): void
    {
        $parsed = $this->service->parsePassiveDescription('10kΩ 1% 0402');

        $this->assertEquals(10000, $parsed['value']);
        $this->assertEquals('1%', $parsed['tolerance']);
        $this->assertEquals('0402', $parsed['package']);
    }

    public function test_parse_passive_description_capacitor(): void
    {
        $parsed = $this->service->parsePassiveDescription('100nF 25V 0603');

        $this->assertEquals('0603', $parsed['package']);
        $this->assertEquals('25V', $parsed['voltage']);
    }
}
