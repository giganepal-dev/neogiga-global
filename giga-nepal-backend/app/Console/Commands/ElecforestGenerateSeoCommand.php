<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Console\Command;

class ElecforestGenerateSeoCommand extends Command
{
    protected $signature = 'catalog:elecforest-generate-seo {--limit=0 : Maximum imported products}';
    protected $description = 'Regenerate complete editable draft SEO for ElecForest products';

    public function handle(ElecforestImporter $importer): int
    {
        $this->line(json_encode($importer->generateSeoForImported((int) $this->option('limit')), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return self::SUCCESS;
    }
}
