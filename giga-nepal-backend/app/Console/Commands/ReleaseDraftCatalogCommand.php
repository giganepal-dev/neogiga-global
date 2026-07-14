<?php

namespace App\Console\Commands;

use App\Jobs\CatalogImport\RebuildProductSearchIndexJob;
use App\Services\Catalog\DraftCatalogReleaseService;
use Illuminate\Console\Command;
use Throwable;

class ReleaseDraftCatalogCommand extends Command
{
    protected $signature = 'catalog:release-drafts
        {--apply : Execute the governed release; omitted means read-only dry run}
        {--expected-count= : Exact eligible count printed by the latest dry run}
        {--expected-plan-hash= : Exact SHA-256 plan hash printed by the latest dry run}
        {--backup-reference= : Verified immutable database/storage backup identifier}
        {--acknowledge-media-publication-risk : Acknowledge that file integrity is verified but media licensing is not independently verified}
        {--chunk= : Products per bounded processing chunk (1-500)}';

    protected $description = 'Dry-run and optionally release verified ElecForest drafts with governed pricing, media and stock allocation';

    public function handle(DraftCatalogReleaseService $release): int
    {
        try {
            $plan = $release->plan();
            $this->line(json_encode($release->forOutput($plan), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            if (! (bool) $this->option('apply')) {
                $this->newLine();
                $this->info('Dry run only: no database rows, media state, inventory, prices, reports or caches were changed.');
                $this->comment('To apply, take and verify a backup, then repeat with --apply, --expected-count, --expected-plan-hash, --backup-reference and --acknowledge-media-publication-risk.');

                return self::SUCCESS;
            }

            $expectedCount = filter_var($this->option('expected-count'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($expectedCount === false) {
                throw new \RuntimeException('--expected-count must be a non-negative integer copied from the current dry run.');
            }
            $expectedHash = strtolower(trim((string) $this->option('expected-plan-hash')));
            if (preg_match('/^[a-f0-9]{64}$/', $expectedHash) !== 1) {
                throw new \RuntimeException('--expected-plan-hash must be the 64-character SHA-256 hash copied from the current dry run.');
            }
            $chunk = $this->option('chunk');
            if ($chunk !== null && $chunk !== '' && filter_var($chunk, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]) === false) {
                throw new \RuntimeException('--chunk must be an integer from 1 to 500.');
            }

            $result = $release->apply($plan, [
                'expected_count' => (int) $expectedCount,
                'expected_plan_hash' => $expectedHash,
                'backup_reference' => trim((string) $this->option('backup-reference')),
                'acknowledge_media_publication_risk' => (bool) $this->option('acknowledge-media-publication-risk'),
                'chunk_size' => ($chunk === null || $chunk === '') ? (int) config('catalog_release.chunk_size', 100) : (int) $chunk,
            ]);

            if ((int) ($result['released_products'] ?? 0) > 0) {
                RebuildProductSearchIndexJob::dispatch()->onQueue((string) config('elecforest_import.queue', 'catalog-imports'));
            }
            $this->newLine();
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->info('Catalog release completed. The immutable report path is shown above; no deployment was performed by this command.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
