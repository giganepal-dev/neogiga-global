<?php

namespace App\Jobs;

use App\Catalog\Ingestion\Persistence\CatalogImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RunSupplierCatalogImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public array $backoff = [60, 300, 900];

    public function __construct(public readonly string $supplier, public readonly array $options = [])
    {
        $this->onQueue('catalog-'.$supplier);
    }

    public function handle(CatalogImportService $imports): void
    {
        $lock = Cache::lock('catalog-import:'.$this->supplier, 900);
        if (! $lock->get()) {
            $this->release(60);

            return;
        }
        try {
            $imports->run($this->supplier, $this->options + ['queue' => false]);
        } finally {
            $lock->release();
        }
    }
}
