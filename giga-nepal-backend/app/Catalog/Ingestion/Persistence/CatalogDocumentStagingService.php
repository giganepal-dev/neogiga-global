<?php

namespace App\Catalog\Ingestion\Persistence;

use App\Catalog\Ingestion\Reports\CatalogImportReporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogDocumentStagingService
{
    /** @var list<string> */
    private const REQUIRED_COLUMNS = ['supplier_sku', 'product_name', 'source_name', 'source_file'];

    public function __construct(
        private readonly CatalogImportService $imports,
        private readonly CatalogImportReporter $reporter,
    ) {}

    /** @return array<string, mixed> */
    public function stage(string $file, array $options = []): array
    {
        if (! is_readable($file)) {
            throw new \InvalidArgumentException('The supplier quotation CSV is not readable.');
        }

        $sourceCode = Str::slug((string) ($options['source'] ?? 'sunny_okystar_quotation_files'), '_');
        if ($sourceCode === '') {
            throw new \InvalidArgumentException('A document source code is required.');
        }
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $runId = (string) Str::uuid();
        $report = [
            'run_id' => $runId,
            'supplier' => $sourceCode,
            'mode' => $dryRun ? 'supplier_document_dry_run' : 'supplier_document_staging',
            'status' => 'running',
            'started_at' => now()->toIso8601String(),
            'source_file' => $options['source_file'] ?? basename($file),
            'counters' => $this->counters(),
            'warnings' => ['Document values are staged as pending review. No media, inventory, marketplace price, or publication data is changed.'],
            'failures' => [],
        ];
        $handle = fopen($file, 'rb');
        if (! $handle) {
            throw new \InvalidArgumentException('The supplier quotation CSV could not be opened.');
        }

        try {
            $headers = fgetcsv($handle);
            $headers = is_array($headers) ? array_map(fn ($value) => Str::snake(trim((string) $value)), $headers) : [];
            $missing = array_values(array_diff(self::REQUIRED_COLUMNS, $headers));
            if ($missing !== []) {
                throw new \InvalidArgumentException('The supplier quotation CSV is missing required columns: '.implode(', ', $missing).'.');
            }

            $sourceId = $dryRun ? null : $this->source($sourceCode, $options);
            if (! $dryRun) {
                DB::table('catalog_import_runs')->insert([
                    'id' => $runId,
                    'catalog_source_id' => $sourceId,
                    'mode' => 'supplier_document_staging',
                    'status' => 'running',
                    'started_at' => now(),
                    'command_options' => json_encode($this->safeOptions($options)),
                    'actor_id' => $options['actor_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            while (($values = fgetcsv($handle)) !== false) {
                if ($this->isEmptyRow($values)) {
                    continue;
                }
                $report['counters']['products_discovered']++;
                $row = array_replace(
                    array_fill_keys($headers, null),
                    array_combine($headers, array_pad(array_slice($values, 0, count($headers)), count($headers), null)) ?: [],
                );
                $candidate = $this->candidate($row);
                if ($dryRun) {
                    $report['counters']['products_queued_for_review']++;

                    continue;
                }

                try {
                    $outcome = DB::transaction(fn () => $this->imports->persistCandidate((int) $sourceId, $sourceCode, $candidate, $runId));
                    $report['counters'][$outcome]++;
                    $report['counters']['products_queued_for_review']++;
                    $supplierProductId = DB::table('supplier_products')
                        ->where('catalog_source_id', $sourceId)
                        ->where('source_product_id', $candidate['source_product_id'])
                        ->value('id');
                    DB::table('catalog_import_items')->updateOrInsert([
                        'catalog_import_run_id' => $runId,
                        'idempotency_key' => hash('sha256', $sourceCode.'|'.$candidate['source_product_id'].'|'.json_encode($candidate['raw_payload'])),
                    ], [
                        'source_url' => $candidate['source_url'],
                        'source_product_id' => $candidate['source_product_id'],
                        'status' => $outcome,
                        'supplier_product_id' => $supplierProductId,
                        'product_id' => DB::table('supplier_products')->where('id', $supplierProductId)->value('product_id'),
                        'result_json' => json_encode(['review_status' => 'pending_review']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Throwable $exception) {
                    $report['counters']['products_rejected']++;
                    $report['failures'][] = ['supplier_sku' => $candidate['source_product_id'], 'reason' => $exception->getMessage()];
                }
            }
            $report['status'] = $report['counters']['products_rejected'] > 0 ? 'completed_with_errors' : 'completed';
        } catch (\Throwable $exception) {
            $report['status'] = 'failed';
            $report['failures'][] = $exception->getMessage();
        } finally {
            fclose($handle);
        }

        $report['completed_at'] = now()->toIso8601String();
        $report['report_path'] = $this->reporter->write($runId, $report);
        if (! $dryRun && isset($sourceId)) {
            DB::table('catalog_import_runs')->where('id', $runId)->update([
                'status' => $report['status'],
                'completed_at' => now(),
                'products_discovered' => $report['counters']['products_discovered'],
                'products_created' => $report['counters']['products_created'],
                'products_updated' => $report['counters']['products_updated'],
                'products_unchanged' => $report['counters']['products_unchanged'],
                'products_rejected' => $report['counters']['products_rejected'],
                'products_queued_for_review' => $report['counters']['products_queued_for_review'],
                'warnings' => json_encode($report['warnings']),
                'failures' => json_encode($report['failures']),
                'updated_at' => now(),
            ]);
        }

        return $report;
    }

    /** @param array<string, mixed> $options */
    private function source(string $code, array $options): int
    {
        $sourceUrl = (string) ($options['source_url'] ?? 'https://www.okystar.com/');
        DB::table('catalog_sources')->updateOrInsert(['code' => $code], [
            'name' => $options['source_name'] ?? 'Sunny / OKYSTAR quotation files',
            'source_url' => $sourceUrl,
            'license_notes' => 'User-provided supplier quotation. Public content and media reuse are not confirmed.',
            'active' => true,
            'source_type' => 'supplier_document',
            'base_url' => $sourceUrl,
            'catalogue_policy' => json_encode([
                'document_only' => true,
                'document_path' => $options['source_file'] ?? null,
                'media_rights_confirmed' => false,
                'public_reuse_status' => 'unconfirmed',
            ]),
            'import_enabled' => false,
            'media_download_enabled' => false,
            'description_reuse_status' => 'unknown',
            'status' => 'pending_manual_review',
            'updated_at' => now(),
            'created_at' => now(),
        ]);
        $sourceId = (int) DB::table('catalog_sources')->where('code', $code)->value('id');
        DB::table('supplier_sources')->updateOrInsert([
            'catalog_source_id' => $sourceId,
            'source_url' => 'uploaded://'.ltrim((string) ($options['source_file'] ?? 'supplier-quotation.csv'), '/'),
        ], [
            'name' => basename((string) ($options['source_file'] ?? 'supplier-quotation.csv')),
            'source_kind' => 'supplier_quotation_document',
            'priority' => 100,
            'enabled' => false,
            'configuration_json' => json_encode(['uploaded_by' => $options['actor_id'] ?? null]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $sourceId;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function candidate(array $row): array
    {
        $sourceUrl = filled($row['source_url'] ?? null) ? $row['source_url'] : null;
        $canonicalUrl = filled($row['source_page_url'] ?? null) ? $row['source_page_url'] : null;

        return [
            'source_product_id' => (string) ($row['supplier_sku'] ?? ''),
            'supplier_sku' => (string) ($row['supplier_sku'] ?? ''),
            'title' => $row['product_name'] ?? null,
            'mpn' => null,
            'source_url' => $sourceUrl,
            'canonical_url' => $canonicalUrl,
            'source_currency' => 'USD',
            'source_price' => $this->decimal($row['quoted_unit_price_usd'] ?? null),
            'source_compare_price' => $this->decimal($row['standard_unit_price_usd'] ?? null),
            'source_moq' => $this->integer($row['quoted_quantity'] ?? null),
            'category_path' => filled($row['category_hint'] ?? null) ? [(string) $row['category_hint']] : [],
            'specifications' => $this->specifications((string) ($row['raw_specs'] ?? '')),
            'assets' => [],
            'raw_payload' => $row,
        ];
    }

    /** @return list<array{label:string,value:string}> */
    private function specifications(string $raw): array
    {
        $specifications = [];
        foreach (preg_split('/[\r\n|]+/', $raw) ?: [] as $line) {
            if (preg_match('/^\s*([^:：]{1,120})\s*[:：]\s*(.+?)\s*$/u', $line, $matches)) {
                $specifications[] = ['label' => trim($matches[1]), 'value' => trim($matches[2])];
            }
        }

        return $specifications;
    }

    private function decimal(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value >= 0 ? (float) $value : null;
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /** @param list<string> $values */
    private function isEmptyRow(array $values): bool
    {
        return count(array_filter($values, fn ($value) => filled($value))) === 0;
    }

    /** @param array<string, mixed> $options @return array<string, mixed> */
    private function safeOptions(array $options): array
    {
        return array_intersect_key($options, array_flip(['source', 'source_name', 'source_url', 'source_file', 'actor_id']));
    }

    /** @return array<string, int> */
    private function counters(): array
    {
        return array_fill_keys(['products_discovered', 'products_created', 'products_updated', 'products_unchanged', 'products_rejected', 'products_queued_for_review'], 0);
    }
}
