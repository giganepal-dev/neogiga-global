<?php

namespace App\Services\CatalogImport\Elecforest;

use App\Jobs\CatalogImport\DownloadElecforestProductImageJob;
use App\Jobs\CatalogImport\ImportElecforestProductJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use SplFileObject;

class ElecforestImporter
{
    private ?int $cachedSourceId = null;

    /** @var array<string, array<string, true>> */
    private array $columnCache = [];

    public function __construct(
        private readonly ElecforestRecordParser $parser,
        private readonly ElecforestProductMapper $mapper,
        private readonly ElecforestIdentityResolver $identity,
        private readonly ElecforestImportValidator $validator,
        private readonly ElecforestSeoGenerator $seo,
    ) {}

    /** @param array<string, mixed> $options @return array<string, mixed> */
    public function importFile(string $file, array $options): array
    {
        $file = $this->assertFile($file);
        $options['_duplicate_skus'] = array_map('strval', array_keys(array_filter($this->supplierSkuCounts($file), static fn (int $count): bool => $count > 1)));
        $startLine = max(1, (int) ($options['start_line'] ?? 1));
        $limit = max(0, (int) ($options['limit'] ?? 0));
        $dryRun = (bool) ($options['dry_run'] ?? false);

        if ($dryRun) {
            return $this->dryRun($file, $options, $startLine, $limit);
        }

        $runId = (string) ($options['run_id'] ?? $this->createRun($file, $options, 'sync'));
        $results = $this->emptyCounters();
        $processed = 0;
        $stream = new SplFileObject($file, 'rb');
        foreach ($stream as $index => $line) {
            $lineNumber = $index + 1;
            if ($lineNumber < $startLine || trim((string) $line) === '') {
                continue;
            }
            if ($limit > 0 && $processed >= $limit) {
                break;
            }
            $processed++;
            $outcome = $this->importLine((string) $line, $lineNumber, $runId, $options);
            $results[$outcome['status']] = ($results[$outcome['status']] ?? 0) + 1;
        }

        $this->finalizeRun($runId);

        return ['run_id' => $runId, 'mode' => 'sync', 'status' => 'completed', 'counters' => $results] + $this->runSummary($runId);
    }

    /** @param array<string, mixed> $options @return array<string, mixed> */
    public function queueFile(string $file, array $options): array
    {
        $file = $this->assertFile($file);
        $options['_duplicate_skus'] = array_map('strval', array_keys(array_filter($this->supplierSkuCounts($file), static fn (int $count): bool => $count > 1)));
        $startLine = max(1, (int) ($options['start_line'] ?? 1));
        $limit = max(0, (int) ($options['limit'] ?? 0));
        $runId = (string) ($options['run_id'] ?? $this->createRun($file, $options, 'queue'));
        $queued = 0;
        $stream = new SplFileObject($file, 'rb');

        foreach ($stream as $index => $line) {
            $lineNumber = $index + 1;
            if ($lineNumber < $startLine || trim((string) $line) === '') {
                continue;
            }
            if ($limit > 0 && $queued >= $limit) {
                break;
            }
            try {
                $record = $this->parser->parse((string) $line, $lineNumber);
                $this->upsertItem($runId, $record, 'queued', ['line_number' => $lineNumber]);
                ImportElecforestProductJob::dispatch($runId, $file, $lineNumber, $options)
                    ->onQueue((string) config('elecforest_import.queue'));
                $queued++;
            } catch (\Throwable $exception) {
                $this->recordFailure($runId, $lineNumber, null, $exception, ['line' => Str::limit((string) $line, 5000, '')]);
            }
        }

        DB::table('catalog_import_runs')->where('id', $runId)->update([
            'status' => $queued > 0 ? 'queued' : 'completed',
            'completed_at' => $queued > 0 ? null : now(),
            'updated_at' => now(),
        ]);

        return ['run_id' => $runId, 'mode' => 'queue', 'status' => $queued > 0 ? 'queued' : 'completed', 'queued' => $queued];
    }

    /** @param array<string, mixed> $options @return array<string, mixed> */
    public function importLineFromFile(string $file, int $lineNumber, string $runId, array $options): array
    {
        $stream = new SplFileObject($this->assertFile($file), 'rb');
        $stream->seek($lineNumber - 1);
        $line = $stream->current();
        if (! is_string($line) || trim($line) === '') {
            throw new \RuntimeException("Line {$lineNumber} is unavailable in the source file.");
        }

        return $this->importLine($line, $lineNumber, $runId, $options);
    }

    /** @param array<string, mixed> $options @return array<string, mixed> */
    public function importLine(string $line, int $lineNumber, string $runId, array $options): array
    {
        try {
            $record = $this->parser->parse($line, $lineNumber);
            $record['supplier_sku_ambiguous'] = $record['supplier_sku'] !== null
                && in_array($record['supplier_sku'], $options['_duplicate_skus'] ?? [], true);
            $errors = $this->validator->validate($record);
            if ($errors !== []) {
                throw new \DomainException(implode(' ', $errors));
            }

            $result = DB::transaction(fn (): array => $this->persist($record, $runId, $options), 3);
            $this->incrementRun($runId, $lineNumber, $result['status']);
            DB::table('catalog_import_failures')->where('catalog_import_run_id', $runId)->where('line_number', $lineNumber)
                ->where('retry_status', '!=', 'resolved')->update(['retry_status' => 'resolved', 'resolved_at' => now(), 'updated_at' => now()]);

            if (! empty($options['download_images']) && empty($options['skip_images'])) {
                foreach ($result['asset_ids'] as $assetId) {
                    if (! empty($options['sync'])) {
                        app(ElecforestMediaImporter::class)->downloadAsset((int) $assetId);
                    } else {
                        DownloadElecforestProductImageJob::dispatch((int) $assetId)
                            ->onQueue((string) config('elecforest_import.image_queue'));
                    }
                }
            }

            return $result;
        } catch (\Throwable $exception) {
            $record = $record ?? null;
            $this->recordFailure($runId, $lineNumber, $record['idempotency_key'] ?? null, $exception, $record['raw'] ?? ['line' => Str::limit($line, 5000, '')]);
            $this->incrementRun($runId, $lineNumber, 'rejected');
            if (isset($record)) {
                $this->upsertItem($runId, $record, 'rejected', ['line_number' => $lineNumber, 'error' => $exception->getMessage()]);
            }

            return ['status' => 'rejected', 'error' => $exception->getMessage(), 'asset_ids' => []];
        } finally {
            $this->finalizeRunIfQueueDrained($runId);
        }
    }

