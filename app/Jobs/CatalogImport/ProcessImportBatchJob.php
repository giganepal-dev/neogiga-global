<?php

namespace NeoGiga\CatalogImport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use NeoGiga\CatalogImport\Models\ImportBatch;
use NeoGiga\CatalogImport\Models\ImportRow;
use NeoGiga\CatalogImport\Services\Processors\ImportProcessorService;

class ProcessImportBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $batchId;
    protected int $retryCount = 0;

    public function __construct(int $batchId)
    {
        $this->batchId = $batchId;
        $this->onQueue('catalog-import');
    }

    public function handle(ImportProcessorService $processor): void
    {
        $batch = ImportBatch::findOrFail($this->batchId);
        
        // Check if batch is already processed or cancelled
        if (in_array($batch->status, ['completed', 'cancelled', 'failed'])) {
            Log::warning("Attempted to process batch in terminal state", [
                'batch_id' => $batch->id,
                'status' => $batch->status
            ]);
            return;
        }

        // Update status to processing
        $batch->update([
            'status' => 'processing',
            'started_at' => now()
        ]);

        try {
            // Use database transaction for atomicity
            DB::transaction(function () use ($batch, $processor) {
                // Get pending rows for this batch
                $rows = ImportRow::where('import_batch_id', $batch->id)
                    ->where('status', 'pending')
                    ->limit($batch->chunk_size ?? 500)
                    ->lockForUpdate()
                    ->get();

                if ($rows->isEmpty()) {
                    $batch->update([
                        'status' => 'completed',
                        'completed_at' => now()
                    ]);
                    return;
                }

                Log::info("Processing batch chunk", [
                    'batch_id' => $batch->id,
                    'row_count' => $rows->count()
                ]);

                // Process each row through the ETL pipeline
                foreach ($rows as $row) {
                    try {
                        $processor->processRow($row, $batch);
                        $row->update(['status' => 'processed']);
                        $batch->increment('processed_rows');
                    } catch (\Exception $e) {
                        $row->update([
                            'status' => 'failed',
                            'error_message' => $e->getMessage()
                        ]);
                        $batch->increment('failed_rows');
                        
                        Log::error("Row processing failed", [
                            'row_id' => $row->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Check if more rows remain
                $remaining = ImportRow::where('import_batch_id', $batch->id)
                    ->where('status', 'pending')
                    ->count();

                if ($remaining === 0) {
                    $batch->update([
                        'status' => 'completed',
                        'completed_at' => now()
                    ]);
                    
                    // Trigger post-batch processing (review queue, indexing)
                    dispatch(new PostBatchCompletionJob($batch->id));
                } else {
                    // Chain next batch job
                    dispatch(new self($batch->id));
                }
            });

        } catch (\Throwable $e) {
            Log::error("Batch processing failed", [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->retryCount++;
            
            if ($this->retryCount >= 3) {
                $batch->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'failure_reason' => $e->getMessage()
                ]);
                
                // Don't retry after 3 attempts
                throw $e;
            }
            
            // Release with delay for retry
            $this->release(60 * $this->retryCount);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Import batch job permanently failed", [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage()
        ]);

        ImportBatch::find($this->batchId)?->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $exception->getMessage()
        ]);
    }
}
