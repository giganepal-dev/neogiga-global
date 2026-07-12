<?php

namespace Tests\Feature;

use App\Catalog\Ingestion\Normalizers\CatalogNormalizer;
use App\Catalog\Ingestion\Parsers\JsonLdExtractor;
use App\Catalog\Ingestion\Persistence\CatalogImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogSupplierIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_dry_run_writes_a_report_without_catalogue_rows(): void
    {
        Storage::fake('local');
        config()->set('catalog_import.enabled', false);
        config()->set('catalog_import.suppliers.adafruit.enabled', false);

        $this->artisan('catalog:import', ['supplier' => 'adafruit', '--dry-run' => true])
            ->assertExitCode(1);

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('supplier_products', 0);
        $this->assertDatabaseCount('catalog_import_runs', 0);
        $this->assertNotEmpty(Storage::disk('local')->allFiles('catalog/reports'));
    }

    public function test_supplier_audit_records_pending_manual_review_without_enabling_imports(): void
    {
        Http::fake(['https://www.adafruit.com/robots.txt' => Http::response("User-agent: *\nAllow: /\nSitemap: https://www.adafruit.com/sitemap.xml\n", 200)]);

        $this->artisan('catalog:supplier-audit', ['supplier' => 'adafruit'])->assertExitCode(0);

        $this->assertDatabaseHas('catalog_sources', ['code' => 'adafruit', 'status' => 'pending_manual_review', 'import_enabled' => false]);
        $this->assertDatabaseHas('supplier_sources', ['name' => 'robots.txt', 'enabled' => false]);
    }

    public function test_normalizer_preserves_model_codes_and_normalizes_units(): void
    {
        $normalizer = app(CatalogNormalizer::class);
        $this->assertSame('RP2040-ZERO', $normalizer->mpn(' RP2040-Zero '));
        $value = $normalizer->specification('3.3 V to 5 V', 'volts');
        $this->assertSame('V', $value['unit']);
        $this->assertSame(3.3, $value['numeric_value']);
        $this->assertSame(5.0, $value['numeric_max_value']);
    }

    public function test_json_ld_fixture_extracts_a_product_without_live_http(): void
    {
        $html = '<script type="application/ld+json">{"@context":"https://schema.org","@type":"Product","name":"Test Sensor","sku":"S-1","mpn":"TS-01","brand":{"@type":"Brand","name":"Test Brand"}}</script>';
        $product = app(JsonLdExtractor::class)->product($html);

        $this->assertSame('Test Sensor', $product['name']);
        $this->assertSame('TS-01', $product['mpn']);
        $this->assertSame('Test Brand', $product['brand']['name']);
    }

    public function test_approved_fixture_source_persists_a_hidden_pending_product_with_provenance(): void
    {
        config()->set('catalog_import.enabled', true);
        config()->set('catalog_import.suppliers.adafruit.enabled', true);
        config()->set('catalog_import.suppliers.adafruit.sitemap_urls', ['https://fixtures.test/sitemap.xml']);
        DB::table('catalog_sources')->insert([
            'code' => 'adafruit', 'name' => 'Adafruit', 'source_url' => 'https://www.adafruit.com', 'active' => true,
            'import_enabled' => true, 'media_download_enabled' => false, 'description_reuse_status' => 'unknown', 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Http::fake([
            'https://fixtures.test/sitemap.xml' => Http::response('<urlset><url><loc>https://fixtures.test/products/test-sensor</loc></url></urlset>', 200, ['Content-Type' => 'application/xml']),
            'https://fixtures.test/products/test-sensor' => Http::response('<script type="application/ld+json">{"@context":"https://schema.org","@type":"Product","name":"Fixture Sensor","sku":"FS-1","mpn":"FS-100","brand":{"@type":"Brand","name":"Fixture Brand"},"url":"https://fixtures.test/products/test-sensor"}</script>', 200, ['Content-Type' => 'text/html']),
        ]);

        $report = app(CatalogImportService::class)->run('adafruit', ['limit' => 1]);

        $this->assertSame('completed', $report['status']);
        $this->assertDatabaseHas('products', ['sku' => 'NG-ADAF-FS-1', 'mpn' => 'FS-100', 'status' => 'pending']);
        if (Schema::hasColumn('products', 'approval_status')) {
            $this->assertDatabaseHas('products', ['sku' => 'NG-ADAF-FS-1', 'approval_status' => 'pending_review']);
        }
        if (Schema::hasColumn('products', 'visibility_status')) {
            $this->assertDatabaseHas('products', ['sku' => 'NG-ADAF-FS-1', 'visibility_status' => 'hidden']);
        }
        $this->assertDatabaseHas('supplier_products', ['source_product_id' => 'FS-1', 'review_status' => 'pending_review']);
        $this->assertDatabaseCount('catalog_import_runs', 1);
        $this->assertDatabaseHas('catalog_review_tasks', ['task_type' => 'supplier_product_review', 'status' => 'open']);
    }
}
