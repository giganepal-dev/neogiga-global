<?php

namespace App\Jobs\CustomerImport;

use App\Services\CustomerImport\CustomerImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCustomerImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 1800;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public ?string $path, public array $options = [])
    {
        $this->onQueue('imports');
    }

    public function handle(CustomerImportService $imports): void
    {
        $imports->run($this->path, $this->options + ['dry_run' => false]);
    }
}
