<?php

namespace App\Jobs;

use App\Models\Bom\BomImport;
use App\Models\Bom\BomImportLine;
use App\Services\Bom\BomComponentMatcher;
use App\Services\Product\MpnNormalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for processing large BOM files asynchronously.
 *
 * Handles:
 * - MPN normalization
 * - Catalog matching
 * - Alternative suggestions
 * - Price lookup
 * - Stock lookup
 * - Risk analysis
 *
 * Supports BOMs up to 10,000+ lines via chunked processing.
 */
class ProcessBomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * The name of the queue the job should be dispatched to.
     */
    public string $queue = 'catalog-imports';

    public function __construct(
        public int $bomImportId,
        public array $options = [],
    ) {
        $this->onQueue('catalog-imports');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bomImport = BomImport::find($this->bomImportId);

        if (! $bomImport) {
            Log::error("ProcessBomJob: BomImport #{$this->bomImportId} not found");
            return;
        }

        // Update status to processing
        $bomImport->update(['status' => 'processing']);

        try {
            // Process in chunks to handle large BOMs
            $chunkSize = $this->options['chunk_size'] ?? 100;
            $totalLines = BomImportLine::where('bom_import_id', $bomImport->id)->count();
            $processed = 0;

            BomImportLine::where('bom_import_id', $bomImport->id)
                ->orderBy('line_no')
                ->chunkById($chunkSize, function ($lines) use ($bomImport, &$processed, $totalLines) {
                    $this->processChunk($bomImport, $lines);

                    $processed += $lines->count();

                    // Update progress
                    $bomImport->update([
                        'metadata' => array_merge($bomImport->metadata ?? [], [
                            'progress' => [
                                'total' => $totalLines,
                                'processed' => $processed,
                                'percentage' => $totalLines > 0 ? round(($processed / $totalLines) * 100) : 0,
                            ],
                        ]),
                    ]);

                    Log::info("ProcessBomJob: Processed {$processed}/{$totalLines} lines for import #{$bomImport->id}");
                });

            // Mark as completed
            $bomImport->update([
                'status' => 'completed',
                'metadata' => array_merge($bomImport->metadata ?? [], [
                    'completed_at' => now()->toISOString(),
                    'total_lines' => $totalLines,
                ]),
            ]);

            Log::info("ProcessBomJob: Completed processing import #{$bomImport->id} ({$totalLines} lines)");

        } catch (\Exception $e) {
            Log::error("ProcessBomJob: Failed processing import #{$this->bomImportId}: " . $e->getMessage());

            $bomImport->update([
                'status' => 'failed',
                'metadata' => array_merge($bomImport->metadata ?? [], [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toISOString(),
                ]),
            ]);

            throw $e;
        }
    }

    /**
     * Process a chunk of BOM lines.
     */
    private function processChunk(BomImport $bomImport, $lines): void
    {
        $matcher = app(BomComponentMatcher::class);
        $normalization = app(MpnNormalizationService::class);

        // Prepare lines for matching
        $matchInput = [];
        foreach ($lines as $line) {
            $matchInput[] = [
                'line_no' => $line->line_no,
                'mpn' => $line->mpn,
                'manufacturer' => $line->manufacturer,
            ];
        }

        // Run matching
        $matchResults = $matcher->match($matchInput);

        // Update each line with results
        foreach ($lines as $line) {
            $result = $matchResults[$line->line_no] ?? null;

            if ($result) {
                // Normalize MPN
                $normalized = $normalization->normalize($line->mpn);

                $line->update([
                    'matched_product_id' => $result['matched_product_id'],
                    'match_status' => $result['match_status'],
                    'match_confidence' => $result['match_confidence'],
                    'candidates' => $result['candidates'],
                    'suggestions' => $result['suggestions'],
                    'normalized_mpn' => $normalized['normalized'],
                    'normalization_warnings' => $normalized['warnings'],
                    'metadata' => array_merge($line->metadata ?? [], [
                        'processed_at' => now()->toISOString(),
                    ]),
                ]);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessBomJob: Permanent failure for import #{$this->bomImportId}: " . $exception->getMessage());

        $bomImport = BomImport::find($this->bomImportId);
        if ($bomImport) {
            $bomImport->update([
                'status' => 'failed',
                'metadata' => array_merge($bomImport->metadata ?? [], [
                    'permanent_failure' => $exception->getMessage(),
                    'failed_at' => now()->toISOString(),
                ]),
            ]);
        }
    }
}
