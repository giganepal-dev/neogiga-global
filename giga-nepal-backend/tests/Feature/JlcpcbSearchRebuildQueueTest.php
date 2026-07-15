<?php

namespace Tests\Feature;

use App\Jobs\RebuildApprovedImportSearchIndexJob;
use App\Models\Role;
use App\Models\User;
use App\Services\Catalog\CatalogSearchRebuildService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
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
            7200,
            config('queue.connections.'.RebuildApprovedImportSearchIndexJob::CONNECTION.'.retry_after')
        );

        Queue::assertPushedOn(
            RebuildApprovedImportSearchIndexJob::QUEUE,
            RebuildApprovedImportSearchIndexJob::class,
            function (RebuildApprovedImportSearchIndexJob $job) use ($rebuild): bool {
                return $job->jobId === (int) $rebuild->id
                && $job->connection === RebuildApprovedImportSearchIndexJob::CONNECTION
                && $job->timeout >= 1800
                && $job->failOnTimeout === false;
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

    public function test_rebuild_uses_bounded_chunks_and_bulk_replaces_search_documents_and_facets(): void
    {
        if (! Schema::hasColumn('products', 'visibility_status')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->string('visibility_status', 40)->nullable();
            });
        }
        $sourceId = DB::table('catalog_sources')->insertGetId([
            'code' => 'jlcpcb_parts_database',
            'name' => 'JLCPCB scale-test source',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $now = now();
        $products = [];
        for ($index = 1; $index <= 505; $index++) {
            $products[] = [
                'name' => "Scale component {$index}",
                'slug' => "scale-component-{$index}",
                'sku' => "NG-SCALE-{$index}",
                'mpn' => "MPN-{$index}",
                'status' => 'approved',
                'visibility_status' => 'marketplace_only',
                'attributes' => json_encode(['package' => ['normalized_value' => 'SMD']]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('products')->insert($products);
        $productIds = DB::table('products')->where('sku', 'like', 'NG-SCALE-%')->orderBy('id')->pluck('id');
        DB::table('catalog_product_sources')->insert($productIds->map(fn (int $id, int $offset): array => [
            'product_id' => $id,
            'source_id' => $sourceId,
            'source_part_id' => 'C-SCALE-'.($offset + 1),
            'source_payload_hash' => hash('sha256', (string) $id),
            'data_quality_score' => '0.95',
            'review_status' => 'approved',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        $service = app(CatalogSearchRebuildService::class);
        $jobId = $service->createJob(null, 'jlcpcb_parts_database');
        $result = $service->rebuildApprovedImports($jobId, 'jlcpcb_parts_database');

        $this->assertSame(505, $result['product_count']);
        $this->assertSame(505, $result['indexed_count']);
        $this->assertDatabaseCount('product_search_documents', 505);
        $this->assertSame(505, DB::table('product_facet_values')->where('facet_name', 'package')->count());
        $job = DB::table('catalog_index_rebuild_jobs')->find($jobId);
        $this->assertSame('completed', $job->status);
        $this->assertSame(505, (int) $job->product_count);
        $metadata = json_decode((string) $job->metadata, true);
        $this->assertSame('bounded_keyset_bulk_upsert', $metadata['write_strategy']);
        $this->assertSame(500, $metadata['chunk_size']);
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
