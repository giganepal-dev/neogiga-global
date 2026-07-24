<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueProcessPending extends Command
{
    protected $signature = 'neogiga:queue-process-pending
        {--queue= : Process only this queue (default: all queues)}
        {--dry-run : Show what would be processed without actually running jobs}
        {--limit=100 : Maximum number of jobs to process}
        {--timeout=3600 : Maximum seconds to run}';

    protected $description = 'Manually process pending queue jobs (use when workers are down)';

    public function handle(): int
    {
        if (! Schema::hasTable('jobs')) {
            $this->error('Jobs table not found.');

            return self::FAILURE;
        }

        $queue = $this->option('queue');
        $dryRun = $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));
        $timeout = max(60, (int) $this->option('timeout'));

        $query = DB::table('jobs')
            ->whereNull('reserved_at')
            ->where('available_at', '<=', now()->timestamp)
            ->orderBy('id');

        if ($queue) {
            $query->where('queue', $queue);
        }

        $totalAvailable = (clone $query)->count();

        $this->info('=== NeoGiga Queue Processor ===');
        $this->line("Total pending jobs: {$totalAvailable}");
        $this->line("Queue filter: " . ($queue ?: 'all'));
        $this->line("Dry run: " . ($dryRun ? 'yes' : 'no'));
        $this->line("Limit: {$limit}");
        $this->newLine();

        if ($totalAvailable === 0) {
            $this->info('No pending jobs to process.');

            return self::SUCCESS;
        }

        $jobs = $query->limit($limit)->get();

        if ($dryRun) {
            $this->info('Jobs that would be processed:');
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobName = $payload['job'] ?? 'Unknown';
                $this->line("  #{$job->id} [{$job->queue}] {$jobName} (attempts: {$job->attempts})");
            }

            return self::SUCCESS;
        }

        $processed = 0;
        $failed = 0;
        $startTime = time();

        foreach ($jobs as $job) {
            if (time() - $startTime > $timeout) {
                $this->warn("Timeout reached after {$timeout} seconds.");
                break;
            }

            $payload = json_decode($job->payload, true);
            $jobName = $payload['job'] ?? 'Unknown';

            $this->line("Processing #{$job->id} [{$job->queue}] {$jobName}...");

            try {
                // Reserve the job
                DB::table('jobs')
                    ->where('id', $job->id)
                    ->update(['reserved_at' => time()]);

                // Execute the job
                $jobInstance = $this->unserializeJob($payload);
                if ($jobInstance) {
                    $jobInstance->handle();
                    DB::table('jobs')->where('id', $job->id)->delete();
                    $processed++;
                    $this->line("  ✓ Completed");
                } else {
                    // Could not unserialize, mark as failed
                    $this->markAsFailed($job, 'Could not unserialize job');
                    $failed++;
                    $this->error("  ✗ Failed to unserialize");
                }
            } catch (\Throwable $e) {
                $this->markAsFailed($job, $e->getMessage());
                $failed++;
                $this->error("  ✗ Failed: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->line("Processed: {$processed}");
        $this->line("Failed: {$failed}");
        $this->line("Time: " . (time() - $startTime) . " seconds");

        return self::SUCCESS;
    }

    private function unserializeJob(array $payload): ?object
    {
        if (! isset($payload['job'])) {
            return null;
        }

        $jobClass = $payload['job'];
        $data = $payload['data'] ?? [];

        if (! class_exists($jobClass)) {
            return null;
        }

        return new $jobClass($data);
    }

    private function markAsFailed($job, string $reason): void
    {
        $payload = json_decode($job->payload, true);

        DB::table('failed_jobs')->insert([
            'uuid' => $payload['uuid'] ?? uniqid(),
            'connection' => 'database',
            'queue' => $job->queue,
            'payload' => $job->payload,
            'exception' => $reason,
            'failed_at' => now(),
        ]);

        DB::table('jobs')->where('id', $job->id)->delete();
    }
}
