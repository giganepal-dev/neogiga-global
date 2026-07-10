<?php

namespace App\Jobs;

use App\Services\Catalog\CatalogSearchRebuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RebuildApprovedImportSearchIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $jobId, public string $sourceCode = 'jlcpcb_parts_database')
    {
    }

    public function handle(CatalogSearchRebuildService $service): void
    {
        $service->rebuildApprovedImports($this->jobId, $this->sourceCode);
    }
}
