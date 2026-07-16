<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Ti\TiParametricCatalogImporter;
use Illuminate\Console\Command;

class ImportTiParametricCatalog extends Command
{
    protected $signature = 'catalog:import-ti-parametrics
        {--file= : Supplied TI Products.csv file}
        {--limit=0 : Maximum source records}
        {--dry-run : Validate the source without database writes}
        {--publish : Publish as quote-only after source validation}';

    protected $description = 'Safely import Texas Instruments parametric products into the NeoGiga canonical catalog';

    public function handle(TiParametricCatalogImporter $importer): int
    {
        $file = (string) ($this->option('file') ?: base_path('../data/NeoGiga_TI_Amplifiers_Import_Ready/Products.csv'));

        try {
            $result = $importer->import($file, [
                'dry_run' => (bool) $this->option('dry-run'),
                'limit' => (int) $this->option('limit'),
                'publish' => (bool) $this->option('publish'),
            ]);
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $result['skipped_invalid'] === 0 ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
