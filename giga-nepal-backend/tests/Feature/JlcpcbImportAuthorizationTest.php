<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JlcpcbImportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_jlcpcb_import_mutations_require_catalog_manage_permission(): void
    {
        $user = $this->adminWith([]);

        $this->actingAs($user)
            ->post('/admin/imports/jlcpcb/bulk-approve', ['source_ids' => [1]])
            ->assertForbidden();

        $this->actingAs($user)
            ->post('/admin/imports/jlcpcb/bulk-publish', ['source_ids' => [1]])
            ->assertForbidden();

        $this->actingAs($user)
            ->post('/admin/imports/jlcpcb/search-rebuild')
            ->assertForbidden();

        $this->actingAs($user)
            ->post('/admin/imports/jlcpcb/1/approve')
            ->assertForbidden();

        $this->actingAs($user)
            ->post('/admin/imports/jlcpcb/1/publish')
            ->assertForbidden();

        $this->actingAs($user)
            ->post('/admin/imports/jlcpcb/1/reject')
            ->assertForbidden();
    }

    /** @param list<string> $permissions */
    private function adminWith(array $permissions): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Catalog admin', 'permissions' => $permissions, 'is_active' => true],
        );
        $role->forceFill(['permissions' => $permissions])->save();

        return User::create([
            'name' => 'Import reviewer',
            'email' => 'jlcpcb-import-reviewer@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);
    }
}
