<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Console\Command;

class ElecforestRetryCommand extends Command
{
    protected $signature = 'catalog:elecforest-retry {--run-id= : Run UUID}';
    protected $description = 'Retry unresolved ElecForest import failures';

    public function handle(ElecforestImporter $importer): int
    {
        $runId = trim((string) $this->option('run-id'));
        if ($runId === '') {
            $this->error('--run-id is required.');
            return self::FAILURE;
        }
        try {
            $this->line(json_encode($importer->retryFailures($runId), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
