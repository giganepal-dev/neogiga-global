<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Console\Command;

class ElecforestResumeCommand extends Command
{
    protected $signature = 'catalog:elecforest-resume {--run-id= : Run UUID} {--queue : Continue through database queue} {--limit=0 : Maximum additional rows}';
    protected $description = 'Resume an interrupted ElecForest import from its last checkpoint line';

    public function handle(ElecforestImporter $importer): int
    {
        $runId = trim((string) $this->option('run-id'));
        if ($runId === '') {
            $this->error('--run-id is required.');
            return self::FAILURE;
        }
        try {
            $result = $importer->resume($runId, ['queue' => (bool) $this->option('queue'), 'sync' => ! $this->option('queue'), 'limit' => (int) $this->option('limit')]);
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
