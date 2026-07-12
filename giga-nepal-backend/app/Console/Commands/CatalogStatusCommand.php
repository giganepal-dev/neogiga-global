<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalogStatusCommand extends Command
{
    protected $signature = 'catalog:status';

    protected $description = 'Display supplier catalogue policy, import, and review status.';

    public function handle(): int
    {
        if (! Schema::hasTable('catalog_sources')) {
            $this->error('Catalogue source tables have not been migrated.');

            return self::FAILURE;
        }
        $rows = DB::table('catalog_sources')->whereIn('code', array_keys(config('catalog_import.suppliers')))
            ->select('code', 'status', 'import_enabled', 'media_download_enabled', 'last_successful_sync_at', 'last_failed_sync_at')->get()
            ->map(fn ($row) => [(string) $row->code, (string) ($row->status ?? 'not audited'), $row->import_enabled ? 'yes' : 'no', $row->media_download_enabled ? 'yes' : 'no', $row->last_successful_sync_at ?: '-', $row->last_failed_sync_at ?: '-'])->all();
        $this->table(['Supplier', 'Policy', 'Import', 'Media', 'Last success', 'Last failure'], $rows);

        return self::SUCCESS;
    }
}
