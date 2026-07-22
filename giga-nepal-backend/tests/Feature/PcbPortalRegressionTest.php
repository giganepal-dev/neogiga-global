<?php

namespace Tests\Feature;

use App\Models\Pcb\PcbProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PcbPortalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_list_uses_the_existing_project_id_relationships(): void
    {
        config()->set('pcb.domain', 'pcb.neogiga.com');

        $user = User::factory()->create();
        $owner = User::factory()->create();
        $owned = PcbProject::create(['user_id' => $user->id, 'name' => 'Owned controller']);
        $shared = PcbProject::create(['user_id' => $owner->id, 'name' => 'Shared controller']);
        $shared->members()->create([
            'user_id' => $user->id,
            'role' => 'viewer',
            'nda_accepted' => true,
        ]);
        $version = $owned->versions()->create([
            'version_number' => 1,
            'created_by_id' => $user->id,
            'change_summary' => 'Initial version',
        ]);

        $this->actingAs($user)
            ->get('http://pcb.neogiga.com/en/projects')
            ->assertOk()
            ->assertSee($owned->name)
            ->assertSee($shared->name);
        $this->actingAs($user)
            ->get('http://pcb.neogiga.com/en/projects/'.$owned->id)
            ->assertOk()
            ->assertSee($owned->name);

        $this->assertSame('project_id', $owned->members()->getForeignKeyName());
        $this->assertSame('project_id', $owned->files()->getForeignKeyName());
        $this->assertSame('project_id', $owned->quoteConfigurations()->getForeignKeyName());
        $this->assertSame($version->id, $owned->fresh()->currentVersion?->id);
    }

    public function test_project_workspace_actions_are_registered(): void
    {
        $this->assertTrue(app('router')->getRoutes()->hasNamedRoute('pcb.projects.update'));
        $this->assertTrue(app('router')->getRoutes()->hasNamedRoute('pcb.projects.cancel'));
        $this->assertTrue(app('router')->getRoutes()->hasNamedRoute('pcb.quotes.approve'));
        $this->assertTrue(app('router')->getRoutes()->hasNamedRoute('pcb.quotes.reject'));
    }
}