    /** @return array<string, mixed> */
    public function audit(string $file): array
    {
        $file = $this->assertFile($file);
        $stats = [
            'file' => $file, 'bytes' => filesize($file), 'sha256' => hash_file('sha256', $file),
            'lines' => 0, 'valid' => 0, 'malformed' => 0, 'utf8_invalid' => 0,
            'image_coverage' => 0, 'description_coverage' => 0, 'category_coverage' => 0,
            'raw_image_url_count' => 0, 'product_image_url_count' => 0, 'ignored_non_product_image_url_count' => 0,
            'subcategory_coverage' => 0, 'price_coverage' => 0, 'currency_coverage' => 0,
            'sku_coverage' => 0, 'raw_sku_coverage' => 0, 'raw_description_coverage' => 0,
            'url_coverage' => 0, 'normalized_url_coverage' => 0, 'derived_url_count' => 0, 'collection_pages' => 0,
        ];
        $fields = $urls = $skus = $rawSkus = $categories = $currencies = [];
        $stream = new SplFileObject($file, 'rb');
        foreach ($stream as $index => $line) {
            if (! is_string($line) || $line === '') {
                continue;
            }
            $stats['lines']++;
            if (! mb_check_encoding($line, 'UTF-8')) {
                $stats['utf8_invalid']++;
            }
            try {
                $record = $this->parser->parse($line, $index + 1);
                $raw = $record['raw'];
                $stats['valid']++;
            } catch (\Throwable) {
                $stats['malformed']++;
                continue;
            }
            foreach (array_keys($raw) as $field) {
                $fields[$field] = ($fields[$field] ?? 0) + 1;
            }
            if (trim((string) ($raw['description'] ?? '')) !== '') {
                $stats['raw_description_coverage']++;
            }
            $rawImageValues = is_array($raw['image_urls'] ?? null)
                ? $raw['image_urls']
                : (preg_split('/\s*[|\n]\s*/', (string) ($raw['image_urls'] ?? '')) ?: []);
            $rawImageCount = count(array_filter($rawImageValues, static fn (mixed $value): bool => trim((string) $value) !== ''));
            $stats['raw_image_url_count'] += $rawImageCount;
            $stats['product_image_url_count'] += count($record['image_urls']);
            $stats['ignored_non_product_image_url_count'] += max(0, $rawImageCount - count($record['image_urls']));
            $rawSku = strtoupper(trim((string) ($raw['sku'] ?? '')));
            if ($rawSku !== '') {
                $stats['raw_sku_coverage']++;
                $rawSkus[$rawSku] = ($rawSkus[$rawSku] ?? 0) + 1;
            }
            foreach (['image_urls' => 'image_coverage', 'description' => 'description_coverage', 'main_category' => 'category_coverage', 'subcategory' => 'subcategory_coverage', 'price' => 'price_coverage', 'currency' => 'currency_coverage', 'supplier_sku' => 'sku_coverage', 'source_url' => 'normalized_url_coverage'] as $field => $metric) {
                if (! empty($record[$field])) {
                    $stats[$metric]++;
                }
            }
            if (! $record['source_url_was_derived']) {
                $stats['url_coverage']++;
            }
            if ($record['source_url_was_derived']) {
                $stats['derived_url_count']++;
            }
            if ($record['is_collection_page']) {
                $stats['collection_pages']++;
            }
            if ($record['source_url'] !== '') {
                $urls[$record['source_url']] = ($urls[$record['source_url']] ?? 0) + 1;
            }
            if ($record['supplier_sku'] !== null) {
                $skus[$record['supplier_sku']] = ($skus[$record['supplier_sku']] ?? 0) + 1;
            }
            $categories[$record['main_category']] = ($categories[$record['main_category']] ?? 0) + 1;
            if ($record['currency'] !== '') {
                $currencies[$record['currency']] = ($currencies[$record['currency']] ?? 0) + 1;
            }
        }
        arsort($categories);
        arsort($currencies);

        return $stats + [
            'duplicate_source_urls' => $this->duplicates($urls),
            'duplicate_supplier_skus' => $this->duplicates($skus),
            'raw_duplicate_supplier_skus' => $this->duplicates($rawSkus),
            'fields' => $fields, 'categories' => $categories, 'currencies' => $currencies,
        ];
    }

    /** @return array<string, mixed> */
    public function runSummary(string $runId): array
    {
        $run = DB::table('catalog_import_runs')->where('id', $runId)->first();

        return ['run' => $run ? (array) $run : null];
    }

    /** @return array<string, mixed> */
    public function validateImported(?string $runId = null): array
    {
        $sourceId = $this->sourceId();
        $query = DB::table('supplier_products')->where('catalog_source_id', $sourceId);
        if ($runId !== null && $runId !== '') {
            $run = DB::table('catalog_import_runs')->where('id', $runId)->where('catalog_source_id', $sourceId)->exists();
            if (! $run) {
                throw new \InvalidArgumentException("ElecForest import run {$runId} was not found.");
            }
            $supplierIds = DB::table('catalog_import_items')->where('catalog_import_run_id', $runId)
                ->whereNotNull('supplier_product_id')->pluck('supplier_product_id')->unique();
            $query->whereIn('id', $supplierIds);
        }
        $rows = $query->get();
        $productIds = $rows->pluck('product_id')->filter()->unique()->values();
        $checks = [
            'source_records' => $rows->count(),
            'linked_products' => $productIds->count(),
            'unlinked_source_records' => $rows->whereNull('product_id')->count(),
            'draft_products' => DB::table('products')->whereIn('id', $productIds)->where('status', 'draft')->count(),
            'public_products' => DB::table('products')->whereIn('id', $productIds)->whereIn('status', ['active', 'approved', 'published'])->count(),
            'seo_records' => DB::table('product_seo_meta')->whereIn('product_id', $productIds)->count(),
            'content_versions' => DB::table('product_content_versions')->whereIn('product_id', $productIds)->count(),
            'source_offers' => DB::table('supplier_product_offers')->whereIn('supplier_product_id', $rows->pluck('id'))->count(),
            'active_images' => DB::table('product_images')->whereIn('product_id', $productIds)->where('is_active', true)->count(),
            'pending_media_assets' => DB::table('supplier_product_assets')->whereIn('supplier_product_id', $rows->pluck('id'))->where('rights_status', 'pending_review')->count(),
            'warehouse_stock_rows' => DB::table('inventory_stocks')->whereIn('product_id', $productIds)->count(),
            'marketplace_price_rows' => DB::table('marketplace_product_prices')->whereIn('product_id', $productIds)->count(),
            'vendor_price_rows' => DB::table('vendor_product_prices')->whereIn('product_id', $productIds)->count(),
            'country_price_rows' => DB::table('product_country_prices')->whereIn('product_id', $productIds)->count(),
        ];
        $checks['isolation_passed'] = $checks['warehouse_stock_rows'] === 0
            && $checks['marketplace_price_rows'] === 0
            && $checks['vendor_price_rows'] === 0
            && $checks['country_price_rows'] === 0;

        return $checks;
    }

