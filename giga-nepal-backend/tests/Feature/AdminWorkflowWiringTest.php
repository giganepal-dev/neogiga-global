<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminWorkflowWiringTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_and_access_controls_use_registered_workflows(): void
    {
        $this->actingAs($this->admin());

        $categories = $this->get('/admin/categories')
            ->assertOk()
            ->assertSee('data-category-tree-toggle', false)
            ->assertSee('/js/admin-categories.js', false)
            ->assertDontSee("document.querySelector('[data-category-tree-toggle]')", false)
            ->assertSee('/admin/imports/elecforest', false)
            ->assertDontSee('Import CSV');
        $this->assertStringContainsString("script-src 'self'", (string) $categories->headers->get('Content-Security-Policy'));
        $this->assertTrue(File::exists(public_path('js/admin-categories.js')));
        $this->assertStringContainsString('data-category-tree-toggle', File::get(public_path('js/admin-categories.js')));

        $this->get('/admin/seo')
            ->assertOk()
            ->assertSee('/sitemap.xml', false)
            ->assertSee('/admin/products', false)
            ->assertDontSee('Regenerate Sitemap');

        $this->get('/admin/media')
            ->assertOk()
            ->assertSee('/admin/media?folder=datasheets', false)
            ->assertSee('/admin/products', false);

        $this->get('/admin/users')
            ->assertOk()
            ->assertSee('/admin/users/permissions', false)
            ->assertSee('id="role-permissions"', false)
            ->assertDontSee('Permission Matrix</h2><span class="badge b-warn">placeholder', false);
    }

    public function test_inventory_and_pos_controls_expose_existing_mutation_routes(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/inventory')
            ->assertOk()
            ->assertSee('/admin/inventory/transfers', false)
            ->assertSee('/admin/inventory/reservations', false)
            ->assertSee('/admin/inventory/low-stock/generate', false);

        $this->get('/admin/pos')
            ->assertOk()
            ->assertSee('/admin/pos/payment-methods', false)
            ->assertSee('/admin/pos/offline-sync-events', false)
            ->assertDontSee('refund placeholder')
            ->assertDontSee('offline sync placeholder');
    }

    public function test_admin_shell_links_operational_destinations_and_reports_api_scope(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('/admin/marketing/abandoned-carts', false)
            ->assertSee('/admin/system-health', false)
            ->assertSee('Registered Laravel endpoints with live platform health')
            ->assertDontSee('All application APIs wired');
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Super Admin', 'permissions' => ['*'], 'is_active' => true],
        );

        return User::create([
            'name' => 'Admin Workflow',
            'email' => 'admin-workflow@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);
    }
}
