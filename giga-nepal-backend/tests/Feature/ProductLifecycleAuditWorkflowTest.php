<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductLifecycleAuditWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $viewPath = dirname(__DIR__, 2).'/resources/views';
        config(['view.paths' => [$viewPath]]);
        app('view')->getFinder()->setPaths([$viewPath]);
    }

    public function test_lifecycle_changes_are_normalized_and_audited(): void
    {
        $productId = $this->product('Lifecycle Part', 'NG-LIFECYCLE-001');

        $this->actingAs($this->admin())
            ->post("/admin/products/{$productId}/lifecycle", ['lifecycle_status' => 'end_of_life'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Product lifecycle status updated.');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'lifecycle_status' => 'END_OF_LIFE',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product_lifecycle_updated',
            'model_type' => 'products',
            'model_id' => $productId,
        ]);
    }

    public function test_catalog_audit_filter_returns_only_products_missing_an_mpn(): void
    {
        $categoryId = DB::table('product_categories')->insertGetId([
            'name' => 'Audit fixtures',
            'slug' => 'audit-fixtures',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $missingMpnId = $this->product('Missing MPN Part', 'NG-AUDIT-MISSING');
        DB::table('products')->where('id', $missingMpnId)->update(['category_id' => $categoryId]);
        $completeId = $this->product('Complete Part', 'NG-AUDIT-COMPLETE');
        DB::table('products')->where('id', $completeId)->update([
            'category_id' => $categoryId,
            'mpn' => 'COMPLETE-001',
            'description' => 'Complete product description.',
            'base_price' => 10,
            'sale_price' => 10,
            'lifecycle_status' => 'ACTIVE',
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/products?audit=missing_mpn')
            ->assertOk()
            ->assertViewHas('products', function ($products) use ($missingMpnId, $completeId): bool {
                return $products->total() === 1
                    && (int) $products->first()->id === $missingMpnId
                    && $products->doesntContain('id', $completeId);
            });
    }

    private function product(string $name, string $sku): int
    {
        return DB::table('products')->insertGetId([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'sku' => $sku,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Super Admin', 'permissions' => ['*'], 'is_active' => true],
        );

        return User::create([
            'name' => 'Catalog lifecycle operator',
            'email' => 'catalog-lifecycle@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);
    }
}
