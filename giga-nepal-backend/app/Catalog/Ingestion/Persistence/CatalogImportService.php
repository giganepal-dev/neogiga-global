<?php

namespace App\Catalog\Ingestion\Persistence;

use App\Catalog\Ingestion\Contracts\SupplierImporterInterface;
use App\Catalog\Ingestion\Normalizers\CatalogNormalizer;
use App\Catalog\Ingestion\Reports\CatalogImportReporter;
use App\Catalog\Ingestion\Suppliers\AdafruitImporter;
use App\Catalog\Ingestion\Suppliers\OkystarImporter;
use App\Catalog\Ingestion\Suppliers\WaveshareImporter;
use App\Catalog\Ingestion\Validation\SupplierPolicyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CatalogImportService
{
    public function __construct(
        private readonly SupplierPolicyService $policy,
        private readonly CatalogNormalizer $normalizer,
        private readonly CatalogImportReporter $reporter,
        private readonly AdafruitImporter $adafruit,
        private readonly WaveshareImporter $waveshare,
        private readonly OkystarImporter $okystar,
    ) {}

    /** @param array<string, mixed> $options @return array<string, mixed> */
    public function run(string $supplier, array $options): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $runId = (string) Str::uuid();
        $report = ['run_id' => $runId, 'supplier' => $supplier, 'mode' => $dryRun ? 'dry_run' : 'import', 'status' => 'blocked', 'started_at' => now()->toIso8601String(), 'counters' => $this->counters(), 'warnings' => [], 'failures' => []];
        try {
            $definition = $this->policy->assertImportAllowed($supplier);
        } catch (\Throwable $exception) {
            $report['failures'][] = $exception->getMessage();
            $report['report_path'] = $this->reporter->write($runId, $report);

            return $report;
        }

        $sourceId = (int) DB::table('catalog_sources')->where('code', $supplier)->value('id');
        if (! $dryRun) {
            DB::table('catalog_import_runs')->insert(['id' => $runId, 'catalog_source_id' => $sourceId, 'mode' => 'import', 'status' => 'running', 'started_at' => now(), 'command_options' => json_encode($options), 'created_at' => now(), 'updated_at' => now()]);
        }

        try {
            $limit = max(0, (int) ($options['limit'] ?? 0));
            $urls = ! empty($options['product']) ? [(string) $options['product']] : $this->adapter($supplier)->discover($definition, $limit);
            foreach ($urls as $url) {
                $report['counters']['pages_discovered']++;
                $candidate = $this->adapter($supplier)->parse($url, $definition);
                $report['counters']['pages_fetched']++;
                $report['counters']['products_discovered']++;
                if ($dryRun) {
                    $report['counters']['products_queued_for_review']++;

                    continue;
                }
                $outcome = $this->persistCandidate($sourceId, $supplier, $candidate, $runId);
                $report['counters'][$outcome]++;
            }
            $report['status'] = 'completed';
        } catch (\Throwable $exception) {
            $report['status'] = 'failed';
            $report['failures'][] = $exception->getMessage();
        }
        $report['completed_at'] = now()->toIso8601String();
        $report['report_path'] = $this->reporter->write($runId, $report);
        if (! $dryRun) {
            DB::table('catalog_import_runs')->where('id', $runId)->update([
                'status' => $report['status'], 'completed_at' => now(), 'pages_discovered' => $report['counters']['pages_discovered'],
                'pages_fetched' => $report['counters']['pages_fetched'], 'products_discovered' => $report['counters']['products_discovered'],
                'products_created' => $report['counters']['products_created'], 'products_updated' => $report['counters']['products_updated'],
                'products_queued_for_review' => $report['counters']['products_queued_for_review'], 'failures' => json_encode($report['failures']), 'updated_at' => now(),
            ]);
        }

        return $report;
    }

    /** @param array<string, mixed> $candidate */
    private function persistCandidate(int $sourceId, string $supplier, array $candidate, string $runId): string
    {
        $sourceProductId = (string) ($candidate['source_product_id'] ?: $candidate['supplier_sku'] ?: hash('sha256', $candidate['canonical_url'] ?? $candidate['source_url']));
        $hash = hash('sha256', json_encode($candidate, JSON_UNESCAPED_SLASHES));
        $existing = DB::table('supplier_products')->where('catalog_source_id', $sourceId)->where('source_product_id', $sourceProductId)->first();
        if ($existing && $existing->content_hash === $hash) {
            return 'products_unchanged';
        }
        $brandName = $this->normalizer->text($candidate['brand'] ?? $candidate['manufacturer'] ?? $supplier) ?? $supplier;
        $brandId = $this->brand($brandName);
        $mpn = $this->normalizer->mpn($candidate['mpn'] ?? null);
        $normalizedMpnExpression = DB::connection()->getDriverName() === 'pgsql'
            ? "upper(regexp_replace(coalesce(mpn, ''), '\\s+', '', 'g'))"
            : "upper(replace(coalesce(mpn, ''), ' ', ''))";
        $product = $mpn ? DB::table('products')->where('brand_id', $brandId)->whereRaw("{$normalizedMpnExpression} = ?", [$mpn])->first() : null;
        $created = false;
        if (! $product) {
            $productId = DB::table('products')->insertGetId($this->pendingProduct($supplier, $sourceProductId, $candidate, $brandId, $mpn));
            $created = true;
        } else {
            $productId = $product->id;
        }
        DB::table('supplier_products')->updateOrInsert(['catalog_source_id' => $sourceId, 'source_product_id' => $sourceProductId], [
            'product_id' => $productId, 'supplier_sku' => $candidate['supplier_sku'] ?? null, 'manufacturer_part_number' => $mpn,
            'source_name' => $this->normalizer->text($candidate['title'] ?? null), 'source_url' => $candidate['source_url'] ?? null,
            'canonical_url' => $candidate['canonical_url'] ?? null, 'source_brand' => $brandName, 'source_manufacturer' => $candidate['manufacturer'] ?? null,
            'source_status' => $candidate['source_status'] ?? null, 'source_currency' => $candidate['source_currency'] ?? null, 'source_price' => $candidate['source_price'] ?? null,
            'raw_payload_json' => json_encode($candidate['raw_payload'] ?? $candidate), 'content_hash' => $hash, 'first_seen_at' => $existing?->first_seen_at ?? now(),
            'last_seen_at' => now(), 'last_changed_at' => now(), 'imported_at' => now(), 'review_status' => config('catalog_import.review_status'), 'updated_at' => now(), 'created_at' => now(),
        ]);
        $supplierProductId = (int) DB::table('supplier_products')->where('catalog_source_id', $sourceId)->where('source_product_id', $sourceProductId)->value('id');
        DB::table('catalog_review_tasks')->insert(['catalog_source_id' => $sourceId, 'supplier_product_id' => $supplierProductId, 'product_id' => $productId, 'task_type' => $mpn ? 'supplier_product_review' : 'missing_mpn', 'status' => 'open', 'confidence' => $mpn ? 0.9 : 0.2, 'evidence_json' => json_encode(['source_url' => $candidate['source_url'] ?? null]), 'created_at' => now(), 'updated_at' => now()]);

        return $created ? 'products_created' : 'products_updated';
    }

    /** @return array<string, mixed> */
    private function pendingProduct(string $supplier, string $sourceProductId, array $candidate, int $brandId, ?string $mpn): array
    {
        $name = $this->normalizer->text($candidate['title'] ?? null) ?? "{$supplier} {$sourceProductId}";
        $skuBase = 'NG-'.strtoupper(substr($supplier, 0, 4)).'-'.strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $sourceProductId));
        $sku = Str::limit(trim($skuBase, '-'), 190, '');
        $suffix = 1;
        while (DB::table('products')->where('sku', $sku)->exists()) {
            $sku = Str::limit($skuBase, 180, '').'-'.++$suffix;
        }
        $slug = $this->normalizer->slug($name.' '.$sourceProductId);
        $slugBase = $slug;
        $index = 1;
        while (DB::table('products')->where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.++$index;
        }
        $data = ['name' => $name, 'slug' => $slug, 'sku' => $sku, 'mpn' => $mpn, 'brand_id' => $brandId, 'type' => 'simple', 'status' => 'pending', 'base_price' => 0, 'cost_price' => null, 'sale_price' => null, 'track_inventory' => false, 'stock_quantity' => 0, 'short_description' => null, 'description' => null, 'metadata' => json_encode(['source_name' => $supplier, 'source_url' => $candidate['source_url'] ?? null, 'license_note' => 'Supplier content pending rights and editorial review.', 'confidence_level' => 'source_unreviewed', 'imported_at' => now()->toIso8601String()]), 'created_at' => now(), 'updated_at' => now()];
        foreach (['approval_status' => 'pending_review', 'visibility_status' => 'hidden'] as $column => $value) {
            if (Schema::hasColumn('products', $column)) {
                $data[$column] = $value;
            }
        }

        return $data;
    }

    private function brand(string $name): int
    {
        $slug = $this->normalizer->slug($name);
        $brand = DB::table('product_brands')->where('slug', $slug)->first();
        if ($brand) {
            return (int) $brand->id;
        }

        return DB::table('product_brands')->insertGetId(['name' => $name, 'slug' => $slug, 'is_active' => false, 'is_featured' => false, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function adapter(string $supplier): SupplierImporterInterface
    {
        return match ($supplier) {
            'adafruit' => $this->adafruit, 'waveshare' => $this->waveshare, 'okystar' => $this->okystar, default => throw new \InvalidArgumentException("Unsupported supplier [{$supplier}].")
        };
    }

    /** @return array<string, int> */
    private function counters(): array
    {
        return array_fill_keys(['pages_discovered', 'pages_fetched', 'pages_skipped', 'products_discovered', 'products_created', 'products_updated', 'products_unchanged', 'products_rejected', 'products_queued_for_review', 'images_downloaded', 'documents_downloaded'], 0);
    }
}
