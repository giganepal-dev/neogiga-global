<?php

namespace Tests\Feature;

use App\Catalog\Ingestion\Persistence\CatalogDocumentStagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogDocumentStagingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_quotation_csv_stages_pending_provenance_without_commercial_or_media_changes(): void
    {
        Storage::fake('local');
        $csv = $this->csv('supplier-quotes.csv');

        $report = app(CatalogDocumentStagingService::class)->stage($csv, [
            'source_file' => 'catalog/staging/uploads/supplier-quotes.csv',
            'actor_id' => 123,
        ]);

        $this->assertSame('completed', $report['status']);
        $this->assertSame(1, $report['counters']['products_created']);
        $this->assertDatabaseHas('catalog_sources', [
            'code' => 'sunny_okystar_quotation_files',
            'source_type' => 'supplier_document',
            'status' => 'pending_manual_review',
            'import_enabled' => false,
            'media_download_enabled' => false,
        ]);
        $this->assertDatabaseHas('products', ['sku' => 'NG-SUNN-KS0001', 'status' => 'pending', 'mpn' => null, 'brand_id' => null]);
        $this->assertDatabaseHas('supplier_products', [
            'source_product_id' => 'KS0001',
            'supplier_sku' => 'KS0001',
            'source_price' => 7.2,
            'source_currency' => 'USD',
            'review_status' => 'pending_review',
        ]);
        $this->assertDatabaseHas('product_specification_values', ['original_label' => 'Operating voltage', 'original_value' => '3.3 V to 5 V']);
        $this->assertDatabaseHas('supplier_category_mappings', ['source_category_name' => 'Development boards', 'mapping_status' => 'pending_review']);
        $this->assertDatabaseCount('marketplace_product_prices', 0);
        $this->assertDatabaseCount('inventory_stocks', 0);
        $this->assertDatabaseCount('product_images', 0);
        $this->assertDatabaseCount('product_brands', 0);
    }

    public function test_staging_the_same_document_row_is_idempotent(): void
    {
        Storage::fake('local');
        $csv = $this->csv('supplier-quotes.csv');
        $staging = app(CatalogDocumentStagingService::class);

        $staging->stage($csv, ['source_file' => 'catalog/staging/uploads/supplier-quotes.csv']);
        $second = $staging->stage($csv, ['source_file' => 'catalog/staging/uploads/supplier-quotes.csv']);

        $this->assertSame(1, $second['counters']['products_unchanged']);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('supplier_products', 1);
        $this->assertDatabaseCount('catalog_import_items', 2);
    }

    public function test_dry_run_writes_a_report_without_creating_catalogue_rows(): void
    {
        Storage::fake('local');
        $csv = $this->csv('supplier-quotes.csv');

        $report = app(CatalogDocumentStagingService::class)->stage($csv, ['dry_run' => true]);

        $this->assertSame('completed', $report['status']);
        $this->assertSame(1, $report['counters']['products_queued_for_review']);
        $this->assertDatabaseCount('catalog_sources', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('supplier_products', 0);
        $this->assertNotEmpty(Storage::disk('local')->allFiles('catalog/reports'));
    }

    private function csv(string $filename): string
    {
        $content = implode("\n", [
            'supplier_sku,product_name,raw_specs,quoted_unit_price_usd,standard_unit_price_usd,quoted_quantity,category_hint,source_name,source_url,source_page_url,source_file',
            'KS0001,"UNO R3 compatible board","Operating voltage: 3.3 V to 5 V|Interface: USB",7.2,8.1,500,Development boards,Sunny quote,https://www.okystar.com/,,supplier-quotes.csv',
        ]);
        Storage::disk('local')->put("catalog/staging/{$filename}", $content);

        return Storage::disk('local')->path("catalog/staging/{$filename}");
    }
}
