<?php

namespace App\Console\Commands;

use App\Jobs\CustomerImport\ProcessCustomerImportJob;
use App\Services\CustomerImport\CustomerImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportCustomersCommand extends Command
{
    protected $signature = 'neogiga:import-customers
        {path? : Spreadsheet file path; optional when --resume is supplied}
        {--profile=Customer Invoice Details : Saved import profile}
        {--sheet= : Worksheet name}
        {--country= : Force a canonical country name or ISO code}
        {--marketplace= : Marketplace ID, slug, or name}
        {--batch= : External batch identifier}
        {--only-valid : Import valid rows and retain invalid-row reports}
        {--update-existing : Fill blank fields on exact existing matches}
        {--no-marketing-consent : Explicitly preserve unknown/no marketing consent}
        {--source= : Source name}
        {--resume= : Resume an existing import ID, UUID, or batch key}
        {--dry-run : Validate and report without any database or file writes}
        {--queue : Dispatch processing to the dedicated imports queue}
        {--json : Print the report as JSON}';

    protected $description = 'Preview or idempotently import customer invoice contacts with provenance and no automatic marketing consent.';

    public function handle(CustomerImportService $imports): int
    {
        $path = $this->argument('path');
        $options = [
            'profile' => $this->option('profile'),
            'sheet' => $this->option('sheet'),
            'country' => $this->option('country'),
            'marketplace' => $this->option('marketplace'),
            'batch' => $this->option('batch'),
            'only_valid' => (bool) $this->option('only-valid'),
            'update_existing' => (bool) $this->option('update-existing'),
            'no_marketing_consent' => true,
            'source' => $this->option('source'),
            'resume' => $this->option('resume'),
            'dry_run' => (bool) $this->option('dry-run'),
        ];

        try {
            if ($this->option('queue') && ! $options['dry_run']) {
                ProcessCustomerImportJob::dispatch($path, $options)->onQueue('imports');
                $this->info('Customer import queued on the dedicated imports queue.');

                return self::SUCCESS;
            }

            $report = $imports->run($path, $options);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info($report['dry_run'] ? 'Customer import dry-run complete; no writes performed.' : 'Customer import complete.');
            $this->table(['Metric', 'Value'], collect($report)->only([
                'import_id', 'import_uuid', 'profile', 'worksheet', 'total_rows', 'valid_rows', 'imported_rows',
                'updated_rows', 'skipped_rows', 'duplicate_rows', 'warning_rows', 'error_rows', 'unresolved_countries', 'consent_state',
            ])->map(fn ($value, $key) => [$key, is_bool($value) ? ($value ? 'true' : 'false') : $value])->values()->all());
        }

        return $report['error_rows'] > 0 ? self::INVALID : self::SUCCESS;
    }
}
