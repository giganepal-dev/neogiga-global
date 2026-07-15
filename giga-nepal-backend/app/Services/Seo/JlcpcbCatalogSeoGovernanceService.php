<?php

namespace App\Services\Seo;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductSeoMeta;
use App\Services\Catalog\BrandVisibilityService;
use App\Services\Catalog\JlcpcbQualifiedPublicationService;
use App\Services\Product\ProductPublicationGate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class JlcpcbCatalogSeoGovernanceService
{
    private const PLAN_VERSION = 'jlcpcb-catalog-seo-governance-v3';

    private const PRODUCT_MANIFEST_VERSION = 'jlcpcb-catalog-seo-product-manifest-v1';

    private const PRODUCT_CHUNK_SIZE = 500;

    private const MAX_MANIFEST_LINE_BYTES = 65_536;

    private const SOURCE_CODE = 'jlcpcb_parts_database';

    private const GENERATED_SOURCE = 'catalog_seo_template';

    private ?Marketplace $resolvedGlobalMarketplace = null;

    public function __construct(
        private readonly CatalogSeoTemplateService $templates,
        private readonly ProductPublicationGate $publicationGate,
        private readonly JlcpcbQualifiedPublicationService $backupVerifier,
        private readonly BrandVisibilityService $brands,
    ) {}

    /** @return array<string, mixed> */
    public function plan(): array
    {
        $this->assertSchema();

        return $this->buildPlan();
    }

    /** @param array<string, mixed> $plan @return array<string, mixed> */
    public function forOutput(array $plan): array
    {
        unset($plan['eligible_rows']);
        unset($plan['product_manifest']['path']);

        return $plan;
    }

    /** @return array<string, mixed> */
    public function apply(string $expectedPlanHash, string $backupDirectory, int $batchSize = 250): array
    {
        $this->assertSchema();
        $expectedPlanHash = strtolower(trim($expectedPlanHash));
        if (preg_match('/^[a-f0-9]{64}$/', $expectedPlanHash) !== 1) {
            throw new RuntimeException('The exact 64-character SHA-256 --plan-hash from the current dry run is required.');
        }
        if ($batchSize < 1 || $batchSize > 1_000) {
            throw new RuntimeException('The bounded batch size must be between 1 and 1000.');
        }

        $backup = $this->backupVerifier->verifyBackup($backupDirectory);
        $plan = $this->buildPlan();
        if (! hash_equals((string) $plan['plan_hash'], $expectedPlanHash)) {
            throw new RuntimeException('The supplied plan hash does not match the current JLCPCB SEO-governance plan; run a new dry run.');
        }

        $productManifest = $this->openVerifiedProductManifest($plan);

        $changed = ['products' => 0, 'brands' => 0, 'categories' => 0];
        $batches = 0;
        try {
            $this->afterPlanVerified($plan);

            foreach (array_chunk($plan['eligible_rows'], $batchSize) as $chunk) {
                DB::transaction(function () use ($chunk, &$changed): void {
                    foreach ($chunk as $entry) {
                        match ($entry['entity_type']) {
                            'brand' => $this->applyBrand($entry, $changed),
                            'category' => $this->applyCategory($entry, $changed),
                            default => throw new RuntimeException('The SEO plan contains an unsupported entity type.'),
                        };
                    }
                }, 3);
                $batches++;
            }

            $this->eachProductManifestChunk($productManifest, $batchSize, function (array $chunk) use (&$changed, &$batches): void {
                DB::transaction(function () use ($chunk, &$changed): void {
                    $this->applyProductChunk($chunk, $changed);
                }, 3);
                $batches++;
            });
        } finally {
            fclose($productManifest);
        }

        if ($changed['products'] !== (int) $plan['eligible_products']) {
            throw new RuntimeException('The missing JLCPCB product SEO set changed during bounded apply; committed batches remain auditable and a new dry run is required.');
        }

        if ($changed['brands'] > 0) {
            $this->brands->clear();
        } elseif (array_sum($changed) > 0) {
            Cache::forever('seo:sitemap-version', (string) now()->getTimestampMs());
        }

        return [
            'status' => 'completed',
            'plan_hash' => $plan['plan_hash'],
            'backup' => $backup,
            'products_created' => $changed['products'],
            'brands_transitioned' => $changed['brands'],
            'categories_transitioned' => $changed['categories'],
            'batches_committed' => $batches,
            'source_notes' => 'Inserted only missing JLCPCB product SEO rows and transitioned only untouched importer-owned brand/category SEO metadata.',
            'confidence_level' => 'high_scope_confidence',
            'last_updated' => now()->toIso8601String(),
            'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
        ];
    }

    /** @param array<string, mixed> $plan */
    protected function afterPlanVerified(array $plan): void
    {
        // Deliberate no-op extension seam for operation-level audit instrumentation.
    }

    /** @return array<string, mixed> */
    private function buildPlan(): array
    {
        $marketplace = $this->globalMarketplace();
        $entries = [];
        $eligibleCounts = ['product' => 0, 'brand' => 0, 'category' => 0];
        $eligibleDigestContexts = [
            'product' => hash_init('sha256'),
            'brand' => hash_init('sha256'),
            'category' => hash_init('sha256'),
        ];
        $skipped = ['products' => [], 'brands' => [], 'categories' => []];
        $sourceProducts = $this->sourceProducts();
        $linkedProducts = (clone $sourceProducts)->distinct()->count('p.id');
        $brandIds = (clone $sourceProducts)->whereNotNull('p.brand_id')->distinct()->pluck('p.brand_id')->map(fn ($id) => (int) $id)->all();
        $leafCategoryIds = (clone $sourceProducts)->whereNotNull('p.category_id')->distinct()->pluck('p.category_id')->map(fn ($id) => (int) $id)->all();
        $categoryIds = $this->withAncestorCategories($leafCategoryIds);

        [$manifestHandle, $manifestTemporaryPath] = $this->createProductManifestWriter();
        try {
            $productRobots = [];
            $this->eachMissingProductEntryChunk($marketplace, self::PRODUCT_CHUNK_SIZE, function (array $chunk) use (
                &$eligibleCounts,
                $eligibleDigestContexts,
                &$productRobots,
                $manifestHandle,
            ): void {
                foreach ($chunk as $entry) {
                    $encoded = $this->manifestLine($entry);
                    $this->writeAll($manifestHandle, $encoded);
                    $generated = $entry['generated'];
                    $this->increment($productRobots, (string) $generated['robots']);
                    $eligibleCounts['product']++;
                    hash_update($eligibleDigestContexts['product'], $encoded);
                }
            });

            foreach (ProductBrand::query()->whereIn('id', $brandIds)->orderBy('id')->get() as $brand) {
                if ($reason = $this->brandSkipReason($brand)) {
                    $this->increment($skipped['brands'], $reason);

                    continue;
                }
                $generated = $this->stableGenerated($this->templates->brand($brand, $marketplace, 'en'));
                if ($generated['robots'] !== 'index,follow') {
                    $this->increment($skipped['brands'], 'generated_not_indexable');

                    continue;
                }
                $entry = [
                    'entity_type' => 'brand',
                    'entity_id' => (int) $brand->id,
                    'current_hash' => $this->brandFingerprint($brand),
                    'generated' => $generated,
                ];
                $entries[] = $entry;
                $eligibleCounts['brand']++;
                hash_update($eligibleDigestContexts['brand'], $this->json($entry)."\n");
            }

            foreach (ProductCategory::query()->whereIn('id', $categoryIds)->orderBy('id')->get() as $category) {
                if ($reason = $this->categorySkipReason($category)) {
                    $this->increment($skipped['categories'], $reason);

                    continue;
                }
                $generated = $this->stableGenerated($this->templates->category($category, $marketplace, 'en'));
                if ($generated['robots'] !== 'index,follow') {
                    $this->increment($skipped['categories'], 'generated_not_indexable');

                    continue;
                }
                $entry = [
                    'entity_type' => 'category',
                    'entity_id' => (int) $category->id,
                    'current_hash' => $this->categoryFingerprint($category),
                    'generated' => $generated,
                ];
                $entries[] = $entry;
                $eligibleCounts['category']++;
                hash_update($eligibleDigestContexts['category'], $this->json($entry)."\n");
            }

            foreach ($skipped as &$reasons) {
                ksort($reasons);
            }
            unset($reasons);

            $eligibleDigests = [];
            foreach ($eligibleDigestContexts as $type => $context) {
                $eligibleDigests[$type] = hash_final($context);
            }
            $hashPayload = [
                'plan_version' => self::PLAN_VERSION,
                'product_manifest_version' => self::PRODUCT_MANIFEST_VERSION,
                'source_code' => self::SOURCE_CODE,
                'template_version' => CatalogSeoTemplateService::TEMPLATE_VERSION,
                'marketplace_id' => (int) $marketplace->id,
                'eligible_counts' => $eligibleCounts,
                'eligible_digests' => $eligibleDigests,
            ];
            $planHash = hash('sha256', $this->json($hashPayload));
            $productManifest = $this->commitProductManifest(
                $manifestHandle,
                $manifestTemporaryPath,
                $planHash,
                $eligibleCounts['product'],
                $eligibleDigests['product'],
            );
            $manifestHandle = null;
            $manifestTemporaryPath = null;

            return [
                'plan_version' => self::PLAN_VERSION,
                'source_code' => self::SOURCE_CODE,
                'template_version' => CatalogSeoTemplateService::TEMPLATE_VERSION,
                'marketplace_code' => 'GLOBAL',
                'approved_public_source_products' => $linkedProducts,
                'linked_brands' => count($brandIds),
                'linked_categories_including_ancestors' => count($categoryIds),
                'eligible_products' => $eligibleCounts['product'],
                'product_robots' => $productRobots,
                'eligible_brands' => $eligibleCounts['brand'],
                'eligible_categories' => $eligibleCounts['category'],
                'skipped' => $skipped,
                'plan_hash' => $planHash,
                'eligible_digests' => $eligibleDigests,
                'product_manifest' => $productManifest,
                'source_notes' => 'Dry-run plan covers only approved/public JLCPCB products missing product_seo_meta and untouched importer-owned JLCPCB brand/category SEO metadata. Exact product IDs, fingerprints, and generated payloads are bound to a read-only streamed manifest.',
                'confidence_level' => 'high_scope_confidence',
                'last_updated' => now()->toIso8601String(),
                'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
                'eligible_rows' => $entries,
            ];
        } catch (Throwable $exception) {
            if (is_resource($manifestHandle)) {
                fclose($manifestHandle);
            }
            if (is_string($manifestTemporaryPath) && is_file($manifestTemporaryPath)) {
                File::delete($manifestTemporaryPath);
            }

            throw $exception;
        }
    }

    /** @param callable(list<array<string, mixed>>):void $callback */
    private function eachMissingProductEntryChunk(Marketplace $marketplace, int $chunkSize, callable $callback): void
    {
        $lastProductId = 0;
        do {
            $rows = $this->sourceProducts()
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('product_seo_meta as psm')->whereColumn('psm.product_id', 'p.id'))
                ->where('p.id', '>', $lastProductId)
                ->select('p.id')
                ->distinct()
                ->orderBy('p.id')
                ->limit($chunkSize)
                ->get();
            if ($rows->isEmpty()) {
                break;
            }

            $entries = [];
            $products = Product::query()->whereIn('id', $rows->pluck('id'))->orderBy('id')->get();
            foreach ($products as $product) {
                $entries[] = [
                    'entity_type' => 'product',
                    'entity_id' => (int) $product->id,
                    'current_hash' => $this->productFingerprint($product),
                    'generated' => $this->stableGenerated($this->templates->product($product, $marketplace, 'en')),
                ];
            }

            $callback($entries);
            $lastProductId = (int) $rows->last()->id;
        } while ($rows->count() === $chunkSize);
    }

    /** @return array{0: resource, 1: string} */
    private function createProductManifestWriter(): array
    {
        $directory = storage_path('app/catalog-seo-governance/plans');
        File::ensureDirectoryExists($directory, 0770, true);
        $path = $directory.'/.building-'.bin2hex(random_bytes(16)).'.ndjson';
        $handle = fopen($path, 'x+b');
        if ($handle === false) {
            throw new RuntimeException('Unable to create the bounded JLCPCB SEO product-manifest artifact.');
        }

        return [$handle, $path];
    }

    /** @param resource $handle @return array<string, int|string> */
    private function commitProductManifest(
        mixed $handle,
        string $temporaryPath,
        string $planHash,
        int $entryCount,
        string $digest,
    ): array {
        if (! is_resource($handle) || ! fflush($handle)) {
            throw new RuntimeException('Unable to flush the bounded JLCPCB SEO product-manifest artifact.');
        }
        if (function_exists('fsync') && ! fsync($handle)) {
            throw new RuntimeException('Unable to durably sync the bounded JLCPCB SEO product-manifest artifact.');
        }
        fclose($handle);
        if (! chmod($temporaryPath, 0440)) {
            throw new RuntimeException('Unable to make the JLCPCB SEO product-manifest artifact read-only.');
        }

        $path = dirname($temporaryPath).'/'.$planHash.'.ndjson';
        if (! rename($temporaryPath, $path)) {
            throw new RuntimeException('Unable to atomically publish the JLCPCB SEO product-manifest artifact.');
        }
        if (! chmod($path, 0440)) {
            throw new RuntimeException('Unable to preserve read-only permissions on the JLCPCB SEO product-manifest artifact.');
        }
        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException('Unable to measure the JLCPCB SEO product-manifest artifact.');
        }

        return [
            'version' => self::PRODUCT_MANIFEST_VERSION,
            'format' => 'canonical-ndjson',
            'entry_count' => $entryCount,
            'sha256' => $digest,
            'size_bytes' => $size,
            'path' => $path,
        ];
    }

    /** @param array<string, mixed> $plan @return resource */
    private function openVerifiedProductManifest(array $plan): mixed
    {
        $descriptor = $plan['product_manifest'] ?? null;
        if (! is_array($descriptor)
            || ($descriptor['version'] ?? null) !== self::PRODUCT_MANIFEST_VERSION
            || ($descriptor['format'] ?? null) !== 'canonical-ndjson'
            || (int) ($descriptor['entry_count'] ?? -1) !== (int) ($plan['eligible_products'] ?? -2)
            || ! hash_equals((string) ($descriptor['sha256'] ?? ''), (string) ($plan['eligible_digests']['product'] ?? ''))) {
            throw new RuntimeException('The SEO plan does not contain a valid plan-bound product-manifest descriptor.');
        }

        $expectedPath = storage_path('app/catalog-seo-governance/plans/'.(string) $plan['plan_hash'].'.ndjson');
        $path = (string) ($descriptor['path'] ?? '');
        if ($path !== $expectedPath || ! is_file($path) || is_link($path)) {
            throw new RuntimeException('The plan-bound JLCPCB SEO product-manifest artifact is missing or has an invalid path.');
        }
        $permissions = fileperms($path);
        if ($permissions === false || ($permissions & 0222) !== 0) {
            throw new RuntimeException('The plan-bound JLCPCB SEO product-manifest artifact must be read-only before apply.');
        }
        $size = filesize($path);
        if ($size === false || $size !== (int) ($descriptor['size_bytes'] ?? -1)) {
            throw new RuntimeException('The plan-bound JLCPCB SEO product-manifest artifact size changed before apply.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the plan-bound JLCPCB SEO product-manifest artifact.');
        }

        try {
            $digest = hash_init('sha256');
            $count = 0;
            $lastProductId = 0;
            while (($line = fgets($handle, self::MAX_MANIFEST_LINE_BYTES + 1)) !== false) {
                if (! str_ends_with($line, "\n")) {
                    throw new RuntimeException('The plan-bound JLCPCB SEO product manifest contains an oversized or incomplete line.');
                }
                $entry = json_decode(substr($line, 0, -1), true, 512, JSON_THROW_ON_ERROR);
                $this->assertValidProductManifestEntry($entry, $lastProductId);
                if (! hash_equals($line, $this->manifestLine($entry))) {
                    throw new RuntimeException('The plan-bound JLCPCB SEO product manifest is not canonically encoded.');
                }
                hash_update($digest, $line);
                $lastProductId = (int) $entry['entity_id'];
                $count++;
                if ($count > (int) $descriptor['entry_count']) {
                    throw new RuntimeException('The plan-bound JLCPCB SEO product manifest contains more entries than planned.');
                }
            }
            if (! feof($handle)) {
                throw new RuntimeException('The plan-bound JLCPCB SEO product manifest could not be read completely.');
            }
            if ($count !== (int) $descriptor['entry_count']
                || ! hash_equals((string) $descriptor['sha256'], hash_final($digest))) {
                throw new RuntimeException('The plan-bound JLCPCB SEO product manifest count or digest changed before apply.');
            }
            if (! rewind($handle)) {
                throw new RuntimeException('Unable to rewind the verified JLCPCB SEO product manifest for bounded apply.');
            }

            return $handle;
        } catch (Throwable $exception) {
            fclose($handle);

            throw $exception;
        }
    }

    /** @param resource $handle @param callable(list<array<string, mixed>>):void $callback */
    private function eachProductManifestChunk(mixed $handle, int $chunkSize, callable $callback): void
    {
        $chunk = [];
        while (($line = fgets($handle, self::MAX_MANIFEST_LINE_BYTES + 1)) !== false) {
            if (! str_ends_with($line, "\n")) {
                throw new RuntimeException('The verified JLCPCB SEO product manifest became incomplete during apply.');
            }
            $entry = json_decode(substr($line, 0, -1), true, 512, JSON_THROW_ON_ERROR);
            $chunk[] = $entry;
            if (count($chunk) === $chunkSize) {
                $callback($chunk);
                $chunk = [];
            }
        }
        if (! feof($handle)) {
            throw new RuntimeException('The verified JLCPCB SEO product manifest could not be read completely during apply.');
        }
        if ($chunk !== []) {
            $callback($chunk);
        }
    }

    /** @param array<string, mixed> $entry */
    private function manifestLine(array $entry): string
    {
        $line = $this->json($entry)."\n";
        if (strlen($line) > self::MAX_MANIFEST_LINE_BYTES) {
            throw new RuntimeException('A JLCPCB SEO product-manifest entry exceeded the bounded line size.');
        }

        return $line;
    }

    /** @param resource $handle */
    private function writeAll(mixed $handle, string $value): void
    {
        $offset = 0;
        $length = strlen($value);
        while ($offset < $length) {
            $written = fwrite($handle, substr($value, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Unable to write the bounded JLCPCB SEO product-manifest artifact.');
            }
            $offset += $written;
        }
    }

    private function assertValidProductManifestEntry(mixed $entry, int $lastProductId): void
    {
        $requiredGeneratedKeys = [
            'title', 'description', 'canonical', 'robots', 'robots_reason', 'template_version',
            'source_notes', 'confidence_level', 'advisory_disclaimer',
        ];
        $generatedKeys = is_array($entry) && is_array($entry['generated'] ?? null) ? array_keys($entry['generated']) : [];
        sort($generatedKeys);
        sort($requiredGeneratedKeys);
        if (! is_array($entry)
            || ($entry['entity_type'] ?? null) !== 'product'
            || ! is_int($entry['entity_id'] ?? null)
            || $entry['entity_id'] <= $lastProductId
            || preg_match('/^[a-f0-9]{64}$/', (string) ($entry['current_hash'] ?? '')) !== 1
            || ! is_array($entry['generated'] ?? null)
            || $generatedKeys !== $requiredGeneratedKeys) {
            throw new RuntimeException('The plan-bound JLCPCB SEO product manifest contains an invalid or out-of-order entry.');
        }
        foreach ($entry['generated'] as $value) {
            if (! is_string($value)) {
                throw new RuntimeException('The plan-bound JLCPCB SEO product manifest contains a non-string generated field.');
            }
        }
    }

    private function applyProduct(array $entry, array &$changed): void
    {
        if (ProductSeoMeta::query()->where('product_id', $entry['entity_id'])->exists()) {
            throw new RuntimeException("Product {$entry['entity_id']} gained an SEO row after planning; refusing to overwrite it.");
        }
        $product = Product::findOrFail($entry['entity_id']);
        if (! hash_equals($entry['current_hash'], $this->productFingerprint($product))) {
            throw new RuntimeException("Product {$entry['entity_id']} changed after planning.");
        }

        $generated = $entry['generated'];
        $now = now();
        $record = new ProductSeoMeta;
        $record->forceFill([
            'product_id' => $product->id,
            'title' => $generated['title'],
            'meta_title' => $generated['title'],
            'meta_description' => $generated['description'],
            'canonical_url' => $generated['canonical'],
            'robots' => $generated['robots'],
            'schema_type' => 'Product',
            'confidence_level' => $generated['confidence_level'],
            'generated_title' => $generated['title'],
            'generated_description' => $generated['description'],
            'generated_canonical_url' => $generated['canonical'],
            'generated_robots' => $generated['robots'],
            'robots_reason' => $generated['robots_reason'],
            'template_version' => $generated['template_version'],
            'is_manual_override' => false,
            'is_locked' => false,
            'active_source' => 'generated',
            'generated_at' => $now,
            'source_notes' => $generated['source_notes'],
            'last_updated' => $now,
            'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
            'metadata' => [
                'source' => self::GENERATED_SOURCE,
                'generation_scope' => 'jlcpcb_missing_only',
                'source_notes' => $generated['source_notes'],
                'confidence_level' => $generated['confidence_level'],
                'last_updated' => $now->toIso8601String(),
                'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
            ],
        ])->save();
        $this->templates->recordVersion('product', $product->id, $generated + ['active_source' => 'generated'], 'generated', null, $this->globalMarketplace()->id, 'en');
        $changed['products']++;
    }

    /** @param list<array<string, mixed>> $entries */
    private function applyProductChunk(array $entries, array &$changed): void
    {
        if ($entries === []) {
            return;
        }

        $productIds = array_map(static fn (array $entry): int => (int) $entry['entity_id'], $entries);
        $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        if ($products->count() !== count($productIds)) {
            throw new RuntimeException('One or more planned JLCPCB products disappeared during SEO apply.');
        }
        $existing = ProductSeoMeta::query()->whereIn('product_id', $productIds)->orderBy('product_id')->lockForUpdate()->pluck('product_id')->all();
        if ($existing !== []) {
            throw new RuntimeException('One or more products gained an SEO row after planning; refusing to overwrite them.');
        }
        $stillEligibleIds = $this->sourceProducts()
            ->whereIn('p.id', $productIds)
            ->select('p.id')
            ->distinct()
            ->orderBy('p.id')
            ->pluck('p.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $expectedIds = $productIds;
        sort($expectedIds, SORT_NUMERIC);
        if ($stillEligibleIds !== $expectedIds) {
            throw new RuntimeException('One or more plan-bound JLCPCB products are no longer approved and public during SEO apply.');
        }

        $maxVersions = DB::table('catalog_seo_versions')
            ->where('entity_type', 'product')
            ->whereIn('entity_id', $productIds)
            ->where('marketplace_id', $this->globalMarketplace()->id)
            ->where('locale', 'en')
            ->select('entity_id', DB::raw('MAX(version) AS max_version'))
            ->groupBy('entity_id')
            ->pluck('max_version', 'entity_id');
        $now = now();
        $seoRows = [];
        $versionRows = [];

        foreach ($entries as $entry) {
            $product = $products->get((int) $entry['entity_id']);
            if (! $product || ! hash_equals($entry['current_hash'], $this->productFingerprint($product))) {
                throw new RuntimeException("Product {$entry['entity_id']} changed after planning.");
            }

            $generated = $entry['generated'];
            $metadata = [
                'source' => self::GENERATED_SOURCE,
                'generation_scope' => 'jlcpcb_missing_only',
                'source_notes' => $generated['source_notes'],
                'confidence_level' => $generated['confidence_level'],
                'last_updated' => $now->toIso8601String(),
                'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
            ];
            $seoRows[] = [
                'product_id' => (int) $product->id,
                'title' => $generated['title'],
                'meta_title' => $generated['title'],
                'meta_description' => $generated['description'],
                'canonical_url' => $generated['canonical'],
                'robots' => $generated['robots'],
                'schema_type' => 'Product',
                'confidence_level' => $generated['confidence_level'],
                'generated_title' => $generated['title'],
                'generated_description' => $generated['description'],
                'generated_canonical_url' => $generated['canonical'],
                'generated_robots' => $generated['robots'],
                'robots_reason' => $generated['robots_reason'],
                'template_version' => $generated['template_version'],
                'is_manual_override' => false,
                'is_locked' => false,
                'active_source' => 'generated',
                'generated_at' => $now,
                'source_notes' => $generated['source_notes'],
                'last_updated' => $now,
                'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
                'metadata' => $this->json($metadata),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $versionRows[] = [
                'entity_type' => 'product',
                'entity_id' => (int) $product->id,
                'marketplace_id' => $this->globalMarketplace()->id,
                'locale' => 'en',
                'version' => ((int) ($maxVersions[(int) $product->id] ?? 0)) + 1,
                'active_source' => 'generated',
                'title' => $generated['title'],
                'description' => $generated['description'],
                'canonical_url' => $generated['canonical'],
                'robots' => $generated['robots'],
                'robots_reason' => $generated['robots_reason'],
                'template_version' => $generated['template_version'],
                'change_type' => 'generated',
                'changed_by' => null,
                'source_notes' => $generated['source_notes'],
                'confidence_level' => $generated['confidence_level'],
                'last_updated' => $now,
                'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
                'snapshot' => $this->json($generated + ['active_source' => 'generated']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('product_seo_meta')->insert($seoRows);
        DB::table('catalog_seo_versions')->insert($versionRows);
        $changed['products'] += count($entries);
    }

    private function applyBrand(array $entry, array &$changed): void
    {
        $brand = ProductBrand::query()->whereKey($entry['entity_id'])->lockForUpdate()->firstOrFail();
        if (! hash_equals($entry['current_hash'], $this->brandFingerprint($brand)) || $this->brandSkipReason($brand) !== null) {
            throw new RuntimeException("Brand {$entry['entity_id']} changed after planning.");
        }
        $metadata = $this->jsonArray($brand->seo_meta);
        $additionalUpdates = [];
        if ($this->isLegacyGeneratedBrandSeo($brand)) {
            $metadata['legacy_generated_fields'] = [
                'seo_title' => $brand->seo_title,
                'seo_description' => $brand->seo_description,
                'canonical_url' => $brand->canonical_url,
                'source_notes' => 'Archived exact legacy generator fields before enabling marketplace-dynamic SEO.',
            ];
            $additionalUpdates = ['seo_title' => null, 'seo_description' => null, 'canonical_url' => null];
        }
        $this->transitionEntity('brand', $brand->id, 'product_brands', $metadata, $entry['generated'], $additionalUpdates);
        $changed['brands']++;
    }

    private function applyCategory(array $entry, array &$changed): void
    {
        $category = ProductCategory::query()->whereKey($entry['entity_id'])->lockForUpdate()->firstOrFail();
        if (! hash_equals($entry['current_hash'], $this->categoryFingerprint($category)) || $this->categorySkipReason($category) !== null) {
            throw new RuntimeException("Category {$entry['entity_id']} changed after planning.");
        }
        $this->transitionEntity('category', $category->id, 'product_categories', $this->jsonArray($category->seo_meta), $entry['generated']);
        $changed['categories']++;
    }

    /** @param array<string, mixed> $additionalUpdates */
    private function transitionEntity(
        string $type,
        int $id,
        string $table,
        array $existing,
        array $generated,
        array $additionalUpdates = [],
    ): void {
        $now = now();
        $metadata = array_merge($existing, [
            'source' => self::GENERATED_SOURCE,
            'previous_source' => self::SOURCE_CODE,
            'review_status' => 'approved',
            'title' => $generated['title'],
            'description' => $generated['description'],
            'canonical_url' => $generated['canonical'],
            'robots' => $generated['robots'],
            'robots_reason' => $generated['robots_reason'],
            'generated_title' => $generated['title'],
            'generated_description' => $generated['description'],
            'generated_canonical_url' => $generated['canonical'],
            'generated_robots' => $generated['robots'],
            'template_version' => $generated['template_version'],
            'active_source' => 'generated',
            'source_notes' => $generated['source_notes'],
            'confidence_level' => $generated['confidence_level'],
            'last_updated' => $now->toIso8601String(),
            'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
        ]);
        $updated = DB::table($table)->where('id', $id)->update(array_merge($additionalUpdates, [
            'seo_meta' => $this->json($metadata),
            'updated_at' => $now,
        ]));
        if ($updated !== 1) {
            throw new RuntimeException("The locked {$type} {$id} could not be transitioned exactly once.");
        }
        $this->templates->recordVersion($type, $id, $generated + ['active_source' => 'generated'], 'generated_transition', null, $this->globalMarketplace()->id, 'en');
    }

    private function sourceProducts()
    {
        $query = DB::table('products as p')
            ->join('catalog_product_sources as cps', 'cps.product_id', '=', 'p.id')
            ->join('catalog_sources as cs', 'cs.id', '=', 'cps.source_id')
            ->where('cs.code', self::SOURCE_CODE)
            ->where('cps.review_status', 'approved');
        $this->publicationGate->apply($query, 'p');

        return $query;
    }

    /** @param list<int> $leafIds @return list<int> */
    private function withAncestorCategories(array $leafIds): array
    {
        $parents = ProductCategory::query()->pluck('parent_id', 'id');
        $ids = [];
        foreach ($leafIds as $leafId) {
            $current = $leafId;
            $guard = 0;
            while ($current && $guard++ < 100) {
                $ids[(int) $current] = true;
                $current = (int) ($parents[$current] ?? 0);
            }
        }
        $result = array_keys($ids);
        sort($result, SORT_NUMERIC);

        return $result;
    }

    private function brandSkipReason(ProductBrand $brand): ?string
    {
        if (! $brand->is_active || ! ($brand->landing_page_enabled ?? true)) {
            return 'inactive_or_landing_disabled';
        }
        if ($this->hasExplicitValue([$brand->seo_title, $brand->seo_description, $brand->canonical_url])
            && ! $this->isLegacyGeneratedBrandSeo($brand)) {
            return 'explicit_override';
        }

        return $this->importerMetadataSkipReason($this->jsonArray($brand->seo_meta));
    }

    private function categorySkipReason(ProductCategory $category): ?string
    {
        if (! $category->is_active) {
            return 'inactive';
        }
        $metadata = $this->jsonArray($category->seo_meta);
        if ($this->hasExplicitValue([$metadata['title'] ?? null, $metadata['description'] ?? null, $metadata['canonical_url'] ?? null])
            && ! $this->isLegacyGeneratedCategorySeo($category, $metadata)) {
            return 'explicit_override';
        }

        return $this->importerMetadataSkipReason($metadata);
    }

    private function importerMetadataSkipReason(array $metadata): ?string
    {
        if (strtolower(trim((string) ($metadata['source'] ?? ''))) !== self::SOURCE_CODE) {
            return 'not_importer_owned';
        }
        if ($this->truthy($metadata['manual_override'] ?? false)
            || $this->truthy($metadata['locked'] ?? false)
            || strtolower((string) ($metadata['active_source'] ?? '')) === 'manual'
            || trim((string) ($metadata['saved_via'] ?? '')) !== '') {
            return 'manual_or_locked';
        }
        if (($metadata['review_status'] ?? null) !== 'pending_review'
            || strtolower(str_replace(' ', '', (string) ($metadata['robots'] ?? ''))) !== 'noindex,nofollow') {
            return 'importer_state_changed';
        }

        return null;
    }

    private function isLegacyGeneratedBrandSeo(ProductBrand $brand): bool
    {
        $description = trim((string) $brand->seo_description);
        if (preg_match(
            '/^Genuine (.+) components on NeoGiga — datasheets, regional stock, pricing and RFQ across our global marketplace\.$/u',
            $description,
            $matches,
        ) !== 1) {
            return false;
        }
        $legacyIdentity = trim((string) $matches[1]);

        return trim((string) $brand->canonical_url) === ''
            && $legacyIdentity !== ''
            && trim((string) $brand->seo_title) === Str::limit("{$legacyIdentity} Products & Distributor — NeoGiga", 60, '');
    }

    /** @param array<string, mixed> $metadata */
    private function isLegacyGeneratedCategorySeo(ProductCategory $category, array $metadata): bool
    {
        $name = trim((string) $category->name);

        return trim((string) ($metadata['canonical_url'] ?? '')) === ''
            && trim((string) ($metadata['title'] ?? '')) === "{$name} — NeoGiga"
            && trim((string) ($metadata['description'] ?? '')) === "Shop {$name} on NeoGiga — genuine parts, regional stock, datasheets and engineering support.";
    }

    private function stableGenerated(array $generated): array
    {
        return collect($generated)->only([
            'title', 'description', 'canonical', 'robots', 'robots_reason', 'template_version',
            'source_notes', 'confidence_level', 'advisory_disclaimer',
        ])->all();
    }

    private function productFingerprint(Product $product): string
    {
        return hash('sha256', $this->json(collect($product->getAttributes())->only([
            'id', 'name', 'slug', 'mpn', 'category_id', 'status', 'approval_status',
            'visibility_status', 'short_description', 'description', 'updated_at',
        ])->all()));
    }

    private function brandFingerprint(ProductBrand $brand): string
    {
        return hash('sha256', $this->json([
            'id' => $brand->id,
            'seo_meta' => $this->jsonArray($brand->seo_meta),
            'seo_title' => $brand->seo_title,
            'seo_description' => $brand->seo_description,
            'canonical_url' => $brand->canonical_url,
            'is_active' => $brand->is_active,
            'landing_page_enabled' => $brand->landing_page_enabled,
            'updated_at' => (string) $brand->updated_at,
        ]));
    }

    private function categoryFingerprint(ProductCategory $category): string
    {
        return hash('sha256', $this->json([
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'seo_meta' => $this->jsonArray($category->seo_meta),
            'is_active' => $category->is_active,
            'updated_at' => (string) $category->updated_at,
        ]));
    }

    private function globalMarketplace(): Marketplace
    {
        return $this->resolvedGlobalMarketplace ??= Marketplace::with('country')->whereRaw('UPPER(code) = ?', ['GLOBAL'])->first()
            ?? throw new RuntimeException('The GLOBAL marketplace required for canonical SEO generation is missing.');
    }

    private function increment(array &$counts, string $reason): void
    {
        $counts[$reason] = ($counts[$reason] ?? 0) + 1;
    }

    private function hasExplicitValue(array $values): bool
    {
        return collect($values)->contains(fn ($value) => trim((string) $value) !== '');
    }

    private function truthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true);
    }

    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function assertSchema(): void
    {
        $required = [
            'products' => ['id', 'name', 'slug', 'brand_id', 'category_id', 'status', 'approval_status', 'visibility_status'],
            'product_seo_meta' => ['product_id', 'meta_title', 'generated_title', 'source_notes', 'last_updated', 'advisory_disclaimer'],
            'product_brands' => ['id', 'seo_meta', 'seo_title', 'seo_description', 'canonical_url', 'is_active', 'landing_page_enabled'],
            'product_categories' => ['id', 'parent_id', 'seo_meta', 'is_active'],
            'catalog_sources' => ['id', 'code'],
            'catalog_product_sources' => ['product_id', 'source_id', 'review_status'],
            'marketplaces' => ['id', 'code'],
            'catalog_seo_versions' => ['entity_type', 'entity_id', 'version'],
        ];
        foreach ($required as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required SEO-governance table {$table} is missing.");
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException("Required SEO-governance column {$table}.{$column} is missing.");
                }
            }
        }
    }
}
