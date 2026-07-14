<?php

namespace App\Jobs\CatalogImport;

use App\Services\CatalogImport\Elecforest\ElecforestMediaImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DownloadElecforestProductImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;
    public array $backoff = [30, 180, 900];

    public function __construct(public readonly int $assetId) {}

    public function handle(ElecforestMediaImporter $importer): void
    {
        $importer->downloadAsset($this->assetId);
    }
}
