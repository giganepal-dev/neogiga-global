<?php

namespace App\Jobs;

use App\Services\Catalog\CatalogSearchRebuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RebuildApprovedImportSearchIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CONNECTION = 'database_imports';

    public const QUEUE = 'catalog-imports';

    public int $timeout = 3300;

    public bool $failOnTimeout = false;

    public function __construct(public int $jobId, public string $sourceCode = 'jlcpcb_parts_database')
    {
        $this->onConnection(self::CONNECTION);
        $this->onQueue(self::QUEUE);
    }

    public function handle(CatalogSearchRebuildService $service): void
    {
        $service->rebuildApprovedImports($this->jobId, $this->sourceCode);
    }

    public function failed(Throwable $exception): void
    {
        app(CatalogSearchRebuildService::class)->markFailed($this->jobId, $exception);
    }
}
