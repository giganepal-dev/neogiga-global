<?php

namespace App\Console\Commands;

use App\Jobs\CatalogImport\RebuildProductSearchIndexJob;
use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Console\Command;

class ElecforestPublishQualifiedCommand extends Command
{
    protected $signature = 'catalog:elecforest-publish-qualified {--run-id= : Limit publication checks to one import run} {--force : Explicitly bypass review gates}';
    protected $description = 'Publish only ElecForest products that pass all configured catalog gates';

    public function handle(ElecforestImporter $importer): int
    {
        $result = $importer->publishQualified((bool) $this->option('force'), trim((string) $this->option('run-id')) ?: null);
        if ($result['published'] > 0) {
            RebuildProductSearchIndexJob::dispatch()->onQueue((string) config('elecforest_import.queue'));
        }
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return self::SUCCESS;
    }
}
