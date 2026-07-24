<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueHealthCheck extends Command
{
    protected $signature = 'neogiga:queue-health
        {--json : Output as JSON}
        {--clear-stale : Clear stale reserved jobs (stuck for >10 minutes)}';

    protected $description = 'Check queue health, pending jobs, and worker status';

    public function handle(): int
    {
        if (! Schema::hasTable('jobs')) {
            $this->error('Jobs table not found.');

            return self::FAILURE;
        }

        $data = $this->gatherStats();

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->printReport($data);

        if ($this->option('clear-stale')) {
            $this->clearStaleJobs($data);
        }

        return self::SUCCESS;
    }

    private function gatherStats(): array
    {
        $totalPending = DB::table('jobs')->count();
        $totalFailed = DB::table('failed_jobs')->count();
        $totalReserved = DB::table('jobs')->whereNotNull('reserved_at')->count();

        $byQueue = DB::table('jobs')
            ->select('queue', DB::raw('count(*) as pending'))
            ->groupBy('queue')
            ->orderByDesc('pending')
            ->get();

        $staleJobs = DB::table('jobs')
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '<', now()->subMinutes(10)->timestamp)
            ->count();

        $oldestPending = DB::table('jobs')
            ->whereNull('reserved_at')
            ->orderBy('available_at')
            ->first(['queue', 'payload', 'created_at']);

        $recentFailed = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(5)
            ->get(['id', 'queue', 'connection', 'payload', 'exception', 'failed_at']);

        $scheduledJobs = [
            'default' => [
                'DetectAbandonedCartsJob' => 'every 15 minutes',
                'CalculateTrendingProductsJob' => 'hourly',
                'CalculateTrendingCategoriesJob' => 'hourly',
                'CalculateTopSearchTermsJob' => 'hourly',
                'RefreshCustomerSegmentJob' => 'daily',
                'GenerateRegionalSalesReportJob' => 'daily',
            ],
            'campaign-preparation' => [
                'PrepareScheduledEmailCampaignsJob' => 'every minute',
            ],
        ];

        return [
            'total_pending' => $totalPending,
            'total_reserved' => $totalReserved,
            'total_failed' => $totalFailed,
            'stale_jobs' => $staleJobs,
            'by_queue' => $byQueue->toArray(),
            'oldest_pending' => $oldestPending,
            'recent_failed' => $recentFailed->toArray(),
            'scheduled_jobs' => $scheduledJobs,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    private function printReport(array $data): void
    {
        $this->info('=== NeoGiga Queue Health Check ===');
        $this->newLine();

        $this->line("Total pending jobs:  {$data['total_pending']}");
        $this->line("Total reserved:     {$data['total_reserved']}");
        $this->line("Total failed:       {$data['total_failed']}");
        $this->line("Stale jobs (>10m):  {$data['stale_jobs']}");

        if ($data['stale_jobs'] > 0) {
            $this->warn("⚠  {$data['stale_jobs']} jobs appear stuck. Run with --clear-stale to release them.");
        }

        $this->newLine();
        $this->info('Jobs by Queue:');

        if (empty($data['by_queue'])) {
            $this->line('  No pending jobs in any queue.');
        } else {
            foreach ($data['by_queue'] as $row) {
                $this->line("  {$row->queue}: {$row->pending} pending");
            }
        }

        if ($data['oldest_pending']) {
            $this->newLine();
            $this->info('Oldest Pending Job:');
            $this->line("  Queue: {$data['oldest_pending']->queue}");
            $this->line("  Created: " . date('Y-m-d H:i:s', $data['oldest_pending']->created_at));

            $payload = json_decode($data['oldest_pending']->payload, true);
            if (isset($payload['job'])) {
                $this->line("  Job: {$payload['job']}");
            }
        }

        if (! empty($data['recent_failed'])) {
            $this->newLine();
            $this->warn('Recent Failed Jobs:');
            foreach ($data['recent_failed'] as $failed) {
                $payload = json_decode($failed->payload, true);
                $jobName = $payload['job'] ?? 'Unknown';
                $this->line("  #{$failed->id} [{$failed->queue}] {$jobName}");
                $this->line("    Failed: {$failed->failed_at}");
            }
        }

        $this->newLine();
        $this->info('Scheduled Jobs:');
        foreach ($data['scheduled_jobs'] as $queue => $jobs) {
            $this->line("  Queue: {$queue}");
            foreach ($jobs as $job => $schedule) {
                $this->line("    - {$job}: {$schedule}");
            }
        }

        $this->newLine();
        $this->line('Checked at: ' . $data['checked_at']);
    }

    private function clearStaleJobs(array $data): void
    {
        if ($data['stale_jobs'] === 0) {
            $this->info('No stale jobs to clear.');

            return;
        }

        $released = DB::table('jobs')
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '<', now()->subMinutes(10)->timestamp)
            ->update([
                'reserved_at' => null,
                'attempts' => DB::raw('attempts + 1'),
            ]);

        $this->warn("Released {$released} stale job(s) back to the queue.");
    }
}
