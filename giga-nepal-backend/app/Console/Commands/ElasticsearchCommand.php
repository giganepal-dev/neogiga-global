<?php

namespace App\Console\Commands;

use App\Services\Search\ElasticsearchService;
use Illuminate\Console\Command;

class ElasticsearchCommand extends Command
{
    protected $signature = 'es {action : index|delete|sync|stats|search}
                            {--q= : Search query}
                            {--limit=20 : Search result limit}
                            {--chunk=500 : Sync chunk size}';

    protected $description = 'Manage Elasticsearch index and sync';

    public function handle(ElasticsearchService $elastic): int
    {
        $command = $this->argument('action');

        return match ($command) {
            'index' => $this->createIndex($elastic),
            'delete' => $this->deleteIndex($elastic),
            'sync' => $this->syncProducts($elastic),
            'stats' => $this->showStats($elastic),
            'search' => $this->search($elastic),
            default => $this->error("Unknown command: {$command}. Use: index, delete, sync, stats, search"),
        };
    }

    private function createIndex(ElasticsearchService $elastic): int
    {
        $this->info('Creating Elasticsearch index...');

        try {
            $result = $elastic->createIndex();
            $this->info("Index created: {$elastic->getIndexName()}");
            $this->info(json_encode($result, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to create index: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function deleteIndex(ElasticsearchService $elastic): int
    {
        if (! $this->confirm("Delete index {$elastic->getIndexName()}?")) {
            return self::SUCCESS;
        }

        try {
            $result = $elastic->deleteIndex();
            $this->info('Index deleted.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to delete index: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function syncProducts(ElasticsearchService $elastic): int
    {
        $this->info('Syncing products to Elasticsearch...');

        $chunk = (int) $this->option('chunk');
        $bar = $this->output->createProgressBar();

        $stats = $elastic->syncProducts($chunk);

        $bar->finish();
        $this->newLine();
        $this->info("Sync complete: {$stats['indexed']} indexed, {$stats['errors']} errors");

        return self::SUCCESS;
    }

    private function showStats(ElasticsearchService $elastic): int
    {
        $stats = $elastic->stats();

        if (! $stats['exists']) {
            $this->warn('Index does not exist. Run: php artisan elastic index');

            return self::SUCCESS;
        }

        $this->info('Elasticsearch Index Stats:');
        $this->table(['Metric', 'Value'], [
            ['Index Name', $elastic->getIndexName()],
            ['Document Count', number_format($stats['count'])],
            ['Size', number_format($stats['size_bytes']) . ' bytes'],
            ['Total Searches', number_format($stats['searches_total'])],
        ]);

        return self::SUCCESS;
    }

    private function search(ElasticsearchService $elastic): int
    {
        $query = $this->option('q');
        $limit = (int) $this->option('limit');

        if ($query === '') {
            $this->error('Search query required. Use: --q="query"');

            return self::FAILURE;
        }

        $this->info("Searching for: {$query}");

        $results = $elastic->search(['q' => $query, 'size' => $limit]);

        $this->info("Found {$results['total']} results:");

        foreach ($results['hits'] as $hit) {
            $this->line("  [{$hit['product_id']}] {$hit['name']} - \${$hit['base_price']}");
        }

        if (! empty($results['aggregations'])) {
            $this->info('Facets:');
            foreach ($results['aggregations'] as $name => $agg) {
                if (isset($agg['buckets'])) {
                    $buckets = array_slice($agg['buckets'], 0, 5);
                    foreach ($buckets as $bucket) {
                        $this->line("  {$name}: {$bucket['key']} ({$bucket['doc_count']})");
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}
