<?php

namespace App\Console\Commands;

use App\Services\Product\SourceImageCandidateDiscovery;
use Illuminate\Console\Command;

class DiscoverSourceProductImageCandidates extends Command
{
    protected $signature = 'product-images:discover-source-candidates
        {--source-name= : Exact products.source_name to inspect}
        {--manufacturer= : Exact manufacturer name to inspect}
        {--allowed-source-host=* : Source and asset host allowed for this batch; repeat for each approved domain}
        {--limit=0 : Maximum products to inspect; 0 processes the selected source batch}
        {--min-confidence=0.70 : Minimum candidate confidence to retain}
        {--concurrency=4 : Maximum concurrent source-page requests (1-10)}
        {--timeout=10 : HTTP timeout seconds per source page (2-30)}
        {--apply : Persist inactive candidates. Without this flag the command is a dry-run.}';

    protected $description = 'Discover inactive product-image candidates from selected official source pages without downloading or publishing media';

    public function handle(SourceImageCandidateDiscovery $discovery): int
    {
        $hosts = array_values(array_filter(array_map('strval', (array) $this->option('allowed-source-host'))));
        if ($hosts === []) {
            $this->error('At least one --allowed-source-host is required to prevent arbitrary source-page requests.');

            return self::FAILURE;
        }
        if (trim((string) $this->option('source-name')) === '' && trim((string) $this->option('manufacturer')) === '') {
            $this->error('Provide --source-name or --manufacturer to run a controlled source batch.');

            return self::FAILURE;
        }

        $this->line('Safety: this command stores review candidates only. It never downloads, hotlinks, or publishes source images.');
        try {
            $result = $discovery->discover([
                'source_name' => $this->option('source-name'),
                'manufacturer' => $this->option('manufacturer'),
                'allowed_hosts' => $hosts,
                'limit' => (int) $this->option('limit'),
                'min_confidence' => (float) $this->option('min-confidence'),
                'concurrency' => (int) $this->option('concurrency'),
                'timeout' => (int) $this->option('timeout'),
                'apply' => (bool) $this->option('apply'),
            ], function (array $stats): void {
                $this->line(sprintf(
                    'Processed %d | fetched %d | candidates %d | stored %d | failed %d',
                    $stats['products_seen'], $stats['pages_fetched'], $stats['candidates_found'], $stats['candidates_stored'], $stats['fetch_failed'],
                ));
            });
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if (! $this->option('apply')) {
            $this->comment('Dry-run only. Re-run with --apply after reviewing the count and source scope.');
        }

        return self::SUCCESS;
    }
}
