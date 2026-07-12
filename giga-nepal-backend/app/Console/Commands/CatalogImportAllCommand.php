<?php

namespace App\Console\Commands;

use App\Catalog\Ingestion\Persistence\CatalogImportService;
use Illuminate\Console\Command;

class CatalogImportAllCommand extends Command
{
    protected $signature = 'catalog:import-all {--resume} {--dry-run} {--without-media} {--queue}';

    protected $description = 'Run independent policy-gated imports for every configured supplier.';

    public function handle(CatalogImportService $imports): int
    {
        $failed = false;
        foreach (array_keys(config('catalog_import.suppliers')) as $supplier) {
            $report = $imports->run($supplier, ['dry_run' => (bool) $this->option('dry-run'), 'resume' => (bool) $this->option('resume'), 'without-media' => (bool) $this->option('without-media')]);
            $this->line("{$supplier}: {$report['status']} ({$report['report_path']})");
            $failed = $failed || $report['status'] === 'failed';
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
