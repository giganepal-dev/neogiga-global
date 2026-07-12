<?php

namespace App\Console\Commands;

use App\Catalog\Ingestion\Persistence\CatalogDocumentStagingService;
use Illuminate\Console\Command;

class CatalogStageSupplierCsvCommand extends Command
{
    protected $signature = 'catalog:stage-supplier-csv
        {file : Absolute path to a normalized supplier quotation CSV}
        {--source=sunny_okystar_quotation_files : Document source code}
        {--dry-run : Validate rows and write a report without database changes}';

    protected $description = 'Stage a user-provided supplier quotation CSV into hidden pending-review catalogue records.';

    public function handle(CatalogDocumentStagingService $staging): int
    {
        try {
            $report = $staging->stage((string) $this->argument('file'), [
                'source' => (string) $this->option('source'),
                'source_file' => basename((string) $this->argument('file')),
                'dry_run' => (bool) $this->option('dry-run'),
            ]);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return in_array($report['status'], ['completed', 'completed_with_errors'], true) ? self::SUCCESS : self::FAILURE;
    }
}
