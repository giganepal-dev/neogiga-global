<?php

namespace App\Jobs\CatalogImport;

use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ImportElecforestProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [10, 60, 180];

    /** @param array<string, mixed> $options */
    public function __construct(
        public readonly string $runId,
        public readonly string $sourceFile,
        public readonly int $lineNumber,
        public readonly array $options = [],
    ) {}

    public function handle(ElecforestImporter $importer): void
    {
        if (DB::table('catalog_import_runs')->where('id', $this->runId)->value('status') === 'paused') {
            $this->release(60);
            return;
        }
        $importer->importLineFromFile($this->sourceFile, $this->lineNumber, $this->runId, $this->options);
    }
}
