<?php

namespace App\Services\Bom;

use App\Models\Bom\BomImport;
use App\Models\Bom\BomImportLine;
use App\Models\Bom\BomRiskScore;
use App\Models\Product\ProductAlternative;
use App\Services\Product\AlternativePartsService;
use App\Services\Product\MpnNormalizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive BOM processing service.
 *
 * Orchestrates the entire BOM processing pipeline:
 * 1. Parse input (CSV, paste, text)
 * 2. Normalize MPNs
 * 3. Match against catalog
 * 4. Find alternatives
 * 5. Calculate risk scores
 * 6. Generate sourcing recommendations
 */
class BomProcessingService
{
    public function __construct(
        private BomImportParser $parser,
        private BomComponentMatcher $matcher,
        private MpnNormalizationService $normalization,
        private AlternativePartsService $alternatives,
    ) {}

    /**
     * Process a BOM from raw text content.
     *
     * @return array{import_id: int, status: string, stats: array}
     */
    public function processText(string $content, int $userId, array $options = []): array
    {
        // Create import record
        $import = BomImport::create([
            'user_id' => $userId,
            'name' => $options['name'] ?? 'BOM Import ' . now()->format('Y-m-d H:i'),
            'original_filename' => $options['filename'] ?? null,
            'source_format' => $this->parser->detectFormat($content),
            'status' => 'processing',
            'currency' => $options['currency'] ?? 'USD',
            'raw_content' => $content,
        ]);

        try {
            // Parse the content
            $parsed = $this->parser->parse($content);

            if (empty($parsed['lines'])) {
                $import->update([
                    'status' => 'failed',
                    'error_message' => 'No parseable lines found in input',
                ]);

                return [
                    'import_id' => $import->id,
                    'status' => 'failed',
                    'stats' => ['error' => 'No parseable lines'],
                ];
            }

            // Merge duplicates if requested
            $lines = $parsed['lines'];
            if ($options['merge_duplicates'] ?? true) {
                $lines = $this->parser->mergeDuplicates($lines);
            }

            // Create import lines
            $importLines = $this->createImportLines($import->id, $lines);

            // Process matching
            $matchResults = $this->matcher->match($lines);

            // Update lines with match results and normalization
            $this->updateLineResults($importLines, $matchResults, $lines);

            // Calculate statistics
            $stats = $this->calculateStats($import->id, $importLines);

            // Update import record
            $import->update([
                'status' => 'completed',
                'total_lines' => $stats['total_lines'],
                'matched_lines' => $stats['exact_matches'] + $stats['alias_matches'],
                'unmatched_lines' => $stats['unmatched'],
                'processed_at' => now(),
                'metadata' => [
                    'stats' => $stats,
                    'parsed_info' => $this->parser->getStats($parsed),
                ],
            ]);

            Log::info("BOM Processing: Completed import #{$import->id}", $stats);

            return [
                'import_id' => $import->id,
                'status' => 'completed',
                'stats' => $stats,
            ];

        } catch (\Exception $e) {
            Log::error("BOM Processing: Failed import #{$import->id}: " . $e->getMessage());

            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process a BOM from uploaded file.
     */
    public function processFile(string $filePath, int $userId, array $options = []): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Could not read file: {$filePath}");
        }

        return $this->processText($content, $userId, array_merge($options, [
            'filename' => basename($filePath),
        ]));
    }

