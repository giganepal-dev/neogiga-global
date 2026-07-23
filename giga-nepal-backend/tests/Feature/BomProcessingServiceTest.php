<?php

namespace Tests\Feature;

use App\Models\Bom\BomImport;
use App\Models\Bom\BomImportLine;
use App\Services\Bom\BomProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BomProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BomProcessingService::class);
    }

    public function test_process_text_basic(): void
    {
        $csv = "MPN,Quantity\nSTM32F103,10\nLM358,5";

        $result = $this->service->processText($csv, 1, ['name' => 'Test BOM']);

        $this->assertArrayHasKey('import_id', $result);
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(2, $result['stats']['total_lines']);
    }

    public function test_process_text_creates_import_record(): void
    {
        $csv = "MPN,Quantity\nSTM32F103,10";

        $result = $this->service->processText($csv, 1);

        $import = BomImport::find($result['import_id']);
        $this->assertNotNull($import);
        $this->assertEquals(1, $import->user_id);
        $this->assertEquals('completed', $import->status);
    }

    public function test_process_text_creates_import_lines(): void
    {
        $csv = "MPN,Quantity\nSTM32F103,10\nLM358,5\nESP32,3";

        $result = $this->service->processText($csv, 1);

        $lines = BomImportLine::where('bom_import_id', $result['import_id'])->get();
        $this->assertCount(3, $lines);
    }

    public function test_process_text_with_manufacturer(): void
    {
        $csv = "MPN,Manufacturer,Quantity\nSTM32F103,STMicroelectronics,10";

        $result = $this->service->processText($csv, 1);

        $line = BomImportLine::where('bom_import_id', $result['import_id'])->first();
        $this->assertEquals('STMicroelectronics', $line->manufacturer);
    }

    public function test_process_text_empty_content(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->processText('', 1);
    }

    public function test_process_text_merge_duplicates(): void
    {
        $csv = "MPN,Quantity\nSTM32F103,10\nSTM32F103,20\nLM358,5";

        $result = $this->service->processText($csv, 1, ['merge_duplicates' => true]);

        $lines = BomImportLine::where('bom_import_id', $result['import_id'])->get();
        $this->assertCount(2, $lines); // STM32F103 merged, LM358 separate
    }

    public function test_get_results(): void
    {
        $csv = "MPN,Quantity\nSTM32F103,10\nLM358,5";
        $result = $this->service->processText($csv, 1);

        $results = $this->service->getResults($result['import_id']);

        $this->assertArrayHasKey('import', $results);
        $this->assertArrayHasKey('lines', $results);
        $this->assertArrayHasKey('grouped', $results);
        $this->assertArrayHasKey('stats', $results);
    }

    public function test_get_rfq_ready_lines(): void
    {
        $csv = "MPN,Quantity\nSTM32F103,10\nLM358,5";
        $result = $this->service->processText($csv, 1);

        $rfqLines = $this->service->getRfqReadyLines($result['import_id']);

        $this->assertIsArray($rfqLines);
        // All lines are unmatched since no products exist
        $this->assertCount(2, $rfqLines);
    }

    public function test_get_cart_ready_lines(): void
    {
        $csv = "MPN,Quantity\nSTM32F103,10\nLM358,5";
        $result = $this->service->processText($csv, 1);

        $cartLines = $this->service->getCartReadyLines($result['import_id']);

        $this->assertIsArray($cartLines);
        // No cart-ready lines since no exact matches
        $this->assertCount(0, $cartLines);
    }

    public function test_process_text_stores_raw_content(): void
    {
        $csv = "MPN,Quantity\nSTM32F103,10";

        $result = $this->service->processText($csv, 1);

        $import = BomImport::find($result['import_id']);
        $this->assertEquals($csv, $import->raw_content);
    }

    public function test_process_text_with_currency(): void
    {
        $csv = "MPN,Quantity\nSTM32F103,10";

        $result = $this->service->processText($csv, 1, ['currency' => 'EUR']);

        $import = BomImport::find($result['import_id']);
        $this->assertEquals('EUR', $import->currency);
    }
}
