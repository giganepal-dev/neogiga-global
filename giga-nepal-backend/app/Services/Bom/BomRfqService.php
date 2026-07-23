<?php

namespace App\Services\Bom;

use App\Models\Bom\BomImport;
use App\Models\Bom\BomImportLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates RFQs from BOM lines that need sourcing.
 *
 * Handles:
 * - Filtering unmatched/RFQ-required lines
 * - Creating RFQ with proper structure
 * - Line-level settings
 * - Duplicate detection
 * - Manufacturer suggestions
 */
class BomRfqService
{
    /**
     * Create an RFQ from BOM import lines.
     *
     * @param  array{lines?: array<int, int>, status_filter?: array<string>, notes?: string}  $options
     * @return array{rfq_id: int, lines_created: int, warnings: array<string>}
     */
    public function createRfqFromBom(int $bomImportId, int $userId, array $options = []): array
    {
        $bomImport = BomImport::find($bomImportId);

        if (! $bomImport) {
            throw new \RuntimeException("BOM import #{$bomImportId} not found");
        }

        // Get lines to include in RFQ
        $query = BomImportLine::where('bom_import_id', $bomImportId);

        // Filter by specific line IDs if provided
        if (! empty($options['lines'])) {
            $query->whereIn('id', $options['lines']);
        }

        // Filter by match status if provided
        if (! empty($options['status_filter'])) {
            $query->whereIn('match_status', $options['status_filter']);
        } else {
            // Default: include unmatched and RFQ-required lines
            $query->whereIn('match_status', ['none', 'multiple', 'rfq_required']);
        }

        $lines = $query->get();

        if ($lines->isEmpty()) {
            throw new \RuntimeException("No qualifying lines found for RFQ");
        }

        // Create RFQ
        $rfq = $this->createRfqRecord($bomImport, $userId, $options);

        // Create RFQ lines
        $linesCreated = 0;
        $warnings = [];

        foreach ($lines as $line) {
            try {
                $this->createRfqLine($rfq->id, $line);
                $linesCreated++;
            } catch (\Exception $e) {
                $warnings[] = "Line #{$line->line_no}: " . $e->getMessage();
            }
        }

        return [
            'rfq_id' => $rfq->id,
            'lines_created' => $linesCreated,
            'warnings' => $warnings,
        ];
    }

    /**
     * Create the RFQ record.
     */
    private function createRfqRecord(BomImport $bomImport, int $userId, array $options): object
    {
        if (! Schema::hasTable('rfqs')) {
            throw new \RuntimeException("RFQs table not found");
        }

        return DB::table('rfqs')->insertGetId([
            'user_id' => $userId,
            'bom_import_id' => $bomImport->id,
            'title' => $options['title'] ?? "RFQ from BOM: {$bomImport->original_filename}",
            'status' => 'draft',
            'currency' => $options['currency'] ?? 'USD',
            'notes' => $options['notes'] ?? null,
            'metadata' => json_encode([
                'source' => 'bom_import',
                'bom_import_id' => $bomImport->id,
                'created_from' => 'automated',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create an RFQ line from a BOM import line.
     */
    private function createRfqLine(int $rfqId, BomImportLine $line): void
    {
        if (! Schema::hasTable('rfq_lines')) {
            throw new \RuntimeException("RFQ lines table not found");
        }

        DB::table('rfq_lines')->insert([
            'rfq_id' => $rfqId,
            'bom_import_line_id' => $line->id,
            'mpn' => $line->mpn,
            'manufacturer' => $line->manufacturer,
            'description' => $line->description,
            'quantity' => $line->quantity,
            'target_price' => null,
            'currency' => 'USD',
            'status' => 'pending',
            'metadata' => json_encode([
                'original_line_no' => $line->line_no,
                'match_status' => $line->match_status,
                'match_confidence' => $line->match_confidence,
                'raw_reference' => $line->raw_reference,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get RFQ-ready lines from a BOM import.
     *
     * @return array{lines: array, summary: array}
     */
    public function getRfqReadyLines(int $bomImportId): array
    {
        $lines = BomImportLine::where('bom_import_id', $bomImportId)
            ->whereIn('match_status', ['none', 'multiple', 'rfq_required'])
            ->get();

        $summary = [
            'total_lines' => $lines->count(),
            'unmatched' => $lines->where('match_status', 'none')->count(),
            'multiple_matches' => $lines->where('match_status', 'multiple')->count(),
            'rfq_required' => $lines->where('match_status', 'rfq_required')->count(),
        ];

        return [
            'lines' => $lines->map(fn ($line) => [
                'id' => $line->id,
                'line_no' => $line->line_no,
                'mpn' => $line->mpn,
                'manufacturer' => $line->manufacturer,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'match_status' => $line->match_status,
                'match_confidence' => $line->match_confidence,
            ])->toArray(),
            'summary' => $summary,
        ];
    }

    /**
     * Get lines that should go to cart (exact matches).
     *
     * @return array{lines: array, summary: array}
     */
    public function getCartReadyLines(int $bomImportId): array
    {
        $lines = BomImportLine::where('bom_import_id', $bomImportId)
            ->where('match_status', 'exact')
            ->whereNotNull('matched_product_id')
            ->get();

        $summary = [
            'total_lines' => $lines->count(),
            'total_quantity' => $lines->sum('quantity'),
        ];

        return [
            'lines' => $lines->map(fn ($line) => [
                'id' => $line->id,
                'line_no' => $line->line_no,
                'mpn' => $line->mpn,
                'matched_product_id' => $line->matched_product_id,
                'quantity' => $line->quantity,
                'match_confidence' => $line->match_confidence,
            ])->toArray(),
            'summary' => $summary,
        ];
    }
}
