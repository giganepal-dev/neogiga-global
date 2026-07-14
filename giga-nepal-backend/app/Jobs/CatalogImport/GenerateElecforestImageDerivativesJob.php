<?php

namespace App\Jobs\CatalogImport;

use App\Services\CatalogImport\Elecforest\ElecforestMediaImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateElecforestImageDerivativesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public readonly int $imageId) {}

    public function handle(ElecforestMediaImporter $importer): void
    {
        $importer->generateDerivatives($this->imageId);
    }
}
