<?php

namespace App\Jobs\Email\Import;

use App\Models\EmailImport;
use App\Services\Email\Import\SubscriberImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour timeout for large imports
    public int $tries = 1;
    
    protected EmailImport $import;

    public function __construct(EmailImport $import)
    {
        $this->import = $import;
    }

    public function handle(SubscriberImportService $importService): void
    {
        try {
            Log::info("Starting import processing for Import #{$this->import->id}");
            
            $this->import->markAsProcessing();

            // Step 1: Parse the file
            Log::info("Parsing file for Import #{$this->import->id}");
            $totalRows = $importService->parseFile($this->import);
            Log::info("Parsed {$totalRows} rows from Import #{$this->import->id}");

            // Step 2: Validate and map rows
            Log::info("Validating rows for Import #{$this->import->id}");
            $importService->validateAndMapRows($this->import);

            // Step 3: Process the import
            Log::info("Processing import for Import #{$this->import->id}");
            $importService->processImport($this->import);

            Log::info("Import #{$this->import->id} completed successfully");
            
        } catch (\Exception $e) {
            Log::error("Import #{$this->import->id} failed: " . $e->getMessage());
            $this->import->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Import job failed permanently: " . $exception->getMessage(), [
            'import_id' => $this->import->id,
        ]);

        $this->import->markAsFailed($exception->getMessage());
    }
}
