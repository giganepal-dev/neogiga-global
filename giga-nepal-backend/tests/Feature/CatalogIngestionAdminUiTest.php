<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogIngestionAdminUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogue_ingestion_page_requires_admin(): void
    {
        $this->get('/admin/catalog-ingestion')->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_source_policy_and_review_queue(): void
    {
        $this->source();
        $this->task();

        $this->actingAs($this->admin())
            ->get('/admin/catalog-ingestion')
            ->assertOk()
            ->assertSee('Catalogue Sources')
            ->assertSee('Adafruit')
            ->assertSee('Fixture Source Product')
            ->assertSee('Quality');
    }

    public function test_admin_can_see_document_sources_without_crawl_controls(): void
    {
        DB::table('catalog_sources')->insert([
            'code' => 'sunny_okystar_quotation_files', 'name' => 'Sunny / OKYSTAR quotation files', 'source_url' => 'https://www.okystar.com/', 'active' => true,
            'source_type' => 'supplier_document', 'catalogue_policy' => json_encode(['document_only' => true]), 'import_enabled' => false, 'media_download_enabled' => false,
            'description_reuse_status' => 'unknown', 'status' => 'pending_manual_review', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/catalog-ingestion')
            ->assertOk()
            ->assertSee('Sunny / OKYSTAR quotation files')
            ->assertSee('document staging');
    }

    public function test_policy_cannot_enable_import_without_approval(): void
    {
        $this->source();

        $this->actingAs($this->admin())
            ->post('/admin/catalog-ingestion/sources/adafruit', [
                'status' => 'pending_manual_review',
                'description_reuse_status' => 'unknown',
                'import_enabled' => '1',
                'note' => 'Terms review is still in progress.',
            ])
            ->assertSessionHasErrors('import_enabled');

        $this->assertDatabaseHas('catalog_sources', ['code' => 'adafruit', 'import_enabled' => false]);
    }

    public function test_admin_can_approve_source_policy_and_resolve_review_task(): void
    {
        $this->source();
        $taskId = $this->task();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/catalog-ingestion/sources/adafruit', [
                'status' => 'approved',
                'description_reuse_status' => 'not_permitted',
                'import_enabled' => '1',
                'note' => 'Supplier-approved factual product feed only.',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('catalog_sources', ['code' => 'adafruit', 'status' => 'approved', 'import_enabled' => true]);

        $this->actingAs($admin)
            ->post("/admin/catalog-ingestion/review-tasks/{$taskId}", ['status' => 'resolved', 'note' => 'MPN and source evidence reviewed.'])
            ->assertRedirect();
        $this->assertDatabaseHas('catalog_review_tasks', ['id' => $taskId, 'status' => 'resolved', 'assigned_to' => $admin->id]);
    }

    public function test_admin_can_dry_run_a_supplier_quotation_csv_without_creating_catalogue_records(): void
    {
        Storage::fake('local');
        $csv = implode("\n", [
            'supplier_sku,product_name,source_name,source_file',
            'KS0001,UNO R3 compatible board,Sunny quote,supplier-quote.csv',
        ]);

        $this->actingAs($this->admin())
            ->from('/admin/catalog-ingestion')
            ->post('/admin/catalog-ingestion/stage-document', [
                'quotation_csv' => UploadedFile::fake()->createWithContent('supplier-quote.csv', $csv),
                'dry_run' => '1',
            ])
            ->assertRedirect('/admin/catalog-ingestion')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('catalog_sources', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertNotEmpty(Storage::disk('local')->allFiles('catalog/reports'));
    }

    public function test_admin_can_verify_identity_without_merging_or_publishing_the_product(): void
    {
        $this->source();
        $taskId = $this->task();
        $productId = DB::table('catalog_review_tasks')->where('id', $taskId)->value('product_id');

        $this->actingAs($this->admin())
            ->post("/admin/catalog-ingestion/review-tasks/{$taskId}/identity", [
                'manufacturer' => 'Acme Components',
                'mpn' => ' ACME-100 ',
                'note' => 'Verified against the supplier quotation and manufacturer label.',
            ])
            ->assertRedirect();

        $brandId = DB::table('product_brands')->where('slug', 'acme-components')->value('id');
        $this->assertDatabaseHas('products', ['id' => $productId, 'brand_id' => $brandId, 'mpn' => 'ACME-100', 'status' => 'pending']);
        $this->assertDatabaseHas('supplier_products', ['manufacturer_part_number' => 'ACME-100', 'source_manufacturer' => 'Acme Components']);
        $this->assertDatabaseHas('catalog_review_tasks', ['id' => $taskId, 'status' => 'resolved']);
        $this->assertDatabaseHas('catalog_review_tasks', ['product_id' => $productId, 'task_type' => 'supplier_product_review', 'status' => 'open']);
    }

    public function test_identity_verification_suggests_a_duplicate_without_merging_products(): void
    {
        $this->source();
        $taskId = $this->task();
        $stagedProductId = DB::table('catalog_review_tasks')->where('id', $taskId)->value('product_id');
        $brandId = DB::table('product_brands')->insertGetId([
            'name' => 'Acme Components', 'slug' => 'acme-components', 'is_active' => true, 'is_featured' => false, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $canonicalProductId = DB::table('products')->insertGetId([
            'name' => 'Existing Acme Part', 'slug' => 'existing-acme-part', 'sku' => 'NG-EXISTING-ACME-100', 'brand_id' => $brandId, 'mpn' => 'ACME-100',
            'type' => 'simple', 'status' => 'approved', 'base_price' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post("/admin/catalog-ingestion/review-tasks/{$taskId}/identity", [
                'manufacturer' => 'Acme Components',
                'mpn' => 'ACME-100',
                'note' => 'Verified identity; canonical match needs a separate merge decision.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('supplier_products', ['product_id' => $stagedProductId, 'manufacturer_part_number' => 'ACME-100']);
        $this->assertDatabaseHas('catalog_review_tasks', ['product_id' => $stagedProductId, 'task_type' => 'possible_canonical_duplicate', 'status' => 'open']);
        $this->assertDatabaseHas('products', ['id' => $canonicalProductId, 'mpn' => 'ACME-100']);
        $this->assertDatabaseCount('products', 2);
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'super_admin', 'is_active' => true]);

        return User::create(['name' => 'Catalog Admin', 'email' => uniqid('catalog-', true).'@example.com', 'password' => bcrypt('secret'), 'role_id' => $role->id]);
    }

    private function source(): int
    {
        return DB::table('catalog_sources')->insertGetId([
            'code' => 'adafruit', 'name' => 'Adafruit', 'source_url' => 'https://www.adafruit.com', 'active' => true,
            'source_type' => 'supplier', 'base_url' => 'https://www.adafruit.com', 'robots_url' => 'https://www.adafruit.com/robots.txt',
            'catalogue_policy' => json_encode([]), 'import_enabled' => false, 'media_download_enabled' => false,
            'description_reuse_status' => 'unknown', 'status' => 'pending_manual_review', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function task(): int
    {
        $sourceId = DB::table('catalog_sources')->where('code', 'adafruit')->value('id');
        $productId = DB::table('products')->insertGetId([
            'name' => 'Fixture Source Product', 'slug' => 'fixture-source-product-'.uniqid(), 'sku' => 'NG-ADMIN-'.strtoupper(uniqid()),
            'type' => 'simple', 'status' => 'pending', 'base_price' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $supplierProductId = DB::table('supplier_products')->insertGetId([
            'catalog_source_id' => $sourceId, 'product_id' => $productId, 'source_product_id' => 'FIXTURE-1', 'supplier_sku' => 'FIXTURE-1',
            'source_name' => 'Fixture Source Product', 'content_hash' => hash('sha256', 'fixture-source-product'), 'review_status' => 'pending_review',
            'data_quality_score' => 70, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('catalog_review_tasks')->insertGetId([
            'catalog_source_id' => $sourceId, 'supplier_product_id' => $supplierProductId, 'product_id' => $productId,
            'task_type' => 'supplier_product_review', 'status' => 'open', 'confidence' => 0.7,
            'evidence_json' => json_encode(['missing_fields' => ['specifications']]), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
