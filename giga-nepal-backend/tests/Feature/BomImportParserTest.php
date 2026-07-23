<?php

namespace Tests\Feature;

use App\Services\Bom\BomImportParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomImportParserTest extends TestCase
{
    use RefreshDatabase;

    private BomImportParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = app(BomImportParser::class);
    }

    public function test_parse_csv_with_header(): void
    {
        $csv = "MPN,Manufacturer,Description,Quantity\nSTM32F103C8T6,STMicroelectronics,ARM MCU,10\nLM358,Texas Instruments,Op-Amp,5";

        $result = $this->parser->parse($csv);

        $this->assertCount(2, $result['lines']);
        $this->assertTrue($result['has_header']);
        $this->assertEquals('STM32F103C8T6', $result['lines'][0]['mpn']);
        $this->assertEquals('STMicroelectronics', $result['lines'][0]['manufacturer']);
        $this->assertEquals(10, $result['lines'][0]['quantity']);
    }

    public function test_parse_tsv(): void
    {
        $tsv = "MPN\tManufacturer\tQuantity\nSTM32F103\tSTMicro\t10\nLM358\tTI\t5";

        $result = $this->parser->parse($tsv);

        $this->assertCount(2, $result['lines']);
        $this->assertEquals('STM32F103', $result['lines'][0]['mpn']);
    }

    public function test_parse_without_header(): void
    {
        $csv = "STM32F103C8T6,10\nLM358,5";

        $result = $this->parser->parse($csv);

        $this->assertCount(2, $result['lines']);
        $this->assertEquals('STM32F103C8T6', $result['lines'][0]['mpn']);
        $this->assertEquals(10, $result['lines'][0]['quantity']);
    }

    public function test_parse_empty_content(): void
    {
        $result = $this->parser->parse('');

        $this->assertCount(0, $result['lines']);
    }

    public function test_parse_skips_empty_rows(): void
    {
        $csv = "MPN,Quantity\nSTM32F103,10\n\nLM358,5\n";

        $result = $this->parser->parse($csv);

        $this->assertCount(2, $result['lines']);
    }

    public function test_parse_various_header_aliases(): void
    {
        $csv = "Part Number,Qty,Component\nSTM32F103,10,MCU";

        $result = $this->parser->parse($csv);

        $this->assertCount(1, $result['lines']);
        $this->assertEquals('STM32F103', $result['lines'][0]['mpn']);
        $this->assertEquals(10, $result['lines'][0]['quantity']);
    }

    public function test_quantity_parsing(): void
    {
        $csv = "MPN,Qty\nR0402,100 pcs\nC0603,50x\nL0805,25";

        $result = $this->parser->parse($csv);

        $this->assertEquals(100, $result['lines'][0]['quantity']);
        $this->assertEquals(50, $result['lines'][1]['quantity']);
        $this->assertEquals(25, $result['lines'][2]['quantity']);
    }

    public function test_detect_format_csv(): void
    {
        $this->assertEquals('csv', $this->parser->detectFormat("a,b,c\n1,2,3"));
    }

    public function test_detect_format_tsv(): void
    {
        $this->assertEquals('tsv', $this->parser->detectFormat("a\tb\tc\n1\t2\t3"));
    }

    public function test_detect_format_json(): void
    {
        $this->assertEquals('json', $this->parser->detectFormat('[{"mpn":"STM32"}]'));
    }

    public function test_merge_duplicates(): void
    {
        $lines = [
            ['mpn' => 'STM32F103', 'quantity' => 10],
            ['mpn' => 'LM358', 'quantity' => 5],
            ['mpn' => 'STM32F103', 'quantity' => 20],
        ];

        $merged = $this->parser->mergeDuplicates($lines);

        $this->assertCount(2, $merged);
        $this->assertEquals(30, $merged[0]['quantity']); // 10 + 20
        $this->assertEquals(2, $merged[0]['duplicate_count']);
    }

    public function test_get_stats(): void
    {
        $parsed = [
            'lines' => [
                ['mpn' => 'STM32F103', 'manufacturer' => 'ST', 'quantity' => 10],
                ['mpn' => 'LM358', 'manufacturer' => null, 'quantity' => 5],
            ],
            'has_header' => true,
            'mapped' => ['mpn' => 0, 'manufacturer' => 1, 'quantity' => 2],
        ];

        $stats = $this->parser->getStats($parsed);

        $this->assertEquals(2, $stats['total_lines']);
        $this->assertEquals(2, $stats['with_mpn']);
        $this->assertEquals(1, $stats['with_manufacturer']);
        $this->assertTrue($stats['has_header']);
    }

    public function test_parse_pasted_table(): void
    {
        $pasted = "Part Number\tQty\tDescription\nSTM32F103\t10\tARM MCU\nLM358\t5\tOp-Amp";

        $result = $this->parser->parsePastedTable($pasted);

        $this->assertCount(2, $result['lines']);
    }

    public function test_parse_rows_directly(): void
    {
        $rows = [
            ['MPN', 'Qty', 'Description'],
            ['STM32F103', '10', 'ARM MCU'],
            ['LM358', '5', 'Op-Amp'],
        ];

        $result = $this->parser->parseRows($rows);

        $this->assertCount(2, $result['lines']);
        $this->assertTrue($result['has_header']);
    }
}
