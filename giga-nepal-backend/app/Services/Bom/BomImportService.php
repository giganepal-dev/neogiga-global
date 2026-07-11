<?php

namespace App\Services\Bom;

use App\Models\Bom\BomImport;
use App\Models\Bom\BomImportLine;
use App\Models\Erp\RfqRequest;
use App\Models\Marketplace\Product;
use App\Services\Erp\RfqService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Orchestrates the BOM procurement flow: parse an uploaded parts list, match each
 * line to the catalog, allow manual review, and convert the result into an RFQ.
 *
 * All persistence for a single import happens in one transaction. Matching itself
 * is delegated to BomComponentMatcher (read-only) and parsing to BomImportParser.
 */
class BomImportService
{
    public function __construct(
        private readonly BomImportParser $parser,
        private readonly BomComponentMatcher $matcher,
        private readonly RfqService $rfqs,
    ) {
    }

    /**
     * Parse + persist + match a raw BOM (CSV text or pasted text).
     */
    public function createFromContent(
        ?int $userId,
        string $name,
        string $content,
        string $sourceFormat = 'paste',
        string $currency = 'USD',
    ): BomImport {
        $parsed = $this->parser->parse($content);

        if ($parsed['lines'] === []) {
            throw new RuntimeException('No parseable BOM lines were found in the supplied content.');
        }

        $matchResults = $this->matcher->match($parsed['lines']);

        return DB::transaction(function () use ($userId, $name, $sourceFormat, $currency, $parsed, $matchResults) {
            $import = BomImport::create([
                'user_id' => $userId,
                'name' => $name,
                'source_format' => $sourceFormat,
                'status' => 'matched',
                'currency' => strtoupper($currency),
                'total_lines' => count($parsed['lines']),
                'matched_lines' => 0,
                'unmatched_lines' => 0,
                'meta' => ['delimiter' => $parsed['delimiter'], 'has_header' => $parsed['has_header']],
            ]);

            foreach ($parsed['lines'] as $line) {
                $result = $matchResults[$line['line_no']] ?? null;
                $import->lines()->create([
                    'line_no' => $line['line_no'],
                    'raw_reference' => $line['raw_reference'],
                    'mpn' => $line['mpn'],
                    'manufacturer' => $line['manufacturer'],
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'matched_product_id' => $result['matched_product_id'] ?? null,
                    'match_status' => $result['match_status'] ?? 'none',
                    'match_confidence' => $result['match_confidence'] ?? 0,
                    'candidates' => $result['candidates'] ?? [],
                ]);
            }

            return $this->recountRollup($import->fresh('lines'));
        });
    }

    /**
     * Re-run matching over an import's existing lines (e.g. after the catalog changed).
     * Manual overrides are preserved.
     */
    public function rematch(BomImport $import): BomImport
    {
        $import->load('lines');
        $payload = $import->lines
            ->map(fn (BomImportLine $l) => ['line_no' => $l->line_no, 'mpn' => $l->mpn, 'manufacturer' => $l->manufacturer])
            ->all();
        $results = $this->matcher->match($payload);

        DB::transaction(function () use ($import, $results) {
            foreach ($import->lines as $line) {
                if ($line->match_status === 'manual') {
                    continue; // never clobber a human decision
                }
                $r = $results[$line->line_no] ?? null;
                $line->update([
                    'matched_product_id' => $r['matched_product_id'] ?? null,
                    'match_status' => $r['match_status'] ?? 'none',
                    'match_confidence' => $r['match_confidence'] ?? 0,
                    'candidates' => $r['candidates'] ?? [],
                ]);
            }
        });

        return $this->recountRollup($import->fresh('lines'));
    }

    /**
     * Manually set (or clear) the product a line resolves to. Passing null unmatches.
     */
    public function setLineMatch(BomImportLine $line, ?int $productId): BomImportLine
    {
        if ($productId !== null && ! Product::whereKey($productId)->exists()) {
            throw new RuntimeException("Product {$productId} does not exist.");
        }

        $line->update([
            'matched_product_id' => $productId,
            'match_status' => $productId !== null ? 'manual' : 'none',
            'match_confidence' => $productId !== null ? 100 : 0,
            'candidates' => [],
        ]);

        $this->recountRollup($line->import()->first()->fresh('lines'));

        return $line->fresh();
    }

    /**
     * Convert an import into an RFQ. Matched lines carry their product_id/sku;
     * unmatched lines are still included by name so suppliers can quote them.
     *
     * @param array{company_name?:string, contact_name?:string, contact_email?:string,
     *   contact_phone?:string, marketplace_id?:int, notes?:string} $contact
     */
    public function convertToRfq(BomImport $import, array $contact = []): RfqRequest
    {
        $import->load('lines');

        if ($import->lines->isEmpty()) {
            throw new RuntimeException('Cannot convert an empty BOM to an RFQ.');
        }

        $items = $import->lines->map(function (BomImportLine $line) {
            $product = $line->matched_product_id ? $line->matchedProduct()->first() : null;

            return [
                'product_id' => $line->matched_product_id,
                'sku' => $product?->sku,
                'name' => $product?->name ?: ($line->description ?: ($line->mpn ?: 'Unspecified part')),
                'quantity' => (float) $line->quantity,
                'notes' => $this->lineNote($line),
            ];
        })->all();

        $rfq = $this->rfqs->create([
            'user_id' => $import->user_id,
            'company_name' => $contact['company_name'] ?? null,
            'contact_name' => $contact['contact_name'] ?? null,
            'contact_email' => $contact['contact_email'] ?? null,
            'contact_phone' => $contact['contact_phone'] ?? null,
            'marketplace_id' => $contact['marketplace_id'] ?? null,
            'currency' => $import->currency,
            'notes' => $contact['notes'] ?? "Generated from BOM import #{$import->id} ({$import->name}).",
            'items' => $items,
        ]);

        $import->update([
            'status' => 'converted',
            'rfq_request_id' => $rfq->id,
        ]);

        return $rfq;
    }

    private function lineNote(BomImportLine $line): ?string
    {
        $bits = array_filter([
            $line->mpn ? "MPN: {$line->mpn}" : null,
            $line->manufacturer ? "Mfr: {$line->manufacturer}" : null,
            $line->raw_reference ? "Ref: {$line->raw_reference}" : null,
        ]);

        return $bits === [] ? null : mb_substr(implode(' | ', $bits), 0, 255);
    }

    private function recountRollup(BomImport $import): BomImport
    {
        $matched = $import->lines->whereNotNull('matched_product_id')->count();
        $total = $import->lines->count();

        $import->update([
            'matched_lines' => $matched,
            'unmatched_lines' => $total - $matched,
        ]);

        return $import->fresh('lines');
    }
}
