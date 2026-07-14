<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Console\Command;

class ImportElecforestProducts extends Command
{
    protected $signature = 'catalog:import-elecforest
        {--file= : JSONL source file}
        {--limit=0 : Maximum source records}
        {--start-line=1 : One-based source line}
        {--chunk=100 : Queue planning chunk size}
        {--dry-run : Validate and map without database writes}
        {--sync : Process synchronously}
        {--queue : Dispatch one queue job per source record}
        {--resume : Continue an existing run}
        {--retry-failed : Retry unresolved failures for an existing run}
        {--run-id= : Existing run UUID}
        {--only-new : Do not update linked products}
        {--update-existing : Update only ElecForest-managed linked products}
        {--rewrite-content : Regenerate deterministic NeoGiga content}
        {--generate-seo : Generate complete editable draft SEO}
        {--download-images : Download allowlisted images for internal rights review}
        {--skip-images : Do not create download work}
        {--skip-prices : Do not record supplier price observations}
        {--skip-inventory : Retain external availability only; never write warehouse inventory}
        {--publish-qualified : Publish only records passing every publication gate}
        {--draft-all : Keep every imported record in draft/review state}
        {--source-category= : Optional source category path to map}
        {--neo-category= : Existing NeoGiga category name or slug}
        {--country= : Context only; does not create regional stock or selling prices}
        {--currency= : Context only; source currency remains provenance}
        {--force : Explicitly bypass a qualified-publication gate}';

    protected $description = 'Production-safe, resumable ElecForest JSONL catalog import';

    public function handle(ElecforestImporter $importer): int
    {
        if ($this->option('sync') && $this->option('queue')) {
            $this->error('Choose either --sync or --queue, not both.');
            return self::FAILURE;
        }
        $file = (string) ($this->option('file') ?: config('elecforest_import.default_file'));
        $options = $this->optionsArray();

        try {
            if ($this->option('source-category') && $this->option('neo-category')) {
                [$main, $subcategory] = $this->sourceCategoryParts((string) $this->option('source-category'));
                $mapping = $importer->mapCategory($main, $subcategory, (string) $this->option('neo-category'));
                $this->line(json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
            if ($this->option('resume')) {
                $runId = $this->requiredRunId();
                $result = $importer->resume($runId, $options);
            } elseif ($this->option('retry-failed')) {
                $result = $importer->retryFailures($this->requiredRunId(), $options);
            } elseif ($this->option('queue') && ! $this->option('dry-run')) {
                $result = $importer->queueFile($file, $options);
            } else {
                $result = $importer->importFile($file, $options);
            }
            if ($this->option('publish-qualified') && ! $this->option('dry-run')) {
                $result['publication'] = ($result['status'] ?? null) === 'queued'
                    ? ['status' => 'deferred', 'reason' => 'Run catalog:elecforest-publish-qualified after the queued run is complete and validated.']
                    : $importer->publishQualified((bool) $this->option('force'), $result['run_id'] ?? null);
            }
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }

    /** @return array<string, mixed> */
    private function optionsArray(): array
    {
        return [
            'file' => $this->option('file'), 'limit' => (int) $this->option('limit'),
            'start_line' => (int) $this->option('start-line'), 'chunk' => max(1, (int) $this->option('chunk')),
            'dry_run' => (bool) $this->option('dry-run'), 'sync' => (bool) ($this->option('sync') || ! $this->option('queue')),
            'queue' => (bool) $this->option('queue'), 'only_new' => (bool) $this->option('only-new'),
            'update_existing' => (bool) $this->option('update-existing'), 'rewrite_content' => (bool) $this->option('rewrite-content'),
            'generate_seo' => true, 'download_images' => (bool) $this->option('download-images'),
            'skip_images' => (bool) $this->option('skip-images'), 'skip_prices' => (bool) $this->option('skip-prices'),
            'skip_inventory' => true, 'publish_qualified' => (bool) $this->option('publish-qualified'),
            'draft_all' => (bool) ($this->option('draft-all') || ! $this->option('publish-qualified')),
            'country' => $this->option('country'), 'currency' => $this->option('currency'),
            'force' => (bool) $this->option('force'), 'run_id' => $this->option('run-id'),
        ];
    }

    private function requiredRunId(): string
    {
        $runId = trim((string) $this->option('run-id'));
        if ($runId === '') {
            throw new \InvalidArgumentException('--run-id is required for resume or retry.');
        }
        return $runId;
    }

    /** @return array{0:string,1:string} */
    private function sourceCategoryParts(string $path): array
    {
        $parts = preg_split('/\s*(?:\/|>|\|)\s*/', trim($path), 2) ?: [];

        return [trim($parts[0] ?? ''), trim($parts[1] ?? '')];
    }
}
