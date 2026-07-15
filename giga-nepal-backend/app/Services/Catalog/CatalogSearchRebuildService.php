<?php

namespace App\Services\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CatalogSearchRebuildService
{
    private const REBUILD_CHUNK_SIZE = 500;

    public function rebuildApprovedImports(int $jobId, string $sourceCode = 'jlcpcb_parts_database'): array
    {
        $this->assertTables();

        // Resume from checkpoint if job was previously running.
        $job = DB::table('catalog_index_rebuild_jobs')->where('id', $jobId)->first();
        $runningStatuses = ['running'];
        $isResume = $job && in_array($job->status, $runningStatuses, true);
        $resumeFromId = $isResume ? ((int) ($job->last_processed_product_id ?? 0)) : 0;
        $resumeCount = $isResume ? ((int) ($job->indexed_count ?? 0)) : 0;
        $resumeFacetCount = $isResume ? ((int) ($job->facet_count ?? 0)) : 0;

        $now = now();
        DB::table('catalog_index_rebuild_jobs')->where('id', $jobId)->update([
            'status' => 'running',
            'started_at' => $isResume ? ($job->started_at ?? $now) : $now,
            'updated_at' => $now,
        ]);

        try {
            $indexed = $resumeCount;
            $facetCount = $resumeFacetCount;
            $this->eachImportChunk($sourceCode, self::REBUILD_CHUNK_SIZE, $resumeFromId, function (Collection $rows) use ($jobId, $sourceCode, &$indexed, &$facetCount): void {
                $result = $this->replaceSearchChunk($rows, $sourceCode);
                $indexed += $result['indexed_count'];
                $facetCount += $result['facet_count'];
                $lastProductId = (int) $rows->last()->product_id;

                DB::table('catalog_index_rebuild_jobs')->where('id', $jobId)->update([
                    'product_count' => $indexed,
                    'indexed_count' => $indexed,
                    'facet_count' => $facetCount,
                    'last_processed_product_id' => $lastProductId,
                    'updated_at' => now(),
                ]);
            });

            DB::table('catalog_index_rebuild_jobs')->where('id', $jobId)->update([
                'status' => 'completed',
                'product_count' => $indexed,
                'indexed_count' => $indexed,
                'facet_count' => $facetCount,
                'last_processed_product_id' => null,
                'completed_at' => now(),
                'metadata' => json_encode([
                    'source_code' => $sourceCode,
                    'mode' => 'all_imports',
                    'chunk_size' => self::REBUILD_CHUNK_SIZE,
                    'write_strategy' => 'bounded_keyset_bulk_upsert',
                    'was_resumed' => $isResume,
                    'public_visibility_note' => 'Index records include review_status and visibility_status; sitemap publication remains a separate gate.',
                ]),
                'updated_at' => now(),
            ]);

            return ['product_count' => $indexed, 'indexed_count' => $indexed, 'facet_count' => $facetCount];
        } catch (\Throwable $e) {
            $this->markFailed($jobId, $e);

            throw $e;
        }
    }

    public function markFailed(int $jobId, \Throwable $exception): void
    {
        if (! Schema::hasTable('catalog_index_rebuild_jobs')) {
            return;
        }

        DB::table('catalog_index_rebuild_jobs')
            ->where('id', $jobId)
            ->where('status', '!=', 'completed')
            ->update([
                'status' => 'failed',
                'error' => Str::limit($exception->getMessage(), 5000),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function createJob(?int $userId, string $sourceCode = 'jlcpcb_parts_database'): int
    {
        $this->assertTables();

        return DB::table('catalog_index_rebuild_jobs')->insertGetId([
            'source_code' => $sourceCode,
            'scope' => 'all_imports',
            'status' => 'queued',
            'queued_by' => $userId,
            'metadata' => json_encode(['queued_from' => 'admin.import_review']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function importRowsQuery(string $sourceCode): Builder
    {
        // Use a subquery for offer stock so we avoid GROUP BY on large text columns
        // (attributes::text, description, search_keywords).
        $offerSub = DB::table('catalog_distributor_offers')
            ->select('product_id',
                DB::raw('max(stock) as offer_stock'),
                DB::raw('count(id) as offer_count'))
            ->groupBy('product_id');

        return DB::table('catalog_product_sources as cps')
            ->join('catalog_sources as cs', 'cs.id', '=', 'cps.source_id')
            ->join('products as p', 'p.id', '=', 'cps.product_id')
            ->leftJoin('product_brands as b', 'b.id', '=', 'p.brand_id')
            ->leftJoin('product_categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoinSub($offerSub, 'offer_agg', 'offer_agg.product_id', '=', 'p.id')
            ->where('cs.code', $sourceCode)
            // Only index approved imports with validated products.
            ->where('cps.review_status', 'approved')
            ->whereIn('p.status', ['approved', 'active'])
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
                DB::raw('p.attributes::text as attributes'),
                'p.search_keywords',
                'b.name as brand_name',
                'c.name as category_name',
                DB::raw('coalesce(offer_agg.offer_stock, 0) as offer_stock'),
                DB::raw('coalesce(offer_agg.offer_count, 0) as offer_count'),
            ]);
    }

    /** @param callable(Collection<int, object>):void $callback */
    private function eachImportChunk(string $sourceCode, int $chunkSize, int $startFromId, callable $callback): void
    {
        $lastProductId = $startFromId;
        do {
            $rows = (clone $this->importRowsQuery($sourceCode))
                ->where('cps.product_id', '>', $lastProductId)
                ->orderBy('cps.product_id')
                ->limit($chunkSize)
                ->get();
            if ($rows->isEmpty()) {
                break;
            }

            $callback($rows);
            $lastProductId = (int) $rows->last()->product_id;
        } while ($rows->count() === $chunkSize);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array{indexed_count:int,facet_count:int}
     */
    private function replaceSearchChunk(Collection $rows, string $sourceCode): array
    {
        $now = now();
        $documents = [];
        $facetRows = [];
        $productIds = [];

        foreach ($rows as $row) {
            $facets = $this->facetsFor($row);
            $productId = (int) $row->product_id;
            $productIds[] = $productId;
            $documents[] = [
                'product_id' => $productId,
                'source_code' => $sourceCode,
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
                'indexed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($facets as $name => $value) {
                foreach ((array) $value as $single) {
                    if ($single === null || $single === '') {
                        continue;
                    }
                    $facetRows[] = [
                        'product_id' => $productId,
                        'source_code' => $sourceCode,
                        'facet_name' => $name,
                        'facet_value' => Str::limit((string) $single, 500, ''),
                        'indexed_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        DB::transaction(function () use ($documents, $facetRows, $productIds, $sourceCode): void {
            DB::table('product_search_documents')->upsert(
                $documents,
                ['product_id', 'source_code'],
                [
                    'title', 'sku', 'mpn', 'manufacturer', 'category', 'status', 'visibility_status',
                    'review_status', 'data_quality_score', 'searchable_text', 'facets', 'indexed_at', 'updated_at',
                ],
            );
            DB::table('product_facet_values')
                ->where('source_code', $sourceCode)
                ->whereIn('product_id', $productIds)
                ->delete();
            if ($facetRows !== []) {
                DB::table('product_facet_values')->insert($facetRows);
            }
        }, 3);

        return ['indexed_count' => count($documents), 'facet_count' => count($facetRows)];
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
