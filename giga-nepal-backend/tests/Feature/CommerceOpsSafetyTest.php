<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommerceOpsSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_refresh_keeps_alerts_beyond_the_first_chunk_and_resolves_only_recovered_stock(): void
    {
        $this->actingAs($this->superAdmin());

        $productId = DB::table('products')->insertGetId([
            'name' => 'Low-stock safety product',
            'slug' => 'low-stock-safety-product',
            'sku' => 'LOW-STOCK-SAFETY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'name' => 'Low-stock safety warehouse',
            'code' => 'LOW-STOCK-SAFETY',
            'address_line1' => 'Test address',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = [];
        foreach (range(1, 501) as $sequence) {
            $rows[] = [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'sku' => sprintf('LOW-%04d', $sequence),
                'quantity_available' => 1,
                'reorder_point' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('inventory_stocks')->insert($chunk);
        }

        $lowStockIds = DB::table('inventory_stocks')->orderBy('id')->pluck('id');
        $stockBeyondFirstChunk = (int) $lowStockIds->last();
        $existingAlertId = DB::table('low_stock_alerts')->insertGetId([
            'inventory_stock_id' => $stockBeyondFirstChunk,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'available_quantity' => 1,
            'threshold' => 5,
            'status' => 'active',
            'severity' => 'warning',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recoveredStockId = DB::table('inventory_stocks')->insertGetId([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'sku' => 'LOW-RECOVERED',
            'quantity_available' => 10,
            'reorder_point' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $recoveredAlertId = DB::table('low_stock_alerts')->insertGetId([
            'inventory_stock_id' => $recoveredStockId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'available_quantity' => 1,
            'threshold' => 5,
            'status' => 'active',
            'severity' => 'warning',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/admin/inventory/low-stock/generate')
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('low_stock_alerts', [
            'id' => $existingAlertId,
            'inventory_stock_id' => $stockBeyondFirstChunk,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('low_stock_alerts', [
            'id' => $recoveredAlertId,
            'status' => 'resolved',
        ]);
        $this->assertSame(
            501,
            DB::table('low_stock_alerts')
                ->whereIn('status', ['open', 'active', 'acknowledged', 'reorder_queued'])
                ->count()
        );
    }

    public function test_pos_refund_serializes_on_sale_and_replays_an_idempotent_request_without_over_refunding(): void
    {
        $this->actingAs($this->superAdmin());

        $saleId = DB::table('pos_sales')->insertGetId([
            'sale_reference' => 'POS-SAFETY-001',
            'subtotal' => '100.0000',
            'total_amount' => '100.0000',
            'currency_code' => 'USD',
            'payment_status' => 'paid',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $payload = [
            'amount' => '30.0000',
            'refund_method' => 'cash',
            'reason' => 'Customer return',
        ];
        $this->get("/admin/pos/sales/{$saleId}")
            ->assertOk()
            ->assertSee('name="idempotency_key"', false)
            ->assertSee('value="pos-refund-', false);
        $this->withHeader('Idempotency-Key', 'pos-refund-safety-1')
            ->post("/admin/pos/sales/{$saleId}/refunds", $payload)
            ->assertRedirect()
            ->assertSessionHas('status', 'POS refund recorded.');
        $this->withHeader('Idempotency-Key', 'pos-refund-safety-1')
            ->post("/admin/pos/sales/{$saleId}/refunds", $payload)
            ->assertRedirect()
            ->assertSessionHas('status', 'POS refund was already recorded; duplicate request ignored.');

        $this->assertSame(1, DB::table('pos_refunds')->where('pos_sale_id', $saleId)->count());
        $this->assertSame('30.0000', DB::table('pos_refunds')->where('pos_sale_id', $saleId)->value('amount'));
        $this->assertSame('partial_refund', DB::table('pos_sales')->where('id', $saleId)->value('payment_status'));
        $this->assertTrue(collect($queries)->contains(
            static fn (string $sql): bool => str_contains($sql, 'pos_sales') && str_contains($sql, 'for update')
        ), 'The refund transaction must lock its POS sale before checking the remaining balance.');

        $this->withHeader('Idempotency-Key', '')
            ->post("/admin/pos/sales/{$saleId}/refunds", $payload)
            ->assertSessionHasErrors('idempotency_key');
        $this->withHeader('Idempotency-Key', 'pos-refund-safety-3')
            ->post("/admin/pos/sales/{$saleId}/refunds", $payload)
            ->assertRedirect()
            ->assertSessionHas('status', 'POS refund recorded.');
        $this->withHeader('Idempotency-Key', 'pos-refund-safety-4')
            ->post("/admin/pos/sales/{$saleId}/refunds", $payload)
            ->assertRedirect()
            ->assertSessionHas('status', 'POS refund recorded.');
        $this->assertSame(3, DB::table('pos_refunds')->where('pos_sale_id', $saleId)->count());

        $this->withHeader('Idempotency-Key', 'pos-refund-safety-1')
            ->post("/admin/pos/sales/{$saleId}/refunds", array_merge($payload, ['amount' => '20.0000']))
            ->assertRedirect()
            ->assertSessionHas('error', 'Idempotency key was already used for a different refund request.');
        $this->withHeader('Idempotency-Key', 'pos-refund-safety-2')
            ->post("/admin/pos/sales/{$saleId}/refunds", array_merge($payload, ['amount' => '80.0000']))
            ->assertRedirect()
            ->assertSessionHas('error', 'Refund exceeds remaining sale total.');

        $this->assertSame(3, DB::table('pos_refunds')->where('pos_sale_id', $saleId)->count());
    }

    public function test_only_super_admin_can_manage_permissions_and_toggle_synchronizes_json_and_pivot_grants(): void
    {
        $superAdmin = $this->superAdmin();
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'permissions' => ['catalog.manage', 'legacy.keep'],
            'is_active' => true,
        ]);
        $admin = $this->userForRole($adminRole, 'ordinary-admin@example.com');
        $permissionId = DB::table('permissions')->insertGetId([
            'key' => 'catalog.manage',
            'name' => 'Manage catalog',
            'group' => 'catalog',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('full access')
            ->assertSee("action=\"/admin/users/roles/{$adminRole->id}/permissions/{$permissionId}\"", false)
            ->assertSee('aria-pressed="true"', false)
            ->assertDontSee("action=\"/admin/users/roles/{$superAdmin->role_id}/permissions/{$permissionId}\"", false);

        $this->actingAs($admin)
            ->post('/admin/users/permissions', [
                'key' => 'security.forbidden',
                'name' => 'Forbidden permission',
                'group' => 'security',
            ])
            ->assertForbidden();
        $this->actingAs($admin)
            ->post("/admin/users/roles/{$adminRole->id}/permissions/{$permissionId}")
            ->assertForbidden();
        $this->assertDatabaseMissing('permissions', ['key' => 'security.forbidden']);
        $this->assertTrue(Role::findOrFail($adminRole->id)->allows('catalog.manage'));

        $this->actingAs($superAdmin)
            ->post("/admin/users/roles/{$adminRole->id}/permissions/{$permissionId}")
            ->assertRedirect()
            ->assertSessionHas('status', 'Role permission updated.');
        $revokedRole = Role::findOrFail($adminRole->id);
        $this->assertSame(['legacy.keep'], $revokedRole->permissions);
        $this->assertFalse($revokedRole->allows('catalog.manage'));
        $this->assertDatabaseMissing('role_permissions', [
            'role_id' => $adminRole->id,
            'permission_id' => $permissionId,
        ]);

        $this->actingAs($superAdmin)
            ->post("/admin/users/roles/{$adminRole->id}/permissions/{$permissionId}")
            ->assertRedirect()
            ->assertSessionHas('status', 'Role permission updated.');
        $grantedRole = Role::findOrFail($adminRole->id);
        $this->assertEqualsCanonicalizing(['legacy.keep', 'catalog.manage'], $grantedRole->permissions);
        $this->assertTrue($grantedRole->allows('catalog.manage'));
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $adminRole->id,
            'permission_id' => $permissionId,
        ]);

        $this->actingAs($superAdmin)
            ->post("/admin/users/roles/{$superAdmin->role_id}/permissions/{$permissionId}")
            ->assertRedirect()
            ->assertSessionHas('error', 'Wildcard roles are immutable in the permission matrix.');
        $this->assertSame(['*'], Role::findOrFail($superAdmin->role_id)->permissions);
    }

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Super Admin', 'permissions' => ['*'], 'is_active' => true],
        );

        return $this->userForRole($role, 'commerce-safety-super-admin@example.com');
    }

    private function userForRole(Role $role, string $email): User
    {
        return User::create([
            'name' => 'Commerce safety user',
            'email' => $email,
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);
    }
}
