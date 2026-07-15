<?php

namespace Tests\Feature;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use App\Services\Catalog\BrandVisibilityService;
use App\Services\Catalog\JlcpcbQualifiedPublicationService;
use App\Services\Product\ProductPublicationGate;
use App\Services\Seo\CatalogSeoTemplateService;
use App\Services\Seo\JlcpcbCatalogSeoGovernanceService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JlcpcbCatalogSeoGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private string $backupRoot;

    private int $sourceId;

    private int $globalMarketplaceId;

    private int $nepalMarketplaceId;

    private int $autoBrandId;

    private int $explicitBrandId;

    private int $parentCategoryId;

    private int $childCategoryId;

    private int $manualCategoryId;

    private int $missingSeoProductId;

    private int $incompleteSeoProductId;

    private int $manualSeoProductId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePublicationColumnsExist();
        $this->backupRoot = storage_path('framework/testing/jlcpcb-seo-governance-backups');
        File::ensureDirectoryExists($this->backupRoot);
        config()->set('jlcpcb_qualified_publication.backup_root', $this->backupRoot);
        $this->seedFixture();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupRoot);
        parent::tearDown();
    }

    public function test_dry_run_is_deterministic_and_scopes_missing_and_untouched_rows_only(): void
    {
        $service = app(JlcpcbCatalogSeoGovernanceService::class);
        $first = $service->plan();
        $second = $service->plan();

        $this->assertSame($first['plan_hash'], $second['plan_hash']);
        $this->assertSame(3, $first['approved_public_source_products']);
        $this->assertSame(2, $first['eligible_products']);
        $this->assertSame(['index,follow' => 1, 'noindex,follow' => 1], $first['product_robots']);
        $this->assertSame(1, $first['eligible_brands']);
        $this->assertSame(2, $first['eligible_categories']);
        $this->assertSame(1, $first['skipped']['brands']['explicit_override']);
        $this->assertSame(1, $first['skipped']['categories']['manual_or_locked']);

        $beforeBrand = DB::table('product_brands')->where('id', $this->autoBrandId)->value('seo_meta');
        $beforeCategory = DB::table('product_categories')->where('id', $this->childCategoryId)->value('seo_meta');
        $this->artisan('catalog:jlcpcb-govern-seo')
            ->expectsOutputToContain('"eligible_products": 2')
            ->expectsOutputToContain('Dry run only: no product SEO, brand SEO, category SEO, marketplace, route, or frontend state was changed.')
            ->assertSuccessful();

        $this->assertFalse(DB::table('product_seo_meta')->where('product_id', $this->missingSeoProductId)->exists());
        $this->assertSame($beforeBrand, DB::table('product_brands')->where('id', $this->autoBrandId)->value('seo_meta'));
        $this->assertSame($beforeCategory, DB::table('product_categories')->where('id', $this->childCategoryId)->value('seo_meta'));
    }

    public function test_apply_requires_confirmation_and_verified_backup_then_preserves_manual_data_and_localized_metadata(): void
    {
        $service = app(JlcpcbCatalogSeoGovernanceService::class);
        $plan = $service->plan();
        $backup = $this->verifiedBackupDirectory();
        $manualProductBefore = DB::table('product_seo_meta')->where('product_id', $this->manualSeoProductId)->first();
        $explicitBrandBefore = DB::table('product_brands')->where('id', $this->explicitBrandId)->first();
        $manualCategoryBefore = DB::table('product_categories')->where('id', $this->manualCategoryId)->first();

        $this->artisan('catalog:jlcpcb-govern-seo', [
            '--apply' => true,
            '--plan-hash' => $plan['plan_hash'],
            '--backup-dir' => $backup,
        ])->expectsOutputToContain('--apply requires --yes.')->assertFailed();

        $result = $service->apply($plan['plan_hash'], $backup, 2);
        $this->assertSame(2, $result['products_created']);
        $this->assertSame(1, $result['brands_transitioned']);
        $this->assertSame(2, $result['categories_transitioned']);

        $productSeo = DB::table('product_seo_meta')->where('product_id', $this->missingSeoProductId)->first();
        $this->assertNotNull($productSeo);
        $this->assertSame('catalog_seo_template', $this->decodedJson($productSeo->metadata)['source']);
        $this->assertSame('jlcpcb_missing_only', $this->decodedJson($productSeo->metadata)['generation_scope']);
        $this->assertSame('index,follow', $productSeo->robots);
        $this->assertNotEmpty($productSeo->source_notes);
        $this->assertNotEmpty($productSeo->confidence_level);
        $this->assertNotNull($productSeo->last_updated);
        $this->assertSame('Advisory only', $productSeo->advisory_disclaimer);
        $this->assertSame('noindex,follow', DB::table('product_seo_meta')
            ->where('product_id', $this->incompleteSeoProductId)->value('robots'));

        $brandMeta = $this->decodedJson(DB::table('product_brands')->where('id', $this->autoBrandId)->value('seo_meta'));
        $childMeta = $this->decodedJson(DB::table('product_categories')->where('id', $this->childCategoryId)->value('seo_meta'));
        $parentMeta = $this->decodedJson(DB::table('product_categories')->where('id', $this->parentCategoryId)->value('seo_meta'));
        foreach ([$brandMeta, $childMeta, $parentMeta] as $metadata) {
            $this->assertSame('catalog_seo_template', $metadata['source']);
            $this->assertSame('jlcpcb_parts_database', $metadata['previous_source']);
            $this->assertSame('index,follow', $metadata['robots']);
            $this->assertSame('Advisory only', $metadata['advisory_disclaimer']);
            $this->assertArrayHasKey('localized', $metadata);
            $this->assertArrayHasKey('keywords', $metadata);
        }
        $this->assertArrayHasKey('legacy_generated_fields', $brandMeta);
        $this->assertNull(DB::table('product_brands')->where('id', $this->autoBrandId)->value('seo_title'));
        $this->assertNull(DB::table('product_brands')->where('id', $this->autoBrandId)->value('seo_description'));

        $manualProductAfter = DB::table('product_seo_meta')->where('product_id', $this->manualSeoProductId)->first();
        $explicitBrandAfter = DB::table('product_brands')->where('id', $this->explicitBrandId)->first();
        $manualCategoryAfter = DB::table('product_categories')->where('id', $this->manualCategoryId)->first();
        $this->assertSame($manualProductBefore->metadata, $manualProductAfter->metadata);
        $this->assertSame($manualProductBefore->meta_title, $manualProductAfter->meta_title);
        $this->assertSame($explicitBrandBefore->seo_meta, $explicitBrandAfter->seo_meta);
        $this->assertSame($explicitBrandBefore->seo_title, $explicitBrandAfter->seo_title);
        $this->assertSame($manualCategoryBefore->seo_meta, $manualCategoryAfter->seo_meta);

        $global = Marketplace::with('country')->findOrFail($this->globalMarketplaceId);
        $nepal = Marketplace::with('country')->findOrFail($this->nepalMarketplaceId);
        $templates = app(CatalogSeoTemplateService::class);
        $brand = ProductBrand::findOrFail($this->autoBrandId);
        $category = ProductCategory::findOrFail($this->childCategoryId);
        $this->assertSame('index,follow', $templates->activeBrand($brand, $global)['robots']);
        $this->assertNotSame($templates->activeBrand($brand, $global)['canonical'], $templates->activeBrand($brand, $nepal)['canonical']);
        $this->assertSame('index,follow', $templates->activeCategory($category, $global)['robots']);
        $this->assertNotSame($templates->activeCategory($category, $global)['canonical'], $templates->activeCategory($category, $nepal)['canonical']);

        $this->assertSame(5, DB::table('catalog_seo_versions')->whereIn('entity_type', ['product', 'brand', 'category'])->count());
        $replay = $service->plan();
        $this->assertSame(0, $replay['eligible_products']);
        $this->assertSame(0, $replay['eligible_brands']);
        $this->assertSame(0, $replay['eligible_categories']);
    }

    public function test_product_plan_uses_a_read_only_bounded_manifest_without_returning_product_rows_inline(): void
    {
        $service = app(JlcpcbCatalogSeoGovernanceService::class);
        $plan = $service->plan();

        $this->assertSame('jlcpcb-catalog-seo-governance-v3', $plan['plan_version']);
        $this->assertSame('jlcpcb-catalog-seo-product-manifest-v1', $plan['product_manifest']['version']);
        $this->assertSame(2, $plan['product_manifest']['entry_count']);
        $this->assertSame($plan['eligible_digests']['product'], $plan['product_manifest']['sha256']);
        $this->assertFileExists($plan['product_manifest']['path']);
        $this->assertSame(0, fileperms($plan['product_manifest']['path']) & 0222);
        $this->assertLessThanOrEqual(2 * 65_536, $plan['product_manifest']['size_bytes']);
        $this->assertNotContains('product', array_column($plan['eligible_rows'], 'entity_type'));

        $lines = file($plan['product_manifest']['path'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertIsArray($lines);
        $entries = array_map(static fn (string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR), $lines);
        $this->assertSame([$this->missingSeoProductId, $this->incompleteSeoProductId], array_column($entries, 'entity_id'));
        $this->assertSame(64, strlen($entries[0]['current_hash']));
        $this->assertSame('Advisory only', $entries[0]['generated']['advisory_disclaimer']);

        $output = $service->forOutput($plan);
        $this->assertArrayNotHasKey('eligible_rows', $output);
        $this->assertArrayNotHasKey('path', $output['product_manifest']);
    }

    public function test_same_count_product_substitution_invalidates_the_exact_plan(): void
    {
        $service = app(JlcpcbCatalogSeoGovernanceService::class);
        $plan = $service->plan();
        DB::table('product_seo_meta')->insert([
            'product_id' => $this->missingSeoProductId,
            'meta_title' => 'Competing editor row',
            'active_source' => 'manual',
            'is_manual_override' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_seo_meta')->where('product_id', $this->manualSeoProductId)->delete();

        $replacement = $service->plan();
        $this->assertSame($plan['eligible_products'], $replacement['eligible_products']);
        $this->assertNotSame($plan['eligible_digests']['product'], $replacement['eligible_digests']['product']);
        $this->assertNotSame($plan['plan_hash'], $replacement['plan_hash']);

        try {
            $service->apply($plan['plan_hash'], $this->verifiedBackupDirectory(), 2);
            $this->fail('A same-count product substitution must invalidate the exact plan.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('does not match the current', $exception->getMessage());
        }

        $this->assertSame('Competing editor row', DB::table('product_seo_meta')->where('product_id', $this->missingSeoProductId)->value('meta_title'));
        $this->assertFalse(DB::table('product_seo_meta')->where('product_id', $this->incompleteSeoProductId)->exists());
        $this->assertSame(0, DB::table('catalog_seo_versions')->count());
    }

    public function test_competing_product_seo_insert_after_plan_verification_is_preserved_and_aborts_the_chunk(): void
    {
        DB::table('product_brands')->where('id', $this->autoBrandId)->update([
            'seo_meta' => $this->importerMeta(['manual_override' => true]),
        ]);
        DB::table('product_categories')->whereIn('id', [$this->parentCategoryId, $this->childCategoryId])->update([
            'seo_meta' => $this->importerMeta(['manual_override' => true]),
        ]);
        $service = $this->governanceWithAfterPlanVerified(function (): void {
            DB::table('product_seo_meta')->insert([
                'product_id' => $this->missingSeoProductId,
                'meta_title' => 'Concurrent editor title',
                'active_source' => 'manual',
                'is_manual_override' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
        $plan = $service->plan();
        $this->assertSame(0, $plan['eligible_brands']);
        $this->assertSame(0, $plan['eligible_categories']);

        try {
            $service->apply($plan['plan_hash'], $this->verifiedBackupDirectory(), 2);
            $this->fail('A concurrent product SEO insert must abort the exact manifest chunk.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('gained an SEO row after planning', $exception->getMessage());
        }

        $this->assertSame('Concurrent editor title', DB::table('product_seo_meta')->where('product_id', $this->missingSeoProductId)->value('meta_title'));
        $this->assertFalse(DB::table('product_seo_meta')->where('product_id', $this->incompleteSeoProductId)->exists());
        $this->assertSame(0, DB::table('catalog_seo_versions')->count());
    }

    public function test_brand_is_locked_and_re_evaluated_after_plan_verification_before_transition(): void
    {
        $manualMetadata = $this->importerMeta(['manual_override' => true, 'saved_via' => 'admin.web']);
        $service = $this->governanceWithAfterPlanVerified(function () use ($manualMetadata): void {
            DB::table('product_brands')->where('id', $this->autoBrandId)->update(['seo_meta' => $manualMetadata]);
        });
        $plan = $service->plan();

        try {
            $service->apply($plan['plan_hash'], $this->verifiedBackupDirectory(), 10);
            $this->fail('A brand mutated after plan verification must not be transitioned.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString("Brand {$this->autoBrandId} changed after planning", $exception->getMessage());
        }

        $this->assertSame($manualMetadata, DB::table('product_brands')->where('id', $this->autoBrandId)->value('seo_meta'));
        $this->assertSame(0, DB::table('catalog_seo_versions')->count());
        $this->assertFalse(DB::table('product_seo_meta')->where('product_id', $this->missingSeoProductId)->exists());
    }

    public function test_category_is_locked_and_re_evaluated_after_plan_verification_before_transition(): void
    {
        DB::table('product_brands')->where('id', $this->autoBrandId)->update([
            'seo_meta' => $this->importerMeta(['manual_override' => true]),
        ]);
        $manualMetadata = $this->importerMeta(['manual_override' => true, 'saved_via' => 'admin.web']);
        $service = $this->governanceWithAfterPlanVerified(function () use ($manualMetadata): void {
            DB::table('product_categories')->where('id', $this->parentCategoryId)->update(['seo_meta' => $manualMetadata]);
        });
        $plan = $service->plan();

        try {
            $service->apply($plan['plan_hash'], $this->verifiedBackupDirectory(), 10);
            $this->fail('A category mutated after plan verification must not be transitioned.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString("Category {$this->parentCategoryId} changed after planning", $exception->getMessage());
        }

        $this->assertSame($manualMetadata, DB::table('product_categories')->where('id', $this->parentCategoryId)->value('seo_meta'));
        $this->assertSame(0, DB::table('catalog_seo_versions')->count());
        $this->assertFalse(DB::table('product_seo_meta')->where('product_id', $this->missingSeoProductId)->exists());
    }

    public function test_product_seo_unique_guard_rejects_a_second_row_for_the_same_product(): void
    {
        $this->expectException(QueryException::class);
        DB::table('product_seo_meta')->insert([
            'product_id' => $this->manualSeoProductId,
            'meta_title' => 'Duplicate row that must be rejected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_product_seo_unique_guard_treats_null_product_as_the_same_scope(): void
    {
        DB::table('product_seo_meta')->insert([
            'product_id' => null,
            'meta_title' => 'Legacy unlinked row',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        DB::table('product_seo_meta')->insert([
            'product_id' => null,
            'meta_title' => 'Second unlinked row that must be rejected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_version_unique_guard_treats_null_marketplace_as_the_same_scope(): void
    {
        $row = [
            'entity_type' => 'product',
            'entity_id' => $this->missingSeoProductId,
            'marketplace_id' => null,
            'locale' => 'en',
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('catalog_seo_versions')->insert($row);

        $this->expectException(QueryException::class);
        DB::table('catalog_seo_versions')->insert($row);
    }

    private function governanceWithAfterPlanVerified(\Closure $hook): JlcpcbCatalogSeoGovernanceService
    {
        return new class(app(CatalogSeoTemplateService::class), app(ProductPublicationGate::class), app(JlcpcbQualifiedPublicationService::class), app(BrandVisibilityService::class), $hook) extends JlcpcbCatalogSeoGovernanceService
        {
            public function __construct(
                CatalogSeoTemplateService $templates,
                ProductPublicationGate $publicationGate,
                JlcpcbQualifiedPublicationService $backupVerifier,
                BrandVisibilityService $brands,
                private readonly \Closure $hook,
            ) {
                parent::__construct($templates, $publicationGate, $backupVerifier, $brands);
            }

            protected function afterPlanVerified(array $plan): void
            {
                ($this->hook)($plan);
            }
        };
    }

    private function seedFixture(): void
    {
        $globalCountry = DB::table('countries')->insertGetId([
            'name' => 'Global', 'iso_code_2' => 'GL', 'iso_code_3' => 'GLB', 'currency_code' => 'USD', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $nepalCountry = DB::table('countries')->insertGetId([
            'name' => 'Nepal', 'iso_code_2' => 'NP', 'iso_code_3' => 'NPL', 'currency_code' => 'NPR', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $usd = DB::table('currencies')->insertGetId([
            'name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$', 'is_active' => true, 'exchange_rate' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $npr = DB::table('currencies')->insertGetId([
            'name' => 'Nepalese Rupee', 'code' => 'NPR', 'symbol' => 'Rs', 'is_active' => true, 'exchange_rate' => 130,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->globalMarketplaceId = DB::table('marketplaces')->insertGetId([
            'name' => 'NeoGiga Global', 'code' => 'GLOBAL', 'country_id' => $globalCountry, 'currency_id' => $usd,
            'canonical_domain' => 'neogiga.com', 'timezone' => 'UTC', 'locale' => 'en', 'is_active' => true,
            'is_visible' => true, 'indexable' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->nepalMarketplaceId = DB::table('marketplaces')->insertGetId([
            'name' => 'NeoGiga Nepal', 'code' => 'NEPAL', 'country_id' => $nepalCountry, 'currency_id' => $npr,
            'canonical_domain' => 'np.neogiga.com', 'timezone' => 'Asia/Kathmandu', 'locale' => 'en', 'is_active' => true,
            'is_visible' => true, 'indexable' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->sourceId = DB::table('catalog_sources')->insertGetId([
            'code' => 'jlcpcb_parts_database', 'name' => 'JLCPCB parts database', 'source_url' => 'https://example.test/jlcpcb',
            'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->parentCategoryId = DB::table('product_categories')->insertGetId([
            'name' => 'Components', 'slug' => 'components', 'is_active' => true,
            'seo_meta' => $this->importerMeta([
                'title' => 'Components — NeoGiga',
                'description' => 'Shop Components on NeoGiga — genuine parts, regional stock, datasheets and engineering support.',
            ]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->childCategoryId = DB::table('product_categories')->insertGetId([
            'parent_id' => $this->parentCategoryId, 'name' => 'Integrated Circuits', 'slug' => 'integrated-circuits',
            'is_active' => true,
            'seo_meta' => $this->importerMeta([
                'title' => 'Integrated Circuits — NeoGiga',
                'description' => 'Shop Integrated Circuits on NeoGiga — genuine parts, regional stock, datasheets and engineering support.',
            ]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->manualCategoryId = DB::table('product_categories')->insertGetId([
            'name' => 'Manual Category', 'slug' => 'manual-category', 'is_active' => true,
            'seo_meta' => $this->importerMeta(['manual_override' => true]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->autoBrandId = DB::table('product_brands')->insertGetId([
            'name' => 'Auto Semiconductor', 'slug' => 'auto-semiconductor', 'is_active' => true, 'landing_page_enabled' => true,
            'seo_title' => 'Auto Semi Corp Products & Distributor — NeoGiga',
            'seo_description' => 'Genuine Auto Semi Corp components on NeoGiga — datasheets, regional stock, pricing and RFQ across our global marketplace.',
            'seo_meta' => $this->importerMeta(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->explicitBrandId = DB::table('product_brands')->insertGetId([
            'name' => 'Editor Semiconductor', 'slug' => 'editor-semiconductor', 'is_active' => true, 'landing_page_enabled' => true,
            'seo_title' => 'Editor-approved brand title', 'seo_meta' => $this->importerMeta(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->missingSeoProductId = $this->product('Auto governed part', 'auto-governed-part', 'NG-AUTO-SEO', $this->autoBrandId, $this->childCategoryId);
        $this->incompleteSeoProductId = $this->product(
            'Incomplete but governed part',
            'incomplete-governed-part',
            'NG-INCOMPLETE-SEO',
            $this->autoBrandId,
            $this->childCategoryId,
            '',
        );
        $this->manualSeoProductId = $this->product('Manual SEO part', 'manual-seo-part', 'NG-MANUAL-SEO', $this->explicitBrandId, $this->manualCategoryId);
        DB::table('product_seo_meta')->insert([
            'product_id' => $this->manualSeoProductId,
            'meta_title' => 'Editor-approved product title', 'meta_description' => 'Editor-approved product description.',
            'canonical_url' => 'https://editor.example/manual-seo-part', 'robots' => 'index,follow',
            'is_manual_override' => true, 'is_locked' => true, 'active_source' => 'manual',
            'confidence_level' => 'manual_admin_override',
            'metadata' => json_encode(['source' => 'manual_admin_override', 'saved_via' => 'admin.web']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensurePublicationColumnsExist(): void
    {
        if (! Schema::hasColumn('products', 'approval_status')) {
            Schema::table('products', fn (Blueprint $table) => $table->string('approval_status', 40)->default('draft')->index());
        }
        if (! Schema::hasColumn('products', 'visibility_status')) {
            Schema::table('products', fn (Blueprint $table) => $table->string('visibility_status', 40)->default('public')->index());
        }
    }

    private function product(
        string $name,
        string $slug,
        string $sku,
        int $brandId,
        int $categoryId,
        string $description = 'Complete published JLCPCB identity used for scoped SEO governance testing.',
    ): int {
        $id = DB::table('products')->insertGetId([
            'name' => $name, 'slug' => $slug, 'sku' => $sku, 'mpn' => $sku.'-MPN',
            'brand_id' => $brandId, 'category_id' => $categoryId, 'manufacturer_name' => $name.' Manufacturer',
            'description' => $description,
            'status' => 'approved', 'approval_status' => 'approved', 'visibility_status' => 'marketplace_only',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('catalog_product_sources')->insert([
            'product_id' => $id, 'source_id' => $this->sourceId, 'source_part_id' => 'C'.$id,
            'source_url' => 'https://example.test/jlcpcb/C'.$id, 'source_payload_hash' => hash('sha256', (string) $id),
            'review_status' => 'approved', 'data_quality_score' => 0.95, 'raw_snapshot' => json_encode(['id' => $id]),
            'imported_at' => now(), 'last_synced_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function importerMeta(array $extra = []): string
    {
        return json_encode(array_merge([
            'source' => 'jlcpcb_parts_database', 'review_status' => 'pending_review', 'robots' => 'noindex,nofollow',
            'keywords' => ['components', 'rfq'],
            'localized' => ['global' => ['locale' => 'en'], 'nepal' => ['locale' => 'en-NP']],
        ], $extra), JSON_UNESCAPED_SLASHES);
    }

    private function verifiedBackupDirectory(): string
    {
        $directory = $this->backupRoot.'/backup';
        File::ensureDirectoryExists($directory);
        file_put_contents($directory.'/MANIFEST.txt', json_encode(['status' => 'verified']));
        file_put_contents($directory.'/catalog.dump', 'immutable catalog fixture');
        file_put_contents($directory.'/RESTORE_VERIFICATION.txt', "result=passed\n");
        file_put_contents($directory.'/RESTORE_VERIFICATION_COUNTS.tsv', "products\t2\n");
        file_put_contents($directory.'/SHA256SUMS', sprintf(
            "%s  MANIFEST.txt\n%s  catalog.dump\n",
            hash_file('sha256', $directory.'/MANIFEST.txt'),
            hash_file('sha256', $directory.'/catalog.dump'),
        ));
        file_put_contents($directory.'/RESTORE_VERIFICATION_SHA256SUMS', sprintf(
            "%s  RESTORE_VERIFICATION_COUNTS.tsv\n%s  RESTORE_VERIFICATION.txt\n",
            hash_file('sha256', $directory.'/RESTORE_VERIFICATION_COUNTS.tsv'),
            hash_file('sha256', $directory.'/RESTORE_VERIFICATION.txt'),
        ));

        return $directory;
    }

    private function decodedJson(mixed $value): array
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
