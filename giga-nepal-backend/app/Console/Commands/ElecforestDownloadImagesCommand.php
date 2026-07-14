<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Console\Command;

class ElecforestDownloadImagesCommand extends Command
{
    protected $signature = 'catalog:elecforest-download-images {--limit=0 : Maximum assets} {--sync : Download now} {--retry-failed : Include failed assets}';
    protected $description = 'Securely download ElecForest images into inactive rights-review storage';

    public function handle(ElecforestImporter $importer): int
    {
        $result = $importer->downloadImages((int) $this->option('limit'), (bool) $this->option('sync'), (bool) $this->option('retry-failed'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
