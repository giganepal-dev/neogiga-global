<?php

namespace App\Console\Commands;

use App\Catalog\Ingestion\Persistence\CatalogImportService;
use App\Jobs\RunSupplierCatalogImport;
use Illuminate\Console\Command;

class CatalogImportCommand extends Command
{
    protected $signature = 'catalog:import {supplier : adafruit, waveshare, or okystar} {--resume} {--limit=} {--category=} {--product=} {--dry-run} {--refresh} {--without-media} {--only-media} {--since=} {--queue}';

    protected $description = 'Run a policy-gated, resumable supplier catalogue import. Imports default to pending review.';

    public function handle(CatalogImportService $imports): int
    {
        $options = $this->options();
        $options['dry_run'] = (bool) $this->option('dry-run');
        if ($this->option('queue') && ! $options['dry_run']) {
            RunSupplierCatalogImport::dispatch((string) $this->argument('supplier'), $options);
            $this->info('Import queued. It will remain blocked until source policy is explicitly approved.');

            return self::SUCCESS;
        }
        $report = $imports->run((string) $this->argument('supplier'), $options);
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $report['status'] === 'completed' ? self::SUCCESS : self::FAILURE;
    }
}
