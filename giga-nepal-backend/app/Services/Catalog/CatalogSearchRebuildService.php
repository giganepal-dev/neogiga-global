<?php

namespace App\Services\Catalog;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CatalogSearchRebuildService
{
    public function rebuildApprovedImports(int $jobId, string $sourceCode = 'jlcpcb_parts_database'): array
    {
        $this->assertTables();

        $now = now();
        DB::table('catalog_index_rebuild_jobs')->where('id', $jobId)->update([
            'status' => 'running',
            'started_at' => $now,
            'updated_at' => $now,
        ]);

        try {
            $rows = $this->approvedImportRows($sourceCode);
            $indexed = 0;
            $facetCount = 0;

            foreach ($rows->chunk(200) as $chunk) {
                foreach ($chunk as $row) {
                    $facets = $this->facetsFor($row);
                    $this->upsertSearchDocument($row, $sourceCode, $facets);
                    $facetCount += $this->replaceFacetValues((int) $row->product_id, $sourceCode, $facets);
                    $indexed++;
                }
            }

            DB::table('catalog_index_rebuild_jobs')->where('id', $jobId)->update([
                'status' => 'completed',
                'product_count' => $rows->count(),
                'indexed_count' => $indexed,
                'facet_count' => $facetCount,
                'completed_at' => now(),
                'metadata' => json_encode([
                    'source_code' => $sourceCode,
                    'mode' => 'approved_imports',
                    'public_visibility_note' => 'Index records include visibility_status; public search integration is a later gate.',
                ]),
                'updated_at' => now(),
            ]);

            return ['product_count' => $rows->count(), 'indexed_count' => $indexed, 'facet_count' => $facetCount];
        } catch (\Throwable $e) {
            DB::table('catalog_index_rebuild_jobs')->where('id', $jobId)->update([
                'status' => 'failed',
                'error' => Str::limit($e->getMessage(), 5000),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    public function createJob(?int $userId, string $sourceCode = 'jlcpcb_parts_database'): int
    {
        $this->assertTables();

        return DB::table('catalog_index_rebuild_jobs')->insertGetId([
            'source_code' => $sourceCode,
            'scope' => 'approved_imports',
            'status' => 'queued',
            'queued_by' => $userId,
            'metadata' => json_encode(['queued_from' => 'admin.import_review']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function approvedImportRows(string $sourceCode): Collection
    {
        return DB::table('catalog_product_sources as cps')
            ->join('catalog_sources as cs', 'cs.id', '=', 'cps.source_id')
            ->join('products as p', 'p.id', '=', 'cps.product_id')
            ->leftJoin('product_brands as b', 'b.id', '=', 'p.brand_id')
            ->leftJoin('product_categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('catalog_distributor_offers as offer', 'offer.product_id', '=', 'p.id')
            ->where('cs.code', $sourceCode)
            ->where('cps.review_status', 'approved')
            ->select([
                'cps.product_id',
                'cps.review_status',
                'cps.data_quality_score',
                'cps.source_part_id',
                'p.name',
                'p.sku',
                'p.mpn',
                'p.manufacturer_name',
                'p.status',
                'p.visibility_status',
                'p.short_description',
                'p.description',
                'p.attributes',
                'p.search_keywords',
                'b.name as brand_name',
                'c.name as category_name',
                DB::raw('max(offer.stock) as offer_stock'),
                DB::raw('count(offer.id) as offer_count'),
            ])
            ->groupBy([
                'cps.product_id',
                'cps.review_status',
                'cps.data_quality_score',
                'cps.source_part_id',
                'p.name',
                'p.sku',
                'p.mpn',
                'p.manufacturer_name',
                'p.status',
                'p.visibility_status',
                'p.short_description',
                'p.description',
                'p.attributes',
                'p.search_keywords',
                'b.name',
                'c.name',
            ])
            ->orderBy('cps.product_id')
            ->get();
    }

    private function upsertSearchDocument(object $row, string $sourceCode, array $facets): void
    {
        DB::table('product_search_documents')->updateOrInsert(
            ['product_id' => $row->product_id, 'source_code' => $sourceCode],
            [
                'title' => $row->name,
                'sku' => $row->sku,
                'mpn' => $row->mpn,
                'manufacturer' => $row->manufacturer_name ?: $row->brand_name,
                'category' => $row->category_name,
                'status' => $row->status,
                'visibility_status' => $row->visibility_status,
                'review_status' => $row->review_status,
                'data_quality_score' => $row->data_quality_score ?? 0,
                'searchable_text' => $this->searchableText($row, $facets),
                'facets' => json_encode($facets),
                'indexed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function replaceFacetValues(int $productId, string $sourceCode, array $facets): int
    {
        DB::table('product_facet_values')->where('product_id', $productId)->where('source_code', $sourceCode)->delete();

        $rows = [];
        foreach ($facets as $name => $value) {
            foreach ((array) $value as $single) {
                if ($single === null || $single === '') {
                    continue;
                }
                $rows[] = [
                    'product_id' => $productId,
                    'source_code' => $sourceCode,
                    'facet_name' => $name,
                    'facet_value' => Str::limit((string) $single, 500, ''),
                    'indexed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($rows) {
            DB::table('product_facet_values')->insert($rows);
        }

        return count($rows);
    }

    private function facetsFor(object $row): array
    {
        $attributes = json_decode((string) ($row->attributes ?? '{}'), true) ?: [];
        $package = $attributes['package']['normalized_value'] ?? $attributes['Package']['normalized_value'] ?? null;

        return array_filter([
            'brand' => $row->brand_name,
            'manufacturer' => $row->manufacturer_name ?: $row->brand_name,
            'category' => $row->category_name,
            'status' => $row->status,
            'visibility' => $row->visibility_status,
            'quality_band' => ((float) $row->data_quality_score >= 0.85) ? 'high' : 'needs_review',
            'stock' => ((int) ($row->offer_stock ?? 0) > 0) ? 'in_stock' : 'unknown_or_out',
            'package' => is_scalar($package) ? $package : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function searchableText(object $row, array $facets): string
    {
        $parts = [
            $row->name,
            $row->sku,
            $row->mpn,
            $row->manufacturer_name,
            $row->brand_name,
            $row->category_name,
            $row->short_description,
            $row->description,
            $row->search_keywords,
            implode(' ', array_map(fn ($v) => is_array($v) ? implode(' ', $v) : (string) $v, $facets)),
        ];

        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts))));
    }

    private function assertTables(): void
    {
        foreach (['catalog_index_rebuild_jobs', 'product_search_documents', 'product_facet_values', 'catalog_product_sources'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Required table {$table} is missing.");
            }
        }
    }
}
