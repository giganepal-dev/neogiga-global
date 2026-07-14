<?php

namespace Tests\Feature;

use App\Jobs\RebuildApprovedImportSearchIndexJob;
use App\Models\Role;
use App\Models\User;
use App\Services\Catalog\CatalogSearchRebuildService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class JlcpcbSearchRebuildQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_rebuild_is_dispatched_to_the_long_running_catalog_import_queue(): void
    {
        Queue::fake();

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/admin/imports/jlcpcb/search-rebuild')
            ->assertRedirect()
            ->assertSessionHas('status');

        $rebuild = DB::table('catalog_index_rebuild_jobs')->latest('id')->first();

        $this->assertNotNull($rebuild);
        $this->assertSame('queued', $rebuild->status);
        $this->assertSame($admin->id, (int) $rebuild->queued_by);
        $this->assertGreaterThan(
            1800,
            config('queue.connections.'.RebuildApprovedImportSearchIndexJob::CONNECTION.'.retry_after')
        );

        Queue::assertPushedOn(
            RebuildApprovedImportSearchIndexJob::QUEUE,
            RebuildApprovedImportSearchIndexJob::class,
            function (RebuildApprovedImportSearchIndexJob $job) use ($rebuild): bool {
                return $job->jobId === (int) $rebuild->id
                    && $job->connection === RebuildApprovedImportSearchIndexJob::CONNECTION
                    && $job->timeout === 1800
                    && $job->failOnTimeout;
            }
        );
    }

    public function test_failed_callback_records_a_terminal_state_for_a_timed_out_rebuild(): void
    {
        $service = app(CatalogSearchRebuildService::class);
        $jobId = $service->createJob(null, 'jlcpcb_parts_database');
        $startedAt = now()->subMinute();

        DB::table('catalog_index_rebuild_jobs')->where('id', $jobId)->update([
            'status' => 'running',
            'started_at' => $startedAt,
            'updated_at' => $startedAt,
        ]);

        $job = new RebuildApprovedImportSearchIndexJob($jobId, 'jlcpcb_parts_database');
        $job->failed(new RuntimeException('Catalog rebuild worker timed out after 1800 seconds.'));

        $rebuild = DB::table('catalog_index_rebuild_jobs')->find($jobId);

        $this->assertSame('failed', $rebuild->status);
        $this->assertSame('Catalog rebuild worker timed out after 1800 seconds.', $rebuild->error);
        $this->assertNotNull($rebuild->completed_at);
        $this->assertNotNull($rebuild->started_at);
    }

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Super Admin', 'permissions' => ['*'], 'is_active' => true],
        );

        return User::create([
            'name' => 'Catalog rebuild admin',
            'email' => 'catalog-rebuild-admin@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);
    }
}
