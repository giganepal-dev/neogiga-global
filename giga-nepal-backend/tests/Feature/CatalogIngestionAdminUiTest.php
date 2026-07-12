<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
