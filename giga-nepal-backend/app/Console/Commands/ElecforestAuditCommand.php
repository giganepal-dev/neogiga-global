<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Console\Command;

class ElecforestAuditCommand extends Command
{
    protected $signature = 'catalog:elecforest-audit {--file= : JSONL source file} {--json : Emit JSON}';
    protected $description = 'Audit ElecForest JSONL validity, coverage and duplicate identifiers';

    public function handle(ElecforestImporter $importer): int
    {
        try {
            $audit = $importer->audit((string) ($this->option('file') ?: config('elecforest_import.default_file')));
            $this->line(json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $audit['malformed'] === 0 && $audit['utf8_invalid'] === 0 ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
