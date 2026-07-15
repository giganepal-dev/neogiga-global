<?php

namespace App\Services\Pcb;

use App\Models\Marketplace\Product;
use App\Models\Pcb\PcbComponentMatch;
use App\Models\Pcb\PcbProject;

class PcbComponentSourcingService
{
    /**
     * Match BOM/CPL components to NeoGiga product catalog.
     * Populates pcb_component_matches with confidence scoring.
     */
    public function matchFromCplImport(PcbProject $project, $cplImportId, int $userId): array
    {
        $cplImport = $project->cplImports()->findOrFail($cplImportId);
        $lines = $cplImport->lines()->whereNull('matched_product_id')->get();

        $results = ['matched' => 0, 'no_match' => 0];

        foreach ($lines as $line) {
            $mpn = trim((string) $line->comment);
            if (empty($mpn) || strlen($mpn) < 2) {
                $results['no_match']++;
                continue;
            }

            // Try exact MPN match
            $product = Product::where('mpn', $mpn)
                ->orWhere('normalized_mpn', $this->normalizeMpn($mpn))
                ->first();

            $confidence = 'no_match';
            $matchReason = null;

            if ($product) {
                $confidence = 'exact';
                $matchReason = 'Exact MPN match in NeoGiga catalog';
            }

            // Try keyword search if no exact match
            if (!$product && strlen($mpn) >= 4) {
                $product = Product::where('name', 'like', "%{$mpn}%")
                    ->orWhere('description', 'like', "%{$mpn}%")
                    ->first();

                if ($product) {
                    $confidence = 'medium';
                    $matchReason = 'Keyword match in product name/description';
                }
            }

            // Try footprint match for medium confidence
            if (!$product && $line->footprint) {
                $footprint = trim((string) $line->footprint);
                $product = Product::where('name', 'like', "%{$footprint}%")->first();
                if ($product) {
                    $confidence = 'low';
                    $matchReason = 'Footprint keyword match';
                }
            }

            $match = PcbComponentMatch::updateOrCreate(
                ['project_id' => $project->id, 'requested_mpn' => $mpn],
                [
                    'requested_description' => $line->comment,
                    'requested_package' => $line->footprint,
                    'matched_product_id' => $product?->id,
                    'matched_mpn' => $product?->mpn,
                    'matched_manufacturer' => $product?->manufacturer_name,
                    'match_confidence' => $confidence,
                    'match_reason' => $matchReason,
                    'alternative_allowed' => $confidence !== 'exact',
                ]
            );

            if ($product) {
                $results['matched']++;
                $line->update([
                    'matched_product_id' => $product->id,
                    'bom_matched' => true,
                    'matched_bom_line_id' => null,
                ]);
            } else {
                $results['no_match']++;
            }
        }

        return $results;
    }

    /**
     * Quick search — scan project's CPL lines against catalog without persisting.
     */
    public function previewMatches(PcbProject $project): array
    {
        $results = [];
        $cplImport = $project->cplImports()->where('status', 'completed')->latest()->first();
        if (!$cplImport) return $results;

        foreach ($cplImport->lines()->limit(50)->get() as $line) {
            $mpn = trim((string) $line->comment);
            if (empty($mpn) || strlen($mpn) < 2) continue;

            $product = Product::where('mpn', $mpn)
                ->orWhere('normalized_mpn', $this->normalizeMpn($mpn))
                ->first();

            $results[] = [
                'designator' => $line->reference_designator,
                'requested_mpn' => $mpn,
                'footprint' => $line->footprint,
                'matched' => $product !== null,
                'product_id' => $product?->id,
                'product_name' => $product?->name,
                'manufacturer' => $product?->manufacturer_name,
                'confidence' => $product ? 'exact' : 'no_match',
            ];
        }

        return $results;
    }

    private function normalizeMpn(string $mpn): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $mpn));
    }
}