    /**
     * Create import lines from parsed data.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, BomImportLine>
     */
    private function createImportLines(int $importId, array $lines): \Illuminate\Database\Eloquent\Collection
    {
        $records = [];
        foreach ($lines as $line) {
            $records[] = [
                'bom_import_id' => $importId,
                'line_no' => $line['line_no'],
                'mpn' => $line['mpn'] ?? null,
                'manufacturer' => $line['manufacturer'] ?? null,
                'description' => $line['description'] ?? null,
                'raw_reference' => $line['raw_reference'] ?? null,
                'quantity' => $line['quantity'] ?? 1,
                'package_type' => $line['package'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Batch insert for performance
        BomImportLine::insert($records);

        return BomImportLine::where('bom_import_id', $importId)
            ->orderBy('line_no')
            ->get();
    }

    /**
     * Update import lines with match results.
     */
    private function updateLineResults($importLines, array $matchResults, array $parsedLines): void
    {
        foreach ($importLines as $line) {
            $result = $matchResults[$line->line_no] ?? null;
            $parsedLine = collect($parsedLines)->firstWhere('line_no', $line->line_no);

            if ($result) {
                $line->update([
                    'matched_product_id' => $result['matched_product_id'],
                    'match_status' => $result['match_status'],
                    'match_confidence' => $result['match_confidence'],
                    'candidates' => $result['candidates'] ?? null,
                    'suggestions' => $result['suggestions'] ?? null,
                    'normalized_mpn' => $result['normalized_mpn'] ?? null,
                    'normalization_warnings' => $result['warnings'] ?? null,
                ]);
            }
        }
    }

    /**
     * Calculate processing statistics.
     */
    private function calculateStats(int $importId, $importLines): array
    {
        $total = $importLines->count();
        $exactMatches = $importLines->where('match_status', 'exact')->count();
        $aliasMatches = $importLines->where('match_status', 'alias_match')->count();
        $multipleMatches = $importLines->where('match_status', 'multiple')->count();
        $likelyMatches = $importLines->where('match_status', 'likely_match')->count();
        $partialMatches = $importLines->where('match_status', 'partial_match')->count();
        $unmatched = $importLines->where('match_status', 'none')->count();
        $invalidInput = $importLines->where('match_status', 'invalid_input')->count();

        $withWarnings = $importLines->filter(fn ($l) => ! empty($l->normalization_warnings))->count();

        return [
            'total_lines' => $total,
            'exact_matches' => $exactMatches,
            'alias_matches' => $aliasMatches,
            'multiple_matches' => $multipleMatches,
            'likely_matches' => $likelyMatches,
            'partial_matches' => $partialMatches,
            'unmatched' => $unmatched,
            'invalid_input' => $invalidInput,
            'with_warnings' => $withWarnings,
            'match_rate' => $total > 0 ? round((($exactMatches + $aliasMatches) / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get detailed results for a BOM import.
     */
    public function getResults(int $importId): array
    {
        $import = BomImport::findOrFail($importId);

        $lines = BomImportLine::where('bom_import_id', $importId)
            ->orderBy('line_no')
            ->get();

        $grouped = [
            'exact' => $lines->where('match_status', 'exact'),
            'alias' => $lines->where('match_status', 'alias_match'),
            'multiple' => $lines->where('match_status', 'multiple'),
            'likely' => $lines->where('match_status', 'likely_match'),
            'partial' => $lines->where('match_status', 'partial_match'),
            'unmatched' => $lines->where('match_status', 'none'),
            'invalid' => $lines->where('match_status', 'invalid_input'),
        ];

        return [
            'import' => $import,
            'lines' => $lines,
            'grouped' => $grouped,
            'stats' => $import->metadata['stats'] ?? [],
        ];
    }

    /**
     * Approve or change a match for a specific line.
     */
    public function approveMatch(int $importId, int $lineNo, int $productId, ?string $notes = null): BomImportLine
    {
        $line = BomImportLine::where('bom_import_id', $importId)
            ->where('line_no', $lineNo)
            ->firstOrFail();

        $line->update([
            'matched_product_id' => $productId,
            'match_status' => 'approved',
            'match_confidence' => 100,
            'reviewer_notes' => $notes,
            'requires_review' => false,
        ]);

        return $line;
    }

    /**
     * Get lines ready for RFQ (unmatched or needs review).
     */
    public function getRfqReadyLines(int $importId): array
    {
        return BomImportLine::where('bom_import_id', $importId)
            ->whereIn('match_status', ['none', 'multiple', 'likely_match', 'partial_match'])
            ->orderBy('line_no')
            ->get()
            ->toArray();
    }

    /**
     * Get lines ready for cart (exact matches).
     */
    public function getCartReadyLines(int $importId): array
    {
        return BomImportLine::where('bom_import_id', $importId)
            ->where('match_status', 'exact')
            ->whereNotNull('matched_product_id')
            ->orderBy('line_no')
            ->get()
            ->toArray();
    }
}
