<?php

namespace Tests\Feature;

use App\Services\Catalog\DraftCatalogReleaseService;
use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DraftCatalogReleaseTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $fixtures = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        config()->set('catalog_release.reports.disk', 'local');
        $this->seedGlobalMarketplaceAndWarehouses();
    }

    protected function tearDown(): void
    {
        foreach ($this->fixtures as $fixture) {
            @unlink($fixture);
        }
        parent::tearDown();
    }

    public function test_dry_run_is_read_only_and_quarantines_the_template_sku(): void
    {
        $catalog = $this->importCatalog(true);
        $before = $this->mutationCounts();

        $plan = app(DraftCatalogReleaseService::class)->plan();

        $this->assertSame(2, $plan['summary']['draft_products_found']);
        $this->assertSame(1, $plan['summary']['eligible_products'], json_encode($plan['blocked'], JSON_UNESCAPED_SLASHES));
        $this->assertSame(1, $plan['summary']['quarantined_products']);
        $this->assertSame(0, $plan['summary']['blocked_products']);
        $this->assertSame(1, $plan['summary']['verified_real_images']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $plan['plan_hash']);
        $this->assertSame('NG-EF-', $plan['quarantined'][0]['sku']);
        $this->assertGreaterThan(0, $plan['summary']['open_review_tasks']);
        $this->assertArrayHasKey('media_rights_review', $plan['open_review_tasks_by_type']);
        $this->artisan('catalog:release-drafts')
            ->expectsOutputToContain('"eligible_products": 1')
            ->expectsOutputToContain('Dry run only: no database rows, media state, inventory, prices, reports or caches were changed.')
            ->assertSuccessful();
        $this->assertSame($before, $this->mutationCounts());
        $this->assertSame('draft', DB::table('products')->where('id', $catalog['product_id'])->value('status'));
        $this->assertSame([], Storage::disk('local')->allFiles('catalog-releases'));
    }

    public function test_apply_uses_exact_margin_verified_media_and_fixed_idempotent_stock_split(): void
    {
        $catalog = $this->importCatalog(true);
        $release = app(DraftCatalogReleaseService::class);
        $plan = $release->plan();
        $authorization = [
            'expected_count' => 1,
            'expected_plan_hash' => $plan['plan_hash'],
            'backup_reference' => 'test-backup-sha256:abc123',
            'acknowledge_media_publication_risk' => true,
            'chunk_size' => 1,
        ];

        $result = $release->apply($plan, $authorization);

        $this->assertSame('completed', $result['status']);
        $this->assertSame(1, $result['released_products']);
        $this->assertSame(4, $result['stock_rows_created']);
        $this->assertSame(4, $result['movement_rows_created']);
        $this->assertSame(1, $result['images_activated']);
        $this->assertTrue(Storage::disk('local')->exists($result['report_path']));

        $product = DB::table('products')->find($catalog['product_id']);
        $this->assertSame('approved', $product->status);
        $this->assertSame('public', $product->visibility_status);
        $this->assertSame('1.2500', number_format((float) $product->cost_price, 4, '.', ''));
        $this->assertSame('1.3125', number_format((float) $product->sale_price, 4, '.', ''));
        $this->assertSame('1.3125', number_format((float) $product->base_price, 4, '.', ''));
        $this->assertSame(10_000, (int) $product->stock_quantity);
        $this->assertTrue((bool) $product->track_inventory);

        $price = DB::table('marketplace_product_prices')->where('product_id', $product->id)->first();
        $this->assertSame('USD', $price->currency_code);
        $this->assertSame('1.2500', number_format((float) $price->cost_price, 4, '.', ''));
        $this->assertSame('1.3125', number_format((float) $price->sale_price, 4, '.', ''));
        $this->assertSame('source_cost_plus_5_percent_exact', $price->pricing_rule);
        $this->assertSame('ElecForest', $price->source_name);
        $this->assertNotEmpty($price->source_url);
        $this->assertNotEmpty($price->source_file);
        $this->assertNotEmpty($price->source_page_url);
        $this->assertNotEmpty($price->downloaded_at);
        $this->assertNotEmpty($price->imported_at);
        $this->assertNotEmpty($price->data_year);
        $this->assertNotEmpty($price->license_note);
        $this->assertNotEmpty($price->confidence_level);
        $this->assertNotEmpty($price->original_raw_value);
        $this->assertNotEmpty($price->normalized_value);

        $allocations = DB::table('inventory_stocks as stock')
            ->join('warehouses as warehouse', 'warehouse.id', '=', 'stock.warehouse_id')
            ->where('stock.product_id', $product->id)
            ->pluck('stock.quantity_available', 'warehouse.code')
            ->map(static fn ($quantity): int => (int) $quantity)
            ->all();
        $this->assertSame([
            'NG-SHENZHEN-CN' => 8_000,
            'NG-KATHMANDU-NP' => 667,
            'NG-NEWDELHI-IN' => 667,
            'NG-DUBAI-AE' => 666,
        ], $allocations);
        $this->assertSame(10_000, array_sum($allocations));
        $this->assertSame(0, DB::table('inventory_stocks as stock')
            ->join('warehouses as warehouse', 'warehouse.id', '=', 'stock.warehouse_id')
            ->where('stock.product_id', $product->id)
            ->where('warehouse.code', 'INV-POS-VERIFICATION')
            ->count());
        $this->assertSame(4, DB::table('inventory_movements')->where('product_id', $product->id)->distinct()->count('idempotency_key'));

        $image = DB::table('product_images')->where('id', $catalog['image_id'])->first();
        $this->assertTrue((bool) $image->is_active);
        $this->assertTrue((bool) $image->is_primary);
        foreach (['source_file', 'source_page_url', 'imported_at', 'data_year', 'license_note', 'confidence_level', 'original_raw_value', 'normalized_value'] as $field) {
            $this->assertNotEmpty($image->{$field}, "Missing image provenance {$field}");
        }
        $this->assertSame('pending_review', DB::table('supplier_product_assets')->where('id', $catalog['asset_id'])->value('rights_status'));
        $this->assertSame('pending rights review', $image->source_license);
        $this->assertSame(1, DB::table('catalog_review_tasks')->where('product_id', $product->id)->where('task_type', 'media_rights_review')->where('status', 'open')->count());
        $mediaEvidence = json_decode((string) DB::table('catalog_review_tasks')->where('product_id', $product->id)->where('task_type', 'media_rights_review')->value('evidence_json'), true);
        $this->assertTrue((bool) ($mediaEvidence['catalog_release']['operator_acknowledged_publication_risk'] ?? false));
        $this->assertFalse((bool) ($mediaEvidence['catalog_release']['license_independently_verified'] ?? true));
        $this->assertGreaterThan(0, DB::table('catalog_review_tasks')->where('product_id', $product->id)->where('task_type', '!=', 'media_rights_review')->where('status', 'open')->count());
        $this->assertSame('index,follow', DB::table('product_seo_meta')->where('product_id', $product->id)->value('robots'));

        $template = DB::table('products')->where('sku', 'NG-EF-')->first();
        $this->assertSame('draft', $template->status);
        $this->assertSame('hidden', $template->visibility_status);
        $this->assertSame(0, (int) $template->stock_quantity);
        $this->assertSame(0, DB::table('marketplace_product_prices')->where('product_id', $template->id)->count());

        $replay = $release->apply($plan, $authorization);
        $this->assertSame(0, $replay['released_products']);
        $this->assertSame(1, $replay['already_released_products']);
        $this->assertSame(1, DB::table('marketplace_product_prices')->where('product_id', $product->id)->count());
        $this->assertSame(4, DB::table('inventory_stocks')->where('product_id', $product->id)->count());
        $this->assertSame(4, DB::table('inventory_movements')->where('product_id', $product->id)->count());
        $this->assertSame(1, DB::table('catalog_change_events')->where('supplier_product_id', $catalog['supplier_product_id'])->where('event_type', 'catalog_release_available')->count());
    }

    public function test_apply_requires_all_authorization_gates_and_checksum_tampering_blocks_preflight(): void
    {
        $catalog = $this->importCatalog(false);
        $release = app(DraftCatalogReleaseService::class);
        $plan = $release->plan();

        try {
            $release->apply($plan, [
                'expected_count' => 1,
                'expected_plan_hash' => $plan['plan_hash'],
                'backup_reference' => '',
                'acknowledge_media_publication_risk' => true,
            ]);
            $this->fail('Apply should require a verified backup reference.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('backup reference', $exception->getMessage());
        }
        try {
            $release->apply($plan, [
                'expected_count' => 1,
                'expected_plan_hash' => $plan['plan_hash'],
                'backup_reference' => 'test-backup-sha256:abc123',
                'acknowledge_media_publication_risk' => false,
            ]);
            $this->fail('Apply should require explicit acknowledgement of unverified media-license publication risk.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('publication risk', $exception->getMessage());
        }
        $this->assertSame('draft', DB::table('products')->where('id', $catalog['product_id'])->value('status'));
        $this->assertDatabaseCount('marketplace_product_prices', 0);
        $this->assertDatabaseCount('inventory_stocks', 0);

        Storage::disk('public')->put($catalog['image_path'], 'not an image anymore');
        $tampered = $release->plan();
        $this->assertSame(0, $tampered['summary']['eligible_products']);
        $this->assertSame(1, $tampered['summary']['blocked_products']);
        $this->assertStringContainsString('No checksum-verified', implode(' ', $tampered['blocked'][0]['reasons']));
        $this->assertSame('draft', DB::table('products')->where('id', $catalog['product_id'])->value('status'));
        $this->assertDatabaseCount('marketplace_product_prices', 0);
        $this->assertDatabaseCount('inventory_stocks', 0);
    }

    /** @return array{product_id:int,supplier_product_id:int,asset_id:int,image_id:int,image_path:string} */
    private function importCatalog(bool $withTemplate): array
    {
        $rows = [$this->sourceRow()];
        if ($withTemplate) {
            $rows[] = $this->sourceRow([
                'product_name' => '模板',
                'slug' => 'template-2',
                'sku' => '!',
                'product_url' => 'https://elecforest.com/products/template-2',
                'image_urls' => '',
            ]);
        }
        $file = storage_path('framework/testing/catalog-release-'.bin2hex(random_bytes(8)).'.jsonl');
        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }
        file_put_contents($file, implode("\n", array_map(static fn (array $row): string => json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $rows))."\n");
        $this->fixtures[] = $file;
        app(ElecforestImporter::class)->importFile($file, ['sync' => true, 'generate_seo' => true]);

        $productId = (int) DB::table('products')->where('sku', 'NG-EF-EF-RELEASE-1')->value('id');
        $supplier = DB::table('supplier_products')->where('product_id', $productId)->first();
        $asset = DB::table('supplier_product_assets')->where('supplier_product_id', $supplier->id)->first();
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $this->assertIsString($bytes);
        $checksum = hash('sha256', $bytes);
        $path = 'products/elecforest/'.substr($checksum, 0, 2).'/'.$checksum.'.png';
        Storage::disk('public')->put($path, $bytes);
        DB::table('supplier_product_assets')->where('id', $asset->id)->update([
            'local_path' => $path,
            'mime_type' => 'image/png',
            'checksum' => $checksum,
            'size_bytes' => strlen($bytes),
            'rights_status' => 'pending_review',
            'download_status' => 'downloaded',
            'retrieved_at' => now(),
            'updated_at' => now(),
        ]);
        $imageId = (int) DB::table('product_images')->insertGetId([
            'product_id' => $productId,
            'file_path' => $path,
            'original_url' => $asset->original_url,
            'source_url' => $asset->original_url,
            'source_name' => 'ElecForest',
            'source_license' => 'pending rights review',
            'checksum' => $checksum,
            'file_name' => basename($path),
            'mime_type' => 'image/png',
            'file_size' => strlen($bytes),
            'width' => 1,
            'height' => 1,
            'storage_disk' => 'public',
            'sort_order' => 0,
            'is_primary' => false,
            'is_active' => false,
            'downloaded_at' => now(),
            'metadata' => json_encode(['source_notes' => 'Test supplier image held for review.']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'product_id' => $productId,
            'supplier_product_id' => (int) $supplier->id,
            'asset_id' => (int) $asset->id,
            'image_id' => $imageId,
            'image_path' => $path,
        ];
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function sourceRow(array $overrides = []): array
    {
        return array_merge([
            'breadcrumb' => '',
            'compare_at_price' => '$1.50',
            'currency' => 'USD',
            'description' => 'Supply voltage: 5 V. Output current: 2 A. Applications: prototyping and education.',
            'generated_tags' => 'Power | Module',
            'image_urls' => 'https://ueeshop.ly200-cdn.com/u_file/example/release-product.png',
            'main_category' => 'Modules',
            'price' => '1.25',
            'product_name' => 'Governed Release Module',
            'product_url' => 'https://elecforest.com/products/governed-release-module',
            'quantity_text' => '12',
            'scraped_at_utc' => '2026-07-13T10:14:51Z',
            'site_tags' => '',
            'sku' => 'EF-RELEASE-1',
            'slug' => 'governed-release-module',
            'source_method' => 'pagination',
            'stock_status' => 'InStock',
            'subcategory' => 'Boards',
            'variants' => '',
        ], $overrides);
    }

    private function seedGlobalMarketplaceAndWarehouses(): void
    {
        DB::table('product_categories')->insert([
            'name' => 'Development Boards',
            'slug' => 'development-boards',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $countries = [
            ['China', 'CN', 'CHN'],
            ['Nepal', 'NP', 'NPL'],
            ['India', 'IN', 'IND'],
            ['United Arab Emirates', 'AE', 'ARE'],
        ];
        $countryIds = [];
        foreach ($countries as [$name, $iso2, $iso3]) {
            $countryIds[$iso2] = DB::table('countries')->insertGetId([
                'name' => $name,
                'iso_code_2' => $iso2,
                'iso_code_3' => $iso3,
                'currency_code' => 'USD',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $currencyId = DB::table('currencies')->insertGetId([
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $marketplaceId = DB::table('marketplaces')->insertGetId([
            'name' => 'NeoGiga Global',
            'code' => 'GLOBAL',
            'country_id' => $countryIds['CN'],
            'currency_id' => $currencyId,
            'is_active' => true,
            'is_default' => true,
            'launch_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([
            ['Shenzhen Central', 'NG-SHENZHEN-CN', 'CN', []],
            ['Kathmandu Regional', 'NG-KATHMANDU-NP', 'NP', []],
            ['New Delhi Regional', 'NG-NEWDELHI-IN', 'IN', []],
            ['Dubai Regional', 'NG-DUBAI-AE', 'AE', []],
            ['Verification Only', 'INV-POS-VERIFICATION', 'CN', ['purpose' => 'phase_2_inventory_pos_verification']],
        ] as [$name, $code, $country, $metadata]) {
            DB::table('warehouses')->insert([
                'marketplace_id' => $marketplaceId,
                'name' => $name,
                'code' => $code,
                'country_id' => $countryIds[$country],
                'address_line1' => 'Test warehouse address',
                'is_active' => true,
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** @return array<string, int> */
    private function mutationCounts(): array
    {
        return [
            'prices' => DB::table('marketplace_product_prices')->count(),
            'stocks' => DB::table('inventory_stocks')->count(),
            'movements' => DB::table('inventory_movements')->count(),
            'events' => DB::table('catalog_change_events')->count(),
            'audits' => DB::table('audit_logs')->count(),
            'active_images' => DB::table('product_images')->where('is_active', true)->count(),
        ];
    }
}
