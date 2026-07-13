<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminUsersAndProductsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_render_the_users_and_roles_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('User Manager')
            ->assertSee('Permission Matrix');
    }

    public function test_product_creation_uses_schema_valid_defaults_and_redirects_to_the_detail_page(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/products', [
                'name' => 'Admin Test Product',
                'sku' => 'NG-ADMIN-TEST-001',
                'status' => 'draft',
            ])
            ->assertRedirect('/admin/products/1');

        $this->assertDatabaseHas('products', [
            'id' => 1,
            'sku' => 'NG-ADMIN-TEST-001',
            'status' => 'draft',
            'type' => 'simple',
        ]);
    }

    public function test_product_creation_rejects_non_schema_statuses(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/products')
            ->post('/admin/products', [
                'name' => 'Invalid Product',
                'sku' => 'NG-ADMIN-INVALID-001',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/products')
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_archive_action_uses_valid_statuses(): void
    {
        $productId = DB::table('products')->insertGetId([
            'name' => 'Archive Test Product',
            'slug' => 'archive-test-product',
            'sku' => 'NG-ADMIN-ARCHIVE-001',
            'type' => 'simple',
            'status' => 'draft',
            'base_price' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin())->post("/admin/products/{$productId}/toggle")->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $productId, 'status' => 'archived']);

        $this->actingAs($this->admin())->post("/admin/products/{$productId}/toggle")->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $productId, 'status' => 'draft']);
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super admin', 'is_active' => true]);

        return User::create([
            'name' => 'Admin User',
            'email' => uniqid('admin-', true).'@example.com',
            'password' => bcrypt('secret-password'),
            'role_id' => $role->id,
        ]);
    }
}
