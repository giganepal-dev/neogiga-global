<?php

namespace App\Jobs\CatalogImport;

use App\Services\Catalog\CatalogSearchRebuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RebuildProductSearchIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 900;

    public function __construct(public readonly ?int $requestedBy = null) {}

    public function handle(CatalogSearchRebuildService $search): void
    {
        $jobId = $search->createJob($this->requestedBy, 'elecforest');
        $search->rebuildApprovedImports($jobId, 'elecforest');
    }
}