    /** @return array{published:int,blocked:int} */
    public function publishQualified(bool $force = false, ?string $runId = null): array
    {
        $sourceId = $this->sourceId();
        $query = DB::table('supplier_products')->where('catalog_source_id', $sourceId)->whereNotNull('product_id');
        if ($runId !== null && $runId !== '') {
            $run = DB::table('catalog_import_runs')->where('id', $runId)->where('catalog_source_id', $sourceId)->exists();
            if (! $run) {
                throw new \InvalidArgumentException("ElecForest import run {$runId} was not found.");
            }
            $supplierIds = DB::table('catalog_import_items')->where('catalog_import_run_id', $runId)
                ->whereNotNull('supplier_product_id')->pluck('supplier_product_id')->unique();
            $query->whereIn('id', $supplierIds);
        }
        $productIds = $query->pluck('product_id')->unique();
        $published = $blocked = 0;
        foreach (DB::table('products')->whereIn('id', $productIds)->orderBy('id')->get() as $product) {
            $failures = $this->validator->publicationFailures($product);
            if ($failures !== [] && ! $force) {
                $blocked++;
                continue;
            }
            DB::transaction(function () use ($product): void {
                DB::table('products')->where('id', $product->id)->update(['status' => 'published', 'visibility_status' => 'public', 'updated_at' => now()]);
                DB::table('product_seo_meta')->where('product_id', $product->id)->update(['robots' => 'index,follow', 'updated_at' => now()]);
            });
            $published++;
        }

        return compact('published', 'blocked');
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    public function resume(string $runId, array $overrides = []): array
    {
        $run = DB::table('catalog_import_runs')->where('id', $runId)->first();
        if (! $run) {
            throw new \InvalidArgumentException("ElecForest import run {$runId} was not found.");
        }
        $options = array_merge(json_decode((string) $run->command_options, true) ?: [], $overrides, [
            'run_id' => $runId, 'start_line' => ((int) ($run->last_line ?? 0)) + 1, 'dry_run' => false,
        ]);
        DB::table('catalog_import_runs')->where('id', $runId)->update(['mode' => 'resume', 'status' => 'running', 'completed_at' => null, 'updated_at' => now()]);

        return ! empty($options['queue'])
            ? $this->queueFile((string) $run->source_file, $options)
            : $this->importFile((string) $run->source_file, $options);
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    public function retryFailures(string $runId, array $overrides = []): array
    {
        $run = DB::table('catalog_import_runs')->where('id', $runId)->first();
        if (! $run) {
            throw new \InvalidArgumentException("ElecForest import run {$runId} was not found.");
        }
        $options = array_merge(json_decode((string) $run->command_options, true) ?: [], $overrides, ['run_id' => $runId, 'dry_run' => false]);
        $file = $this->assertFile((string) $run->source_file);
        $options['_duplicate_skus'] = array_map('strval', array_keys(array_filter($this->supplierSkuCounts($file), static fn (int $count): bool => $count > 1)));
        $failures = DB::table('catalog_import_failures')->where('catalog_import_run_id', $runId)
            ->whereIn('retry_status', ['pending', 'retrying', 'failed'])->whereNotNull('line_number')->orderBy('line_number')->get()->unique('line_number');
        DB::table('catalog_import_runs')->where('id', $runId)->update(['mode' => 'retry', 'status' => 'running', 'completed_at' => null, 'updated_at' => now()]);
        $results = $this->emptyCounters();
        foreach ($failures as $failure) {
            DB::table('catalog_import_failures')->where('id', $failure->id)->update([
                'attempts' => DB::raw('attempts + 1'), 'retry_status' => 'retrying', 'last_attempted_at' => now(), 'updated_at' => now(),
            ]);
            $result = $this->importLineFromFile($file, (int) $failure->line_number, $runId, $options);
            $results[$result['status']] = ($results[$result['status']] ?? 0) + 1;
        }
        $this->finalizeRun($runId);

        return ['run_id' => $runId, 'retried' => $failures->count(), 'counters' => $results];
    }

    /** @return array{generated:int,skipped:int} */
    public function generateSeoForImported(int $limit = 0): array
    {
        $query = DB::table('supplier_products as sp')->join('products as p', 'p.id', '=', 'sp.product_id')
            ->leftJoin('product_categories as c', 'c.id', '=', 'p.category_id')
            ->where('sp.catalog_source_id', $this->sourceId())
            ->select(['p.id', 'p.name', 'p.slug', 'p.sku', 'p.metadata', 'c.name as category_name', 'sp.supplier_sku', 'sp.raw_payload_json'])->orderBy('p.id');
        if ($limit > 0) {
            $query->limit($limit);
        }
        $generated = $skipped = 0;
        foreach ($query->get() as $product) {
            $metadata = json_decode((string) $product->metadata, true) ?: [];
            $provenance = $metadata['content_provenance'] ?? [];
            $content = [
                'source_notes' => $provenance['source_notes'] ?? 'Generated from the imported ElecForest record and NeoGiga catalog fields.',
                'confidence_level' => $provenance['confidence_level'] ?? 'source_unreviewed',
                'advisory_disclaimer' => $provenance['advisory_disclaimer'] ?? config('elecforest_import.advisory_disclaimer'),
            ];
            $raw = json_decode((string) $product->raw_payload_json, true) ?: [];
            $record = [
                'supplier_sku' => $product->supplier_sku,
                'main_category' => $raw['main_category'] ?? null,
                'subcategory' => $raw['subcategory'] ?? null,
                'generated_tags' => preg_split('/\s*[|,;]\s*/', (string) ($raw['generated_tags'] ?? '')) ?: [],
                'site_tags' => preg_split('/\s*[|,;]\s*/', (string) ($raw['site_tags'] ?? '')) ?: [],
            ];
            $mapped = ['content' => ['name' => $product->name] + $content, 'category' => ['category_name' => $product->category_name ?: 'Electronic Components'], 'sku' => $product->sku, 'slug' => $product->slug, 'record' => $record, 'specifications' => []];
            $this->persistSeo((int) $product->id, $mapped);
            $generated++;
        }

        return compact('generated', 'skipped');
    }

    /** @return array{processed:int,downloaded:int,failed:int} */
    public function downloadImages(int $limit = 0, bool $sync = false, bool $retryFailed = false): array
    {
        $query = DB::table('supplier_product_assets as a')->join('supplier_products as sp', 'sp.id', '=', 'a.supplier_product_id')
            ->where('sp.catalog_source_id', $this->sourceId())->where('a.asset_type', 'image');
        $statuses = $retryFailed ? ['failed', 'not_requested', 'downloading'] : ['not_requested'];
        $query->whereIn('a.download_status', $statuses)->orderBy('a.id');
        if ($limit > 0) {
            $query->limit($limit);
        }
        $processed = $downloaded = $failed = 0;
        foreach ($query->pluck('a.id') as $assetId) {
            $processed++;
            if (! $sync) {
                DownloadElecforestProductImageJob::dispatch((int) $assetId)->onQueue((string) config('elecforest_import.image_queue'));
                continue;
            }
            try {
                app(ElecforestMediaImporter::class)->downloadAsset((int) $assetId);
                $downloaded++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return compact('processed', 'downloaded', 'failed');
    }

    /** @return array<string, mixed> */
    public function mapCategory(string $main, string $subcategory, string $neoCategory): array
    {
        $category = DB::table('product_categories')->where('slug', $neoCategory)->orWhereRaw('lower(name) = ?', [mb_strtolower($neoCategory)])->first();
        if (! $category) {
            throw new \InvalidArgumentException("NeoGiga category {$neoCategory} was not found.");
        }
        $key = hash('sha256', mb_strtolower(trim($main).'|'.trim($subcategory)));
        DB::table('supplier_category_mappings')->updateOrInsert(
            ['catalog_source_id' => $this->sourceId(), 'source_category_key' => $key],
            [
                'source_category_name' => $subcategory !== '' ? $subcategory : $main,
                'source_category_path' => trim($main.' / '.$subcategory, ' /'), 'category_id' => $category->id,
                'confidence' => 1, 'mapping_status' => 'approved_manual', 'reviewed_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]
        );

        return ['source_category' => trim($main.' / '.$subcategory, ' /'), 'neo_category_id' => (int) $category->id, 'neo_category' => $category->name, 'status' => 'approved_manual'];
    }

    public function sourceId(): int
    {
        if ($this->cachedSourceId !== null) {
            return $this->cachedSourceId;
        }
        $values = [
            'name' => config('elecforest_import.source_name'),
            'source_url' => config('elecforest_import.source_url'),
            'license_notes' => config('elecforest_import.license_note'),
            'active' => true,
            'updated_at' => now(),
        ];
        foreach ([
            'source_type' => 'supplier', 'base_url' => config('elecforest_import.source_url'),
            'country_code' => 'CN', 'import_enabled' => true, 'media_download_enabled' => false,
            'description_reuse_status' => 'editorial_rewrite_required', 'status' => 'active_internal_review',
        ] as $column => $value) {
            if (Schema::hasColumn('catalog_sources', $column)) {
                $values[$column] = $value;
            }
        }
        $existing = DB::table('catalog_sources')->where('code', config('elecforest_import.source_code'))->first();
        if ($existing) {
            DB::table('catalog_sources')->where('id', $existing->id)->update($values);

            return $this->cachedSourceId = (int) $existing->id;
        }

        return $this->cachedSourceId = (int) DB::table('catalog_sources')->insertGetId(['code' => config('elecforest_import.source_code'), 'created_at' => now()] + $values);
    }

    /** @param array<string, mixed> $record @param array<string, mixed> $options @return array<string, mixed> */
    private function persist(array $record, string $runId, array $options): array
    {
        $sourceId = $this->sourceId();
        $mapped = $this->mapper->map($record, $sourceId, true);
        $identity = $record['supplier_sku_ambiguous']
            ? $this->resolveWithoutSupplierSku($record, $mapped['manufacturer'], $sourceId)
            : $this->identity->resolve($record, $mapped['manufacturer'], $sourceId);

        if ($identity['ambiguous']) {
            throw new \DomainException('Ambiguous identity match requires administrator review.');
        }

        $existingSource = DB::table('supplier_products')->where('catalog_source_id', $sourceId)
            ->where('source_product_id', $record['source_product_id'])->first();
        $isUnchanged = $existingSource && hash_equals((string) $existingSource->content_hash, (string) $record['content_hash']);
        $productId = $identity['product_id'];
        $created = false;

        if (! $productId) {
            $productId = $this->createProduct($mapped, $sourceId);
            $created = true;
        } else {
            $canonical = DB::table('products')->find($productId);
            $mapped['sku'] = (string) $canonical->sku;
            $mapped['slug'] = (string) $canonical->slug;
            if (! $this->isElecforestManaged((int) $productId)) {
                $mapped['content']['name'] = (string) $canonical->name;
                $mapped['category']['category_id'] = $canonical->category_id;
            }
            if (($options['only_new'] ?? false) === true) {
                $isUnchanged = true;
            } elseif ($this->isElecforestManaged((int) $productId)
                && (($options['update_existing'] ?? true) || ($options['rewrite_content'] ?? false))
                && (! $isUnchanged || ($options['rewrite_content'] ?? false))) {
                $this->updateManagedProduct((int) $productId, $mapped);
            }
        }
        $managed = $created || $this->isElecforestManaged((int) $productId);

        $supplierProductId = $this->persistSourceRecord((int) $productId, $sourceId, $record, $mapped);
        $assetIds = $this->persistAssets($supplierProductId, (int) $productId, $record, $mapped['content']['name']);
        if (empty($options['skip_prices'])) {
            $this->persistOffer($supplierProductId, $record);
        }
        $this->persistIdentifiers((int) $productId, $sourceId, $record, $mapped['sku']);
        $this->persistSpecifications((int) $productId, $sourceId, $record, $mapped, $managed);
        $this->persistApplications((int) $productId, $sourceId, $mapped['applications']);
        $this->persistCategoryAssignment((int) $productId, $sourceId, $mapped['category']);
        $this->persistSourceLink((int) $productId, $sourceId, $record);
        $this->persistContentVersion((int) $productId, $sourceId, $mapped['content']);
        if ($managed && ($options['generate_seo'] ?? true) !== false) {
            $this->persistSeo((int) $productId, $mapped);
        }
        $this->persistReviewTasks((int) $productId, $supplierProductId, $sourceId, $mapped, $assetIds);

        $status = $created ? 'created' : ($isUnchanged ? 'unchanged' : 'updated');
        $this->persistChangeEvent($sourceId, $supplierProductId, $runId, $status, $existingSource, $record);
        $this->upsertItem($runId, $record, $status, [
            'line_number' => $record['line_number'], 'matched_by' => $identity['matched_by'],
            'product_id' => $productId, 'supplier_product_id' => $supplierProductId,
        ], $supplierProductId, (int) $productId);

        return compact('status', 'productId', 'supplierProductId', 'assetIds') + ['product_id' => $productId, 'supplier_product_id' => $supplierProductId, 'asset_ids' => $assetIds];
    }

    /** @param array<string, mixed> $mapped */
    private function createProduct(array $mapped, int $sourceId): int
    {
        $record = $mapped['record'];
        $content = $mapped['content'];
        $metadata = [
            'elecforest_managed' => true, 'source_code' => 'elecforest', 'source_product_id' => $record['source_product_id'],
            'source_url_was_derived' => (bool) ($record['source_url_was_derived'] ?? false),
            'subtitle' => $content['subtitle'], 'key_features' => $content['key_features'], 'applications' => $content['applications'],
            'compatibility' => $content['compatibility'], 'package_contents' => $content['package_contents'],
            'usage_notes' => $content['usage_notes'], 'safety_notes' => $content['safety_notes'], 'warranty' => $content['warranty'],
            'content_provenance' => [
                'method' => 'deterministic_assisted', 'source_notes' => $content['source_notes'],
                'confidence_level' => $content['confidence_level'], 'last_updated' => $content['last_updated'],
                'advisory_disclaimer' => $content['advisory_disclaimer'],
            ],
        ];
        $data = [
            'name' => $content['name'], 'slug' => $mapped['slug'], 'sku' => $mapped['sku'],
            'type' => 'simple', 'status' => config('elecforest_import.draft_status'),
            'brand_id' => $mapped['brand']['id'], 'manufacturer_id' => $mapped['manufacturer']['id'],
            'category_id' => $mapped['category']['category_id'], 'manufacturer_name' => $mapped['manufacturer']['name'],
            'mpn' => $mapped['manufacturer']['mpn'], 'normalized_mpn' => $this->identity->normalize($mapped['manufacturer']['mpn']),
            'short_description' => $content['short_description'], 'description' => $content['description'],
            'base_price' => 0, 'cost_price' => null, 'sale_price' => null, 'track_inventory' => false, 'stock_quantity' => 0,
            'marketplace_visibility' => json_encode([]), 'attributes' => json_encode(['source_specifications' => $mapped['specifications']]),
            'metadata' => json_encode($metadata), 'seo_meta' => json_encode([]),
            'source_name' => 'ElecForest', 'source_url' => $record['source_url'], 'source_file' => config('elecforest_import.source_file_label'),
            'source_page_url' => $record['source_url'], 'downloaded_at' => $record['scraped_at'], 'imported_at' => now(),
            'data_year' => config('elecforest_import.data_year'), 'license_note' => config('elecforest_import.license_note'),
            'confidence_level' => $content['confidence_level'], 'original_raw_value' => json_encode($record['raw'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'normalized_value' => json_encode(['name' => $content['name'], 'category_path' => $mapped['category']['path'], 'sku' => $mapped['sku'], 'source_url' => $record['source_url'], 'source_url_was_derived' => (bool) ($record['source_url_was_derived'] ?? false)]),
            'last_verified_at' => null, 'created_at' => now(), 'updated_at' => now(),
        ];
        if (Schema::hasColumn('products', 'visibility_status')) {
            $data['visibility_status'] = config('elecforest_import.draft_visibility');
        }
        if (Schema::hasColumn('products', 'search_keywords')) {
            $data['search_keywords'] = implode(' ', array_filter([$content['name'], $record['supplier_sku'], $record['main_category'], $record['subcategory'], ...$record['generated_tags']]));
        }

        return (int) DB::table('products')->insertGetId($this->existingColumns('products', $data));
    }

    /** @param array<string, mixed> $mapped */
    private function updateManagedProduct(int $productId, array $mapped): void
    {
        $content = $mapped['content'];
        $metadata = json_decode((string) DB::table('products')->where('id', $productId)->value('metadata'), true) ?: [];
        $metadata['source_url_was_derived'] = (bool) ($mapped['record']['source_url_was_derived'] ?? false);
        $metadata['subtitle'] = $content['subtitle'];
        $metadata['key_features'] = $content['key_features'];
        $metadata['applications'] = $content['applications'];
        $metadata['compatibility'] = $content['compatibility'];
        $metadata['package_contents'] = $content['package_contents'];
        $metadata['usage_notes'] = $content['usage_notes'];
        $metadata['safety_notes'] = $content['safety_notes'];
        $metadata['warranty'] = $content['warranty'];
        $metadata['content_provenance'] = [
            'method' => 'deterministic_assisted', 'source_notes' => $content['source_notes'],
            'confidence_level' => $content['confidence_level'], 'last_updated' => $content['last_updated'],
            'advisory_disclaimer' => $content['advisory_disclaimer'],
        ];
        DB::table('products')->where('id', $productId)->update($this->existingColumns('products', [
            'name' => $content['name'], 'short_description' => $content['short_description'], 'description' => $content['description'],
            'category_id' => $mapped['category']['category_id'], 'confidence_level' => $content['confidence_level'],
            'normalized_value' => json_encode(['name' => $content['name'], 'category_path' => $mapped['category']['path'], 'source_url' => $mapped['record']['source_url'], 'source_url_was_derived' => (bool) ($mapped['record']['source_url_was_derived'] ?? false)]),
            'metadata' => json_encode($metadata),
            'updated_at' => now(),
        ]));
    }

    /** @param array<string, mixed> $record @param array<string, mixed> $mapped */
    private function persistSourceRecord(int $productId, int $sourceId, array $record, array $mapped): int
    {
        $existing = DB::table('supplier_products')->where('catalog_source_id', $sourceId)->where('source_product_id', $record['source_product_id'])->first();
        $data = [
            'product_id' => $productId, 'supplier_sku' => $record['supplier_sku'],
            'manufacturer_part_number' => $mapped['manufacturer']['mpn'], 'source_name' => $record['source_name'],
            'source_slug' => $record['source_slug'], 'source_url' => $record['source_url'], 'canonical_url' => $record['source_url'],
            'source_category_path_json' => json_encode([$record['main_category'], $record['subcategory']]),
            'source_brand' => null, 'source_manufacturer' => null, 'source_status' => $record['stock_status'],
            'source_currency' => $record['currency'] ?: null, 'source_price' => $record['price'],
            'source_compare_price' => $record['compare_at_price'], 'source_stock_state' => $record['stock_status'],
            'raw_payload_json' => json_encode($record['raw'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'content_hash' => $record['content_hash'], 'first_seen_at' => $existing->first_seen_at ?? now(),
            'last_seen_at' => now(), 'last_changed_at' => ! $existing || $existing->content_hash !== $record['content_hash'] ? now() : $existing->last_changed_at,
            'imported_at' => now(), 'review_status' => 'pending_review',
            'data_quality_score' => $this->qualityScore($record, $mapped), 'updated_at' => now(),
        ];
        DB::table('supplier_products')->updateOrInsert(
            ['catalog_source_id' => $sourceId, 'source_product_id' => $record['source_product_id']],
            $this->existingColumns('supplier_products', ['created_at' => $existing->created_at ?? now()] + $data)
        );

        return (int) DB::table('supplier_products')->where('catalog_source_id', $sourceId)->where('source_product_id', $record['source_product_id'])->value('id');
    }

    /** @param array<string, mixed> $record @param array<string, mixed> $mapped @return list<int> */
    private function persistAssets(int $supplierProductId, int $productId, array $record, string $name): array
    {
        $ids = [];
        foreach ($record['image_urls'] as $index => $url) {
            $existing = DB::table('supplier_product_assets')->where('supplier_product_id', $supplierProductId)->where('original_url', $url)->first();
            DB::table('supplier_product_assets')->updateOrInsert(
                ['supplier_product_id' => $supplierProductId, 'original_url' => $url],
                [
                    'asset_type' => 'image', 'canonical_url' => $url, 'rights_status' => 'pending_review',
                    'download_status' => $existing->download_status ?? 'not_requested', 'sort_order' => $index,
                    'alt_text' => Str::limit($name.($index === 0 ? ' product image' : ' product image '.($index + 1)), 250, ''),
                    'created_at' => $existing->created_at ?? now(), 'updated_at' => now(),
                ]
            );
            $ids[] = (int) DB::table('supplier_product_assets')->where('supplier_product_id', $supplierProductId)->where('original_url', $url)->value('id');
        }

        return $ids;
    }

    /** @param array<string, mixed> $record */
    private function persistOffer(int $supplierProductId, array $record): void
    {
        $raw = ['price' => $record['raw']['price'] ?? null, 'compare_at_price' => $record['raw']['compare_at_price'] ?? null, 'stock_status' => $record['stock_status'], 'quantity_text' => $record['quantity_text']];
        $latest = DB::table('supplier_product_offers')->where('supplier_product_id', $supplierProductId)->latest('id')->first();
        $latestRaw = $latest ? json_decode((string) $latest->raw_value, true) : null;
        if ($latestRaw !== $raw) {
            DB::table('supplier_product_offers')->insert([
                'supplier_product_id' => $supplierProductId, 'source_price' => $record['price'],
                'source_compare_price' => $record['compare_at_price'], 'currency' => $record['currency'] ?: null,
                'availability_status' => $record['stock_status'] ?: null, 'quantity_text' => $record['quantity_text'] ?: null,
                'observed_at' => $record['scraped_at'] ?: now(), 'source_url' => $record['source_url'],
                'raw_value' => json_encode($raw), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /** @param array<string, mixed> $record */
    private function persistIdentifiers(int $productId, int $sourceId, array $record, string $sku): void
    {
        $identifiers = [
            ['neogiga_sku', $sku, $this->identity->normalize($sku), true, 'internal_verified'],
            ['source_product_id', $record['source_product_id'], $this->identity->normalize($record['source_product_id']), false, 'source_unreviewed'],
        ];
        if ($record['supplier_sku']) {
            $identifiers[] = ['supplier_sku', $record['supplier_sku'], $this->identity->normalize($record['supplier_sku']), false, $record['supplier_sku_ambiguous'] ? 'ambiguous_source_value' : 'source_unreviewed'];
        }
        foreach ($identifiers as [$type, $value, $normalized, $verified, $confidence]) {
            DB::table('product_identifiers')->updateOrInsert(
                ['product_id' => $productId, 'identifier_type' => $type, 'normalized_value' => $normalized],
                ['catalog_source_id' => $type === 'neogiga_sku' ? null : $sourceId, 'identifier_value' => $value, 'is_verified' => $verified, 'confidence_level' => $confidence, 'source_url' => $record['source_url'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /** @param array<string, mixed> $record @param array<string, mixed> $mapped */
    private function persistSpecifications(int $productId, int $sourceId, array $record, array $mapped, bool $attachCanonical): void
    {
        $groupId = null;
        if ($mapped['specifications'] !== []) {
            $group = DB::table('product_spec_groups')->where('category_id', $mapped['category']['category_id'])->where('name', 'ElecForest Source Specifications')->first();
            if (! $group) {
                $groupId = (int) DB::table('product_spec_groups')->insertGetId(['category_id' => $mapped['category']['category_id'], 'name' => 'ElecForest Source Specifications', 'sort_order' => 90, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()]);
            } else {
                $groupId = (int) $group->id;
            }
        }
        foreach ($mapped['specifications'] as $index => $spec) {
            DB::table('product_source_specifications')->updateOrInsert(
                ['product_id' => $productId, 'catalog_source_id' => $sourceId, 'normalized_name' => $spec['normalized_name']],
                $spec + ['source_url' => $record['source_url'], 'created_at' => now(), 'updated_at' => now()]
            );
            $existing = DB::table('product_specs')->where('product_id', $productId)->where('name', $spec['normalized_name'])->exists();
            if ($attachCanonical && ! $existing) {
                DB::table('product_specs')->updateOrInsert(
                    ['product_id' => $productId, 'name' => $spec['normalized_name']],
                    ['spec_group_id' => $groupId, 'value' => $spec['normalized_value'], 'unit' => $spec['normalized_unit'], 'sort_order' => $index, 'is_visible' => true, 'is_filterable' => false, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    /** @param list<array<string, mixed>> $applications */
    private function persistApplications(int $productId, int $sourceId, array $applications): void
    {
        foreach ($applications as $application) {
            DB::table('product_applications')->updateOrInsert(
                ['product_id' => $productId, 'application' => $application['application']],
                ['catalog_source_id' => $sourceId, 'evidence_type' => 'source', 'source_notes' => $application['source_notes'], 'confidence' => $application['confidence'], 'is_verified' => false, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /** @param array<string, mixed> $category */
    private function persistCategoryAssignment(int $productId, int $sourceId, array $category): void
    {
        if (! $category['category_id']) {
            return;
        }
        DB::table('product_category_assignments')->updateOrInsert(
            ['product_id' => $productId, 'category_id' => $category['category_id']],
            ['catalog_source_id' => $sourceId, 'is_primary' => true, 'confidence' => $category['confidence'], 'mapping_status' => $category['status'], 'created_at' => now(), 'updated_at' => now()]
        );
    }

    /** @param array<string, mixed> $record */
    private function persistSourceLink(int $productId, int $sourceId, array $record): void
    {
        DB::table('catalog_product_sources')->updateOrInsert(
            ['source_id' => $sourceId, 'product_id' => $productId],
            [
                'source_part_id' => $record['source_product_id'], 'source_url' => $record['source_url'], 'source_payload_hash' => $record['content_hash'],
                'source_updated_at' => $record['scraped_at'], 'imported_at' => now(), 'last_synced_at' => now(),
                'data_quality_score' => 0.60, 'review_status' => 'pending_review',
                'raw_snapshot' => json_encode($record['raw'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at' => now(), 'updated_at' => now(),
            ]
        );
    }

    /** @param array<string, mixed> $content */
    private function persistContentVersion(int $productId, int $sourceId, array $content): void
    {
        $payload = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $latest = DB::table('product_content_versions')->where('product_id', $productId)->orderByDesc('version')->first();
        if ($latest) {
            $prior = json_decode((string) $latest->content_json, true) ?: [];
            $current = $content;
            unset($prior['last_updated'], $current['last_updated']);
            if (hash_equals(hash('sha256', json_encode($prior)), hash('sha256', json_encode($current)))) {
                return;
            }
        }
        DB::table('product_content_versions')->insert([
            'product_id' => $productId, 'catalog_source_id' => $sourceId, 'version' => ((int) ($latest->version ?? 0)) + 1,
            'content_method' => 'deterministic_assisted', 'status' => 'pending_review', 'content_json' => $payload,
            'source_notes' => $content['source_notes'], 'confidence_level' => $content['confidence_level'],
            'last_updated' => $content['last_updated'], 'advisory_disclaimer' => $content['advisory_disclaimer'],
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $mapped */
    private function persistSeo(int $productId, array $mapped): void
    {
        $record = $mapped['record'] ?? [];
        $keywords = array_filter([
            $record['supplier_sku'] ?? null,
            $record['main_category'] ?? null,
            $record['subcategory'] ?? null,
            ...($record['generated_tags'] ?? []),
            ...($record['site_tags'] ?? []),
            ...array_map(static fn (array $spec): mixed => $spec['normalized_name'] ?? null, array_slice($mapped['specifications'] ?? [], 0, 4)),
        ]);
        $seo = $this->seo->generate([
            'name' => $mapped['content']['name'], 'category_name' => $mapped['category']['category_name'],
            'sku' => $mapped['sku'], 'slug' => $mapped['slug'], 'keywords' => array_values($keywords),
        ], $mapped['content']);
        $data = [
            'title' => $seo['title'], 'meta_title' => $seo['meta_title'], 'meta_description' => $seo['meta_description'],
            'meta_keywords' => $seo['meta_keywords'], 'canonical_url' => $seo['canonical_url'], 'robots' => $seo['robots'],
            'schema_json' => json_encode($seo['schema_json']), 'schema_type' => $seo['schema_type'],
            'confidence_level' => $seo['confidence_level'], 'metadata' => json_encode($seo['metadata']),
            'og_title' => $seo['og_title'], 'og_description' => $seo['og_description'], 'og_image' => $seo['og_image'],
            'twitter_title' => $seo['twitter_title'], 'twitter_description' => $seo['twitter_description'], 'twitter_image' => $seo['twitter_image'],
            'breadcrumb_schema' => json_encode($seo['breadcrumb_schema']), 'product_schema' => json_encode($seo['product_schema']),
            'source_notes' => $seo['source_notes'], 'last_updated' => $seo['last_updated'], 'advisory_disclaimer' => $seo['advisory_disclaimer'],
            'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('product_seo_meta')->updateOrInsert(['product_id' => $productId], $this->existingColumns('product_seo_meta', $data));
        DB::table('products')->where('id', $productId)->update(['seo_meta' => json_encode($seo), 'updated_at' => now()]);
    }

    /** @param array<string, mixed> $mapped @param list<int> $assetIds */
    private function persistReviewTasks(int $productId, int $supplierProductId, int $sourceId, array $mapped, array $assetIds): void
    {
        $tasks = [];
        if (! $mapped['brand']['verified']) {
            $tasks['missing_verified_brand'] = 0.0;
        }
        if (! $mapped['manufacturer']['verified']) {
            $tasks['missing_verified_manufacturer'] = 0.0;
        }
        if ($mapped['category']['status'] !== 'auto_mapped') {
            $tasks['taxonomy_review'] = $mapped['category']['confidence'];
        }
        if ($assetIds !== []) {
            $tasks['media_rights_review'] = 0.0;
        }
        if ($mapped['applications'] === []) {
            $tasks['missing_source_applications'] = 0.0;
        }
        foreach ($tasks as $type => $confidence) {
            DB::table('catalog_review_tasks')->updateOrInsert(
                ['catalog_source_id' => $sourceId, 'supplier_product_id' => $supplierProductId, 'product_id' => $productId, 'task_type' => $type],
                ['status' => 'open', 'confidence' => $confidence, 'evidence_json' => json_encode(['source_url' => $mapped['record']['source_url'], 'source_notes' => $mapped['content']['source_notes']]), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function persistChangeEvent(int $sourceId, int $supplierProductId, string $runId, string $status, ?object $before, array $after): void
    {
        DB::table('catalog_change_events')->insert([
            'catalog_source_id' => $sourceId, 'supplier_product_id' => $supplierProductId,
            'catalog_import_run_id' => $runId, 'event_type' => 'elecforest_'.$status,
            'before_json' => $before ? json_encode((array) $before) : null,
            'after_json' => json_encode(['content_hash' => $after['content_hash'], 'source_url' => $after['source_url']]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $record @param array<string, mixed> $mapped */
    private function qualityScore(array $record, array $mapped): float
    {
        $score = 0.25;
        foreach (['source_name', 'source_url', 'description', 'main_category', 'supplier_sku', 'currency', 'stock_status'] as $field) {
            if (! empty($record[$field])) {
                $score += 0.07;
            }
        }
        if ($record['image_urls'] !== []) {
            $score += 0.08;
        }
        if ($mapped['category']['status'] === 'auto_mapped') {
            $score += 0.08;
        }

        return min(1.0, round($score, 2));
    }

    /** @param array<string, mixed> $options */
    private function createRun(string $file, array $options, string $mode): string
    {
        $runId = (string) Str::uuid();
        DB::table('catalog_import_runs')->insert($this->existingColumns('catalog_import_runs', [
            'id' => $runId, 'catalog_source_id' => $this->sourceId(), 'mode' => $mode,
            'status' => 'running', 'started_at' => now(), 'source_file' => $file,
            'file_checksum' => hash_file('sha256', $file), 'command_options' => json_encode($this->safeOptions($options)),
            'created_at' => now(), 'updated_at' => now(),
        ]));

        return $runId;
    }

    private function incrementRun(string $runId, int $lineNumber, string $status): void
    {
        $column = match ($status) {
            'created' => 'products_created', 'updated' => 'products_updated', 'unchanged' => 'products_unchanged',
            'rejected' => 'products_rejected', default => 'skipped_records',
        };
        $update = [
            'products_discovered' => DB::raw('products_discovered + 1'),
            $column => DB::raw($column.' + 1'), 'last_line' => max(0, $lineNumber), 'updated_at' => now(),
        ];
        if (in_array($status, ['created', 'updated'], true)) {
            $update['products_queued_for_review'] = DB::raw('products_queued_for_review + 1');
        }
        DB::table('catalog_import_runs')->where('id', $runId)->update($this->existingColumns('catalog_import_runs', $update));
    }

    private function finalizeRun(string $runId): void
    {
        DB::table('catalog_import_runs')->where('id', $runId)->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);
    }

    private function finalizeRunIfQueueDrained(string $runId): void
    {
        $run = DB::table('catalog_import_runs')->where('id', $runId)->first();
        if (! $run || ! in_array($run->mode, ['queue', 'resume', 'retry'], true)) {
            return;
        }
        $pending = DB::table('catalog_import_items')->where('catalog_import_run_id', $runId)->whereIn('status', ['queued', 'processing'])->exists();
        if (! $pending) {
            $this->finalizeRun($runId);
        }
    }

    /** @param array<string, mixed> $record @param array<string, mixed> $result */
    private function upsertItem(string $runId, array $record, string $status, array $result, ?int $supplierProductId = null, ?int $productId = null): void
    {
        DB::table('catalog_import_items')->updateOrInsert(
            ['catalog_import_run_id' => $runId, 'idempotency_key' => $record['idempotency_key']],
            [
                'source_url' => $record['source_url'], 'source_product_id' => $record['source_product_id'], 'status' => $status,
                'supplier_product_id' => $supplierProductId, 'product_id' => $productId, 'result_json' => json_encode($result),
                'created_at' => now(), 'updated_at' => now(),
            ]
        );
    }

    /** @param array<string, mixed> $raw */
    private function recordFailure(string $runId, ?int $lineNumber, ?string $key, \Throwable $exception, array $raw): void
    {
        $retryStatus = $exception instanceof \DomainException
            && str_contains($exception->getMessage(), 'Collection page is not a sellable product record.')
                ? 'not_retryable'
                : 'pending';

        DB::table('catalog_import_failures')->insert([
            'catalog_import_run_id' => $runId, 'line_number' => $lineNumber, 'idempotency_key' => $key,
            'error_class' => $exception::class, 'error_message' => Str::limit($exception->getMessage(), 10000, ''),
            'raw_record' => json_encode($raw, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'attempts' => 1, 'retry_status' => $retryStatus, 'last_attempted_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('catalog_import_runs')->where('id', $runId)->update($this->existingColumns('catalog_import_runs', [
            'failed_records' => DB::raw('failed_records + 1'), 'updated_at' => now(),
        ]));
    }

    /** @param array<string, mixed> $record @param array<string, mixed> $manufacturer @return array{product_id:?int,matched_by:?string,ambiguous:bool} */
    private function resolveWithoutSupplierSku(array $record, array $manufacturer, int $sourceId): array
    {
        $copy = $record;
        $copy['supplier_sku'] = null;

        return $this->identity->resolve($copy, $manufacturer, $sourceId);
    }

    private function isElecforestManaged(int $productId): bool
    {
        $metadata = json_decode((string) DB::table('products')->where('id', $productId)->value('metadata'), true);

        return ($metadata['elecforest_managed'] ?? false) === true;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function existingColumns(string $table, array $data): array
    {
        if (! isset($this->columnCache[$table])) {
            $this->columnCache[$table] = array_fill_keys(Schema::getColumnListing($table), true);
        }

        return array_filter($data, fn (string $column): bool => isset($this->columnCache[$table][$column]), ARRAY_FILTER_USE_KEY);
    }

    /** @param array<string, mixed> $options @return array<string, mixed> */
    private function safeOptions(array $options): array
    {
        unset($options['_duplicate_skus']);

        return $options;
    }

    /** @return array<string, int> */
    private function supplierSkuCounts(string $file): array
    {
        $counts = [];
        $stream = new SplFileObject($file, 'rb');
        foreach ($stream as $index => $line) {
            if (! is_string($line) || trim($line) === '') {
                continue;
            }
            try {
                $record = $this->parser->parse($line, $index + 1);
                if ($record['supplier_sku']) {
                    $counts[$record['supplier_sku']] = ($counts[$record['supplier_sku']] ?? 0) + 1;
                }
            } catch (\Throwable) {
            }
        }

        return $counts;
    }

    /** @param array<string, int> $values @return array{groups:int,extra_records:int} */
    private function duplicates(array $values): array
    {
        $groups = $extra = 0;
        foreach ($values as $count) {
            if ($count > 1) {
                $groups++;
                $extra += $count - 1;
            }
        }

        return ['groups' => $groups, 'extra_records' => $extra];
    }

    /** @param array<string, mixed> $options @return array<string, mixed> */
    private function dryRun(string $file, array $options, int $startLine, int $limit): array
    {
        $sourceId = (int) (DB::table('catalog_sources')->where('code', config('elecforest_import.source_code'))->value('id') ?: 0);
        $counters = $this->emptyCounters();
        $errors = [];
        $processed = 0;
        $stream = new SplFileObject($file, 'rb');
        foreach ($stream as $index => $line) {
            $lineNumber = $index + 1;
            if ($lineNumber < $startLine || ! is_string($line) || trim($line) === '') {
                continue;
            }
            if ($limit > 0 && $processed >= $limit) {
                break;
            }
            $processed++;
            try {
                $record = $this->parser->parse($line, $lineNumber);
                $validation = $this->validator->validate($record);
                if ($validation !== []) {
                    throw new \DomainException(implode(' ', $validation));
                }
                $this->mapper->map($record, $sourceId, false);
                $counters['created']++;
            } catch (\Throwable $exception) {
                $counters['rejected']++;
                $errors[] = ['line' => $lineNumber, 'error' => $exception->getMessage()];
            }
        }

        return [
            'run_id' => null, 'mode' => 'dry_run', 'status' => $errors === [] ? 'completed' : 'completed_with_rejections',
            'source_file' => $file, 'file_checksum' => hash_file('sha256', $file), 'counters' => $counters,
            'errors' => $errors, 'database_writes' => 0,
        ];
    }

    /** @return array{created:int,updated:int,unchanged:int,rejected:int,skipped:int} */
    private function emptyCounters(): array
    {
        return ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'rejected' => 0, 'skipped' => 0];
    }

    private function assertFile(string $file): string
    {
        $real = realpath($file);
        if ($real === false || ! is_file($real) || ! is_readable($real)) {
            throw new \InvalidArgumentException("ElecForest JSONL file is not readable: {$file}");
        }

        return $real;
    }
}
