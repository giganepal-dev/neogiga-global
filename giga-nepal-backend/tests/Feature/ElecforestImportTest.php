<?php

namespace Tests\Feature;

use App\Jobs\CatalogImport\ImportElecforestProductJob;
use App\Jobs\CatalogImport\DownloadElecforestProductImageJob;
use App\Services\CatalogImport\Elecforest\ElecforestCategoryMapper;
use App\Services\CatalogImport\Elecforest\ElecforestIdentityResolver;
use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use App\Services\CatalogImport\Elecforest\ElecforestMediaImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ElecforestImportTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    public function test_audit_validates_jsonl_and_normalizes_invalid_sku_sentinel(): void
    {
        $file = $this->fixture([
            $this->row(['sku' => 'SKU:', 'product_name' => 'First Item', 'slug' => 'first-item', 'product_url' => 'https://elecforest.com/products/first-item']),
            $this->row(['sku' => 'EF-2', 'product_name' => 'Second Item', 'slug' => 'second-item', 'product_url' => 'https://elecforest.com/products/second-item']),
        ]);

        $audit = app(ElecforestImporter::class)->audit($file);

        $this->assertSame(2, $audit['lines']);
        $this->assertSame(2, $audit['valid']);
        $this->assertSame(0, $audit['malformed']);
        $this->assertSame(1, $audit['sku_coverage']);
        $this->assertSame(['groups' => 0, 'extra_records' => 0], $audit['duplicate_supplier_skus']);
    }

    public function test_dry_run_maps_records_without_catalog_or_source_writes(): void
    {
        $file = $this->fixture([$this->row()]);
        $beforeProducts = DB::table('products')->count();
        $beforeSources = DB::table('catalog_sources')->count();

        $result = app(ElecforestImporter::class)->importFile($file, ['dry_run' => true, 'limit' => 1]);

        $this->assertSame(1, $result['counters']['created']);
        $this->assertSame(0, $result['database_writes']);
        $this->assertSame($beforeProducts, DB::table('products')->count());
        $this->assertSame($beforeSources, DB::table('catalog_sources')->count());
    }

    public function test_malformed_line_is_recorded_without_stopping_the_stream(): void
    {
        $file = $this->fixtureRaw([
            '{not-json',
            json_encode($this->row(), JSON_UNESCAPED_SLASHES),
        ]);

        $result = app(ElecforestImporter::class)->importFile($file, ['sync' => true]);

        $this->assertSame(1, $result['counters']['created']);
        $this->assertSame(1, $result['counters']['rejected']);
        $this->assertDatabaseCount('catalog_import_failures', 1);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_resume_continues_after_the_last_processed_line(): void
    {
        $file = $this->fixture([
            $this->row(),
            $this->row(['product_url' => 'https://elecforest.com/products/resumed-item', 'slug' => 'resumed-item', 'sku' => 'EF-RESUME']),
        ]);
        $importer = app(ElecforestImporter::class);
        $first = $importer->importFile($file, ['sync' => true, 'limit' => 1]);

        $resumed = $importer->resume($first['run_id'], ['sync' => true, 'queue' => false, 'limit' => 1]);

        $this->assertSame(1, $first['counters']['created']);
        $this->assertSame(1, $resumed['counters']['created']);
        $this->assertDatabaseCount('products', 2);
        $this->assertSame(2, (int) DB::table('catalog_import_runs')->where('id', $first['run_id'])->value('last_line'));
    }

    public function test_real_import_creates_draft_with_provenance_seo_and_isolated_supplier_offer(): void
    {
        $file = $this->fixture([$this->row()]);
        $importer = app(ElecforestImporter::class);

        $result = $importer->importFile($file, ['sync' => true, 'generate_seo' => true]);
        $product = DB::table('products')->where('sku', 'NG-EF-EF-100')->first();
        $source = DB::table('supplier_products')->where('product_id', $product->id)->first();
        $seo = DB::table('product_seo_meta')->where('product_id', $product->id)->first();
        $content = DB::table('product_content_versions')->where('product_id', $product->id)->first();

        $this->assertSame(1, $result['counters']['created']);
        $this->assertSame('draft', $product->status);
        $this->assertSame('hidden', $product->visibility_status);
        $this->assertEquals(0, $product->base_price);
        $this->assertEquals(0, $product->stock_quantity);
        $this->assertSame('1.250000', number_format((float) $source->source_price, 6, '.', ''));
        $this->assertSame('InStock', $source->source_stock_state);
        $this->assertSame('noindex,nofollow', $seo->robots);
        $this->assertNotEmpty($seo->canonical_url);
        $this->assertNotEmpty($seo->og_title);
        $this->assertNotEmpty($seo->twitter_title);
        $this->assertNotEmpty($seo->breadcrumb_schema);
        $this->assertNotEmpty($seo->product_schema);
        $this->assertGreaterThanOrEqual(8, count(array_filter(explode(', ', (string) $seo->meta_keywords))));
        $this->assertLessThanOrEqual(15, count(array_filter(explode(', ', (string) $seo->meta_keywords))));
        $this->assertGreaterThanOrEqual(140, mb_strlen((string) $seo->meta_description));
        $this->assertLessThanOrEqual(160, mb_strlen((string) $seo->meta_description));
        $this->assertStringContainsString('Advisory only', $seo->advisory_disclaimer);
        $this->assertSame('deterministic_assisted', $content->content_method);
        $this->assertStringContainsString('NeoGiga availability', $product->description);
        $this->assertStringContainsString('Advisory only', $product->description);
        $this->assertNotEmpty($content->source_notes);
        $this->assertNotEmpty($content->confidence_level);
        $this->assertNotEmpty($content->last_updated);
        $this->assertDatabaseCount('inventory_stocks', 0);
        $this->assertDatabaseCount('marketplace_product_prices', 0);
        $this->assertDatabaseCount('vendor_product_prices', 0);
        $this->assertDatabaseCount('product_country_prices', 0);
        $this->assertGreaterThan(0, DB::table('product_source_specifications')->where('product_id', $product->id)->count());
        $this->assertSame(2, DB::table('product_applications')->where('product_id', $product->id)->count());
    }

    public function test_rerun_is_idempotent_for_products_source_links_offers_and_content_versions(): void
    {
        $file = $this->fixture([$this->row()]);
        $importer = app(ElecforestImporter::class);
        $first = $importer->importFile($file, ['sync' => true]);
        $second = $importer->importFile($file, ['sync' => true, 'update_existing' => true]);

        $sourceId = DB::table('catalog_sources')->where('code', 'elecforest')->value('id');
        $this->assertSame(1, $first['counters']['created']);
        $this->assertSame(1, $second['counters']['unchanged']);
        $this->assertSame(1, DB::table('supplier_products')->where('catalog_source_id', $sourceId)->count());
        $this->assertSame(1, DB::table('catalog_product_sources')->where('source_id', $sourceId)->count());
        $this->assertSame(1, DB::table('supplier_product_offers')->count());
        $this->assertSame(1, DB::table('product_content_versions')->count());
        $this->assertSame(1, DB::table('products')->where('sku', 'NG-EF-EF-100')->count());
    }

    public function test_duplicate_supplier_sku_and_same_product_name_never_merge_records(): void
    {
        $file = $this->fixture([
            $this->row(['product_url' => 'https://elecforest.com/products/shared-name-one', 'slug' => 'shared-name-one', 'product_name' => 'Shared Product Name', 'sku' => 'COLLIDE-1']),
            $this->row(['product_url' => 'https://elecforest.com/products/shared-name-two', 'slug' => 'shared-name-two', 'product_name' => 'Shared Product Name', 'sku' => 'COLLIDE-1']),
        ]);

        $result = app(ElecforestImporter::class)->importFile($file, ['sync' => true]);
        $sourceId = DB::table('catalog_sources')->where('code', 'elecforest')->value('id');
        $productIds = DB::table('supplier_products')->where('catalog_source_id', $sourceId)->pluck('product_id');

        $this->assertSame(2, $result['counters']['created']);
        $this->assertCount(2, $productIds->unique());
        $this->assertSame(2, DB::table('products')->whereIn('id', $productIds)->distinct()->count('sku'));
        $this->assertSame(2, DB::table('products')->whereIn('id', $productIds)->distinct()->count('slug'));
        $this->assertSame(2, DB::table('product_identifiers')->whereIn('product_id', $productIds)->where('identifier_type', 'supplier_sku')->count());
    }

    public function test_numeric_duplicate_supplier_skus_are_treated_as_ambiguous_and_never_merge(): void
    {
        $file = $this->fixture([
            $this->row(['product_url' => 'https://elecforest.com/products/numeric-one', 'slug' => 'numeric-one', 'product_name' => 'Numeric One', 'sku' => '3593353']),
            $this->row(['product_url' => 'https://elecforest.com/products/numeric-two', 'slug' => 'numeric-two', 'product_name' => 'Numeric Two', 'sku' => '3593353']),
        ]);

        $result = app(ElecforestImporter::class)->importFile($file, ['sync' => true]);
        $sourceId = DB::table('catalog_sources')->where('code', 'elecforest')->value('id');

        $this->assertSame(2, $result['counters']['created']);
        $this->assertSame(2, DB::table('supplier_products')->where('catalog_source_id', $sourceId)->distinct()->count('product_id'));
        $this->assertSame(2, DB::table('catalog_product_sources')->where('source_id', $sourceId)->count());
    }

    public function test_missing_raw_product_url_is_recovered_from_source_slug_with_audit_provenance(): void
    {
        $file = $this->fixture([$this->row(['product_url' => '', 'slug' => 'recoverable-source-slug'])]);
        $importer = app(ElecforestImporter::class);

        $audit = $importer->audit($file);
        $result = $importer->importFile($file, ['sync' => true]);
        $source = DB::table('supplier_products')->first();

        $this->assertSame(0, $audit['url_coverage']);
        $this->assertSame(1, $audit['normalized_url_coverage']);
        $this->assertSame(1, $audit['derived_url_count']);
        $this->assertSame(1, $result['counters']['created']);
        $this->assertSame('https://elecforest.com/products/recoverable-source-slug', $source->source_url);
    }

    public function test_unique_supplier_sku_and_exact_source_url_match_without_name_matching(): void
    {
        $firstFile = $this->fixture([$this->row()]);
        $skuMatchFile = $this->fixture([$this->row([
            'product_name' => 'Renamed Source Listing', 'slug' => 'renamed-source-listing',
            'product_url' => 'https://elecforest.com/products/renamed-source-listing',
        ])]);
        $urlMatchFile = $this->fixture([$this->row([
            'product_name' => 'Another Name', 'slug' => 'another-source-id', 'sku' => 'DIFFERENT-SKU',
        ])]);
        $importer = app(ElecforestImporter::class);

        $importer->importFile($firstFile, ['sync' => true]);
        $skuResult = $importer->importFile($skuMatchFile, ['sync' => true]);
        $urlResult = $importer->importFile($urlMatchFile, ['sync' => true]);

        $this->assertSame(0, $skuResult['counters']['created']);
        $this->assertSame(1, $skuResult['counters']['updated']);
        $this->assertSame(0, $urlResult['counters']['created']);
        $this->assertSame(1, $urlResult['counters']['updated']);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_verified_manufacturer_and_mpn_match_has_highest_identity_priority(): void
    {
        $manufacturerId = DB::table('manufacturers')->insertGetId([
            'name' => 'Example Semiconductor', 'slug' => 'example-semiconductor',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'name' => 'Existing MPN Product', 'slug' => 'existing-mpn-product', 'sku' => 'NG-MPN-1',
            'manufacturer_id' => $manufacturerId, 'mpn' => 'ABC-123', 'normalized_mpn' => 'ABC123',
            'status' => 'draft', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sourceId = DB::table('catalog_sources')->insertGetId([
            'code' => 'identity-test', 'name' => 'Identity Test', 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $identity = app(ElecforestIdentityResolver::class)->resolve([
            'source_product_id' => 'new-source-id', 'supplier_sku' => null, 'source_url' => '',
        ], ['id' => $manufacturerId, 'mpn' => 'ABC-123'], $sourceId);

        $this->assertSame($productId, $identity['product_id']);
        $this->assertSame('manufacturer_mpn', $identity['matched_by']);
        $this->assertFalse($identity['ambiguous']);
    }

    public function test_category_mapping_is_case_and_plural_insensitive(): void
    {
        $categoryId = DB::table('product_categories')->insertGetId([
            'name' => 'Sensors', 'slug' => 'sensors', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $mapped = app(ElecforestCategoryMapper::class)->resolve('sensor', '', 0, false);

        $this->assertSame($categoryId, $mapped['category_id']);
        $this->assertSame('auto_mapped', $mapped['status']);
    }

    public function test_collection_page_is_audited_and_rejected_from_canonical_products(): void
    {
        $file = $this->fixture([$this->row([
            'product_name' => 'All Products', 'slug' => 'products', 'sku' => '',
            'product_url' => 'https://elecforest.com/products',
        ])]);
        $before = DB::table('products')->count();

        $result = app(ElecforestImporter::class)->importFile($file, ['sync' => true]);

        $this->assertSame(1, $result['counters']['rejected']);
        $this->assertSame($before, DB::table('products')->count());
        $this->assertDatabaseCount('catalog_import_failures', 1);
        $this->assertStringContainsString('Collection page', DB::table('catalog_import_failures')->value('error_message'));
    }

    public function test_content_rewrite_removes_unsafe_html_and_supplier_calls_to_action(): void
    {
        $file = $this->fixture([$this->row([
            'product_name' => '<script>alert(1)</script> USB sensor module',
            'description' => '<style>body{display:none}</style><script>evil()</script>Supply voltage: 5 V. Buy now for free shipping.',
        ])]);

        app(ElecforestImporter::class)->importFile($file, ['sync' => true]);
        $product = DB::table('products')->first();

        $this->assertStringNotContainsString('<script', $product->name);
        $this->assertStringNotContainsString('<script', $product->description);
        $this->assertStringNotContainsString('Buy now', $product->description);
        $this->assertStringNotContainsString('free shipping', $product->description);
        $this->assertStringContainsString('Supply voltage', $product->description);
    }

    public function test_queue_mode_dispatches_bounded_record_jobs(): void
    {
        Bus::fake();
        $file = $this->fixture([
            $this->row(),
            $this->row(['product_url' => 'https://elecforest.com/products/second', 'slug' => 'second', 'sku' => 'EF-200']),
        ]);

        $result = app(ElecforestImporter::class)->queueFile($file, ['queue' => true, 'limit' => 2]);

        $this->assertSame(2, $result['queued']);
        $this->assertSame('queued', $result['status']);
        Bus::assertDispatchedTimes(ImportElecforestProductJob::class, 2);
    }

    public function test_media_importer_rejects_non_allowlisted_or_private_hosts_before_network_io(): void
    {
        $file = $this->fixture([$this->row()]);
        app(ElecforestImporter::class)->importFile($file, ['sync' => true]);
        $assetId = DB::table('supplier_product_assets')->value('id');
        DB::table('supplier_product_assets')->where('id', $assetId)->update(['original_url' => 'https://127.0.0.1/internal.png']);

        try {
            app(ElecforestMediaImporter::class)->downloadAsset((int) $assetId);
            $this->fail('Private/non-allowlisted host should have been rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('allowlisted HTTPS', $exception->getMessage());
        }
        $this->assertSame('failed', DB::table('supplier_product_assets')->where('id', $assetId)->value('download_status'));
    }

    public function test_media_importer_ignores_known_shipping_and_payment_assets_before_network_io(): void
    {
        $file = $this->fixture([$this->row(['image_urls' => 'https://ueeshop.ly200-cdn.com/u_file/photo/logo-dhl.png'])]);
        app(ElecforestImporter::class)->importFile($file, ['sync' => true]);

        $this->assertDatabaseCount('supplier_product_assets', 0);
    }

    public function test_media_mime_validation_hash_deduplication_and_failed_retry_queueing(): void
    {
        Storage::fake('public');
        Bus::fake();
        config()->set('elecforest_import.min_image_dimension', 1);
        $file = $this->fixture([$this->row([
            'image_urls' => 'https://ueeshop.ly200-cdn.com/u_file/example/one.png | https://ueeshop.ly200-cdn.com/u_file/example/two.png',
        ])]);
        $importer = app(ElecforestImporter::class);
        $importer->importFile($file, ['sync' => true]);
        $assets = DB::table('supplier_product_assets')->orderBy('id')->get();

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        Http::fakeSequence()
            ->push('<html>not an image</html>', 200, ['Content-Type' => 'image/png'])
            ->push($png, 200, ['Content-Type' => 'image/png'])
            ->push($png, 200, ['Content-Type' => 'image/png']);
        try {
            app(ElecforestMediaImporter::class)->downloadAsset((int) $assets[0]->id);
            $this->fail('HTML masquerading as an image must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('MIME type', $exception->getMessage());
        }
        $this->assertSame('failed', DB::table('supplier_product_assets')->where('id', $assets[0]->id)->value('download_status'));

        $queued = $importer->downloadImages(1, false, true);
        $this->assertSame(1, $queued['processed']);
        Bus::assertDispatched(DownloadElecforestProductImageJob::class);

        app(ElecforestMediaImporter::class)->downloadAsset((int) $assets[0]->id);
        app(ElecforestMediaImporter::class)->downloadAsset((int) $assets[1]->id);

        $this->assertDatabaseCount('product_images', 1);
        $this->assertSame(1, DB::table('supplier_product_assets')->distinct()->count('checksum'));

        $image = DB::table('product_images')->first();
        $base = preg_replace('/\.[^.]+$/', '', $image->file_path);
        Storage::disk('public')->put($base.'-1w.webp', 'validated-webp');
        Storage::disk('public')->put($base.'-1w.avif', 'validated-avif');

        $derivatives = app(ElecforestMediaImporter::class)->generateDerivatives((int) $image->id);
        $metadata = json_decode((string) DB::table('product_images')->where('id', $image->id)->value('metadata'), true);

        $this->assertSame('reused', $derivatives['status']);
        $this->assertSame($base.'-1w.webp', $metadata['derivatives']['webp_1']);
        $this->assertSame($base.'-1w.avif', $metadata['derivatives']['avif_1']);
    }

    public function test_database_transaction_rolls_back_partial_product_when_source_link_conflicts(): void
    {
        $firstFile = $this->fixture([$this->row()]);
        $importer = app(ElecforestImporter::class);
        $importer->importFile($firstFile, ['sync' => true]);
        $sourceId = DB::table('catalog_sources')->where('code', 'elecforest')->value('id');
        $unrelatedProductId = DB::table('products')->insertGetId([
            'name' => 'Unrelated Existing Product', 'slug' => 'unrelated-existing-product', 'sku' => 'NG-UNRELATED',
            'status' => 'draft', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('catalog_product_sources')->insert([
            'source_id' => $sourceId, 'source_part_id' => 'reserved-source-part', 'product_id' => $unrelatedProductId,
            'source_url' => 'https://elecforest.com/products/reserved-by-existing-product',
            'source_payload_hash' => hash('sha256', 'reserved-source-part'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $before = DB::table('products')->count();
        $conflictingFile = $this->fixture([$this->row([
            'slug' => 'reserved-source-part', 'sku' => 'ROLLBACK-1',
            'product_url' => 'https://elecforest.com/products/new-conflicting-product',
        ])]);

        $result = $importer->importFile($conflictingFile, ['sync' => true]);

        $this->assertSame(1, $result['counters']['rejected']);
        $this->assertSame($before, DB::table('products')->count());
        $this->assertDatabaseMissing('products', ['sku' => 'NG-EF-ROLLBACK-1']);
        $this->assertDatabaseMissing('supplier_products', ['source_product_id' => 'reserved-source-part']);
    }

    public function test_unverified_records_cannot_publish_and_sitemap_scope_remains_draft(): void
    {
        $file = $this->fixture([$this->row()]);
        $importer = app(ElecforestImporter::class);
        $importer->importFile($file, ['sync' => true]);

        $result = $importer->publishQualified();
        $product = DB::table('products')->where('sku', 'NG-EF-EF-100')->first();

        $this->assertSame(0, $result['published']);
        $this->assertSame(1, $result['blocked']);
        $this->assertSame('draft', $product->status);
        $this->assertSame('hidden', $product->visibility_status);
        $this->assertSame('noindex,nofollow', DB::table('product_seo_meta')->where('product_id', $product->id)->value('robots'));
    }

    /** @param list<array<string, mixed>> $rows */
    private function fixture(array $rows): string
    {
        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $file = $directory.'/elecforest-'.bin2hex(random_bytes(8)).'.jsonl';
        file_put_contents($file, implode("\n", array_map(static fn (array $row): string => json_encode($row, JSON_UNESCAPED_SLASHES), $rows))."\n");
        $this->files[] = $file;

        return $file;
    }

    /** @param list<string> $lines */
    private function fixtureRaw(array $lines): string
    {
        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $file = $directory.'/elecforest-'.bin2hex(random_bytes(8)).'.jsonl';
        file_put_contents($file, implode("\n", $lines)."\n");
        $this->files[] = $file;

        return $file;
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function row(array $overrides = []): array
    {
        return array_merge([
            'breadcrumb' => '', 'compare_at_price' => '$1.50', 'currency' => 'USD',
            'description' => 'Supply voltage: 5 V. Output current: 2 A. Applications: prototyping, education.',
            'generated_tags' => 'Power | Module',
            'image_urls' => 'https://ueeshop.ly200-cdn.com/u_file/example/product.jpg',
            'main_category' => 'Modules', 'price' => '1.25', 'product_name' => '5V Power Control Module',
            'product_url' => 'https://elecforest.com/products/5v-power-control-module', 'quantity_text' => '12',
            'scraped_at_utc' => '2026-07-13T10:14:51Z', 'site_tags' => '', 'sku' => 'EF-100',
            'slug' => '5v-power-control-module', 'source_method' => 'pagination', 'stock_status' => 'InStock',
            'subcategory' => 'Boards', 'variants' => '',
        ], $overrides);
    }
}
