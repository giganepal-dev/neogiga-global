<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Console\Command;

class ElecforestValidateCommand extends Command
{
    protected $signature = 'catalog:elecforest-validate {--run-id= : Validate one import run} {--json : Emit JSON}';
    protected $description = 'Validate ElecForest linkage, draft/SEO coverage and price/inventory isolation';

    public function handle(ElecforestImporter $importer): int
    {
        $result = $importer->validateImported(trim((string) $this->option('run-id')) ?: null);
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $result['isolation_passed'] ? self::SUCCESS : self::FAILURE;
    }
}
