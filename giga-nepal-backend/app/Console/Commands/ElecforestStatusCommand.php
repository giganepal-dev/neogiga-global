<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ElecforestStatusCommand extends Command
{
    protected $signature = 'catalog:elecforest-status {--run-id= : Show one run} {--limit=20 : Recent runs} {--json : Emit JSON}';
    protected $description = 'Show ElecForest import runs, counters and validation totals';

    public function handle(ElecforestImporter $importer): int
    {
        $runId = trim((string) $this->option('run-id'));
        $runs = DB::table('catalog_import_runs as r')->join('catalog_sources as s', 's.id', '=', 'r.catalog_source_id')
            ->where('s.code', config('elecforest_import.source_code'))
            ->when($runId !== '', fn ($query) => $query->where('r.id', $runId))
            ->select(['r.*'])->latest('r.created_at')->limit(max(1, (int) $this->option('limit')))->get();
        $result = ['runs' => $runs, 'validation' => $importer->validateImported()];
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return self::SUCCESS;
    }
}
