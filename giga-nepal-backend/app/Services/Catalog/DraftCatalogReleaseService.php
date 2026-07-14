<?php

namespace App\Services\Catalog;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DraftCatalogReleaseService
{
    private const PLAN_VERSION = 'elecforest-catalog-release-v1';

    private const ADVISORY = 'Advisory only. Verify specifications, compatibility, safety, availability and commercial terms before use or purchase.';

    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];

    /** @var array<string, array<string, true>> */
    private array $columnCache = [];

    /**
     * Build a read-only, deterministic release plan. No report or database row
     * is written until apply() receives every explicit authorization gate.
     *
     * @return array<string, mixed>
     */
    public function plan(): array
    {
        $this->assertConfiguration();
        $this->assertSchema();

        $source = DB::table('catalog_sources')
            ->where('code', (string) config('catalog_release.source.code'))
            ->first();
        if (! $source) {
            throw new RuntimeException('Configured ElecForest catalog source was not found.');
        }

        $marketplace = DB::table('marketplaces as m')
            ->join('currencies as c', 'c.id', '=', 'm.currency_id')
            ->where('m.code', (string) config('catalog_release.marketplace.code'))
            ->select(['m.*', 'c.code as currency_code'])
            ->first();
        if (! $marketplace || ! (bool) $marketplace->is_active) {
            throw new RuntimeException('The configured GLOBAL marketplace is missing or inactive.');
        }
        if (strtoupper((string) $marketplace->currency_code) !== strtoupper((string) config('catalog_release.marketplace.currency'))) {
            throw new RuntimeException('The GLOBAL marketplace currency does not match the configured USD release currency.');
        }

        $warehouses = $this->releaseWarehouses();
        $latestOffers = DB::table('supplier_product_offers')
            ->selectRaw('supplier_product_id, MAX(id) AS offer_id')
            ->groupBy('supplier_product_id');

        $rows = DB::table('supplier_products as sp')
            ->join('products as p', 'p.id', '=', 'sp.product_id')
            ->leftJoinSub($latestOffers, 'latest_offer', 'latest_offer.supplier_product_id', '=', 'sp.id')
            ->leftJoin('supplier_product_offers as offer', 'offer.id', '=', 'latest_offer.offer_id')
            ->where('sp.catalog_source_id', $source->id)
            ->where('p.status', 'draft')
            ->orderBy('p.id')
            ->select([
                'p.id as product_id', 'p.name', 'p.slug', 'p.sku', 'p.category_id', 'p.short_description', 'p.description',
                'p.status', 'p.visibility_status', 'p.base_price', 'p.cost_price', 'p.sale_price', 'p.stock_quantity',
                'p.metadata as product_metadata', 'p.source_name as product_source_name', 'p.source_url as product_source_url',
                'p.source_file as product_source_file', 'p.source_page_url as product_source_page_url',
                'p.downloaded_at as product_downloaded_at', 'p.imported_at as product_imported_at',
                'p.data_year as product_data_year', 'p.license_note as product_license_note',
                'p.confidence_level as product_confidence_level', 'p.original_raw_value as product_original_raw_value',
                'p.normalized_value as product_normalized_value', 'p.updated_at as product_updated_at',
                'sp.id as supplier_product_id', 'sp.source_product_id', 'sp.source_name as supplier_source_name',
                'sp.source_url as supplier_source_url', 'sp.source_currency', 'sp.source_price as supplier_source_price',
                'sp.raw_payload_json', 'sp.content_hash', 'sp.imported_at as supplier_imported_at',
                'sp.review_status as supplier_review_status', 'sp.updated_at as supplier_updated_at',
                'offer.id as offer_id', 'offer.source_price as offer_source_price', 'offer.currency as offer_currency',
                'offer.observed_at as offer_observed_at', 'offer.source_url as offer_source_url',
                'offer.raw_value as offer_raw_value', 'offer.updated_at as offer_updated_at',
            ])
            ->get();

        $grouped = $rows->groupBy('product_id');
        $productIds = $grouped->keys()->map(static fn ($id): int => (int) $id)->values()->all();
        $assets = $this->assetsByProductAndChecksum((int) $source->id, $productIds);
        $images = $this->imagesByProduct($productIds);
        $prices = $productIds === []
            ? collect()
            : DB::table('marketplace_product_prices')
                ->whereIn('product_id', $productIds)
                ->where('marketplace_id', $marketplace->id)
                ->get()
                ->groupBy('product_id');
        $stocks = $productIds === []
            ? collect()
            : DB::table('inventory_stocks')->whereIn('product_id', $productIds)->get()->groupBy('product_id');

        $eligible = [];
        $quarantined = [];
        $blocked = [];
        $verifiedMedia = 0;
        $rejectedMedia = 0;
        $quarantineSkus = array_map('strval', (array) config('catalog_release.quarantine_skus', []));

        foreach ($grouped as $productId => $sourceRows) {
            $row = $sourceRows->first();
            if (in_array((string) $row->sku, $quarantineSkus, true)) {
                $quarantined[] = $this->quarantineSnapshot($row);

                continue;
            }

            $reasons = [];
            if ($sourceRows->count() !== 1) {
                $reasons[] = 'Expected exactly one ElecForest supplier product link.';
            }
            $this->validateProductSnapshot($row, $reasons);
            $pricing = $this->pricingSnapshot($row, $reasons);

            $existingPrices = $prices->get($productId, collect());
            if ($existingPrices->isNotEmpty()) {
                $reasons[] = 'A GLOBAL marketplace price already exists; refusing to overwrite it.';
            }
            $existingStocks = $stocks->get($productId, collect());
            if ($existingStocks->isNotEmpty()) {
                $reasons[] = 'Inventory stock already exists; refusing to overwrite or blend it.';
            }
            if (! $this->isZero($row->base_price) || ! $this->isZero($row->cost_price) || ! $this->isZero($row->sale_price)) {
                $reasons[] = 'Canonical product pricing is already populated.';
            }
            if ((int) $row->stock_quantity !== 0) {
                $reasons[] = 'Canonical product stock is already populated.';
            }

            $verifiedImages = [];
            foreach ($images->get($productId, collect())->sortBy(['sort_order', 'id']) as $image) {
                $key = $this->assetKey((int) $productId, (string) $image->checksum);
                $asset = $assets[$key] ?? null;
                if (! $asset) {
                    $rejectedMedia++;

                    continue;
                }
                try {
                    $verifiedImages[] = $this->verifyImage($image, $asset);
                    $verifiedMedia++;
                } catch (Throwable) {
                    $rejectedMedia++;
                }
            }
            if ($verifiedImages === []) {
                $reasons[] = 'No checksum-verified, locally stored real product image is available.';
            }

            if ($reasons !== []) {
                $blocked[] = [
                    'product_id' => (int) $productId,
                    'sku' => (string) $row->sku,
                    'reasons' => array_values(array_unique($reasons)),
                ];

                continue;
            }

            $eligible[] = [
                'product_id' => (int) $productId,
                'sku' => (string) $row->sku,
                'name' => (string) $row->name,
                'supplier_product_id' => (int) $row->supplier_product_id,
                'source_product_id' => (string) $row->source_product_id,
                'offer_id' => (int) $row->offer_id,
                'cost_price' => $pricing['cost_price'],
                'sale_price' => $pricing['sale_price'],
                'source_url' => (string) ($row->offer_source_url ?: $row->supplier_source_url ?: $row->product_source_url),
                'source_page_url' => (string) ($row->product_source_page_url ?: $row->supplier_source_url),
                'source_file' => (string) $row->product_source_file,
                'downloaded_at' => $this->dateString($row->product_downloaded_at),
                'imported_at' => $this->dateString($row->product_imported_at),
                'data_year' => (string) $row->product_data_year,
                'license_note' => (string) $row->product_license_note,
                'confidence_level' => (string) $row->product_confidence_level,
                'original_raw_value' => (string) ($row->offer_raw_value ?: $row->product_original_raw_value),
                'observed_at' => $this->dateString($row->offer_observed_at),
                'product_fingerprint' => $this->hash([
                    'id' => (int) $row->product_id,
                    'name' => (string) $row->name,
                    'slug' => (string) $row->slug,
                    'sku' => (string) $row->sku,
                    'category_id' => (int) $row->category_id,
                    'status' => (string) $row->status,
                    'visibility_status' => (string) $row->visibility_status,
                    'base_price' => (string) $row->base_price,
                    'cost_price' => (string) $row->cost_price,
                    'sale_price' => (string) $row->sale_price,
                    'stock_quantity' => (int) $row->stock_quantity,
                    'updated_at' => $this->dateString($row->product_updated_at),
                ]),
                'supplier_fingerprint' => $this->hash([
                    'id' => (int) $row->supplier_product_id,
                    'product_id' => (int) $row->product_id,
                    'source_price' => (string) $row->supplier_source_price,
                    'source_currency' => (string) $row->source_currency,
                    'content_hash' => (string) $row->content_hash,
                    'updated_at' => $this->dateString($row->supplier_updated_at),
                ]),
                'offer_fingerprint' => $this->hash([
                    'id' => (int) $row->offer_id,
                    'source_price' => (string) $row->offer_source_price,
                    'currency' => (string) $row->offer_currency,
                    'source_url' => (string) $row->offer_source_url,
                    'observed_at' => $this->dateString($row->offer_observed_at),
                    'updated_at' => $this->dateString($row->offer_updated_at),
                ]),
                'images' => array_values($verifiedImages),
            ];
        }

        usort($eligible, static fn (array $a, array $b): int => $a['product_id'] <=> $b['product_id']);
        usort($quarantined, static fn (array $a, array $b): int => $a['product_id'] <=> $b['product_id']);
        usort($blocked, static fn (array $a, array $b): int => $a['product_id'] <=> $b['product_id']);
        $eligibleIds = array_column($eligible, 'product_id');
        $openReviews = $eligibleIds === []
            ? []
            : DB::table('catalog_review_tasks')
                ->whereIn('product_id', $eligibleIds)
                ->where('status', 'open')
                ->selectRaw('task_type, COUNT(*) AS task_count')
                ->groupBy('task_type')
                ->orderBy('task_type')
                ->pluck('task_count', 'task_type')
                ->map(static fn ($count): int => (int) $count)
                ->all();
        $openNonMediaReviews = array_sum(array_filter(
            $openReviews,
            static fn (string $type): bool => $type !== 'media_rights_review',
            ARRAY_FILTER_USE_KEY
        ));

        $deterministic = [
            'version' => self::PLAN_VERSION,
            'source' => ['id' => (int) $source->id, 'code' => (string) $source->code],
            'marketplace' => ['id' => (int) $marketplace->id, 'code' => (string) $marketplace->code, 'currency' => (string) $marketplace->currency_code],
            'margin_percent' => (int) config('catalog_release.margin_percent'),
            'inventory' => $this->inventoryPlan($warehouses),
            'quarantine_skus' => $quarantineSkus,
            'open_review_tasks_by_type' => $openReviews,
            'eligible' => $eligible,
            'quarantined' => $quarantined,
            'blocked' => $blocked,
        ];
        $planHash = $this->hash($deterministic);
        $totalUnits = (int) config('catalog_release.inventory.total_units');

        return [
            'version' => self::PLAN_VERSION,
            'mode' => 'dry-run',
            'generated_at' => now()->toIso8601String(),
            'plan_hash' => $planHash,
            'source' => $deterministic['source'],
            'marketplace' => $deterministic['marketplace'],
            'margin_percent' => $deterministic['margin_percent'],
            'inventory' => $deterministic['inventory'],
            'summary' => [
                'draft_products_found' => $grouped->count(),
                'eligible_products' => count($eligible),
                'quarantined_products' => count($quarantined),
                'blocked_products' => count($blocked),
                'verified_real_images' => $verifiedMedia,
                'rejected_or_unmatched_images' => $rejectedMedia,
                'open_review_tasks' => array_sum($openReviews),
                'open_non_media_review_tasks' => $openNonMediaReviews,
                'marketplace_price_rows_to_create' => count($eligible),
                'inventory_stock_rows_to_create' => count($eligible) * count($warehouses),
                'inventory_movement_rows_to_create' => count($eligible) * count($warehouses),
                'total_units_to_allocate' => count($eligible) * $totalUnits,
            ],
            'quarantined' => $quarantined,
            'blocked' => $blocked,
            'open_review_tasks_by_type' => $openReviews,
            'source_notes' => 'Read-only plan for operator-directed publication of checksum-verified ElecForest drafts. File integrity is verified; media licensing is not independently verified and review tasks remain open. Existing prices and inventory are never overwritten.',
            'confidence_level' => $blocked !== [] ? 'blocked_requires_review' : ($openReviews !== [] ? 'preflight_verified_with_open_reviews' : 'high_preflight_verified'),
            'last_updated' => now()->toIso8601String(),
            'advisory_disclaimer' => self::ADVISORY,
            '_deterministic' => $deterministic,
        ];
    }

    /** @return array<string, mixed> */
    public function forOutput(array $plan): array
    {
        unset($plan['_deterministic']);
        if (count($plan['blocked'] ?? []) > 50) {
            $plan['blocked'] = array_slice($plan['blocked'], 0, 50);
            $plan['blocked_note'] = 'Only the first 50 blocked products are shown; no apply is allowed while any product is blocked.';
        }

        return $plan;
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  array{expected_count:int,expected_plan_hash:string,backup_reference:string,acknowledge_media_publication_risk:bool,chunk_size?:int}  $authorization
     * @return array<string, mixed>
     */
    public function apply(array $plan, array $authorization): array
    {
        $this->assertApplyAuthorization($plan, $authorization);
        $items = (array) ($plan['_deterministic']['eligible'] ?? []);
        $quarantined = (array) ($plan['_deterministic']['quarantined'] ?? []);
        $chunkSize = max(1, min(500, (int) ($authorization['chunk_size'] ?? config('catalog_release.chunk_size', 100))));
        $result = [
            'status' => 'applying',
            'plan_hash' => (string) $plan['plan_hash'],
            'source' => $plan['_deterministic']['source'],
            'marketplace' => $plan['_deterministic']['marketplace'],
            'margin_percent' => $plan['margin_percent'],
            'inventory' => $plan['_deterministic']['inventory'],
            'open_review_tasks_by_type' => $plan['_deterministic']['open_review_tasks_by_type'],
            'operator_acknowledged_media_publication_risk' => true,
            'media_license_independently_verified' => false,
            'eligible_products' => count($items),
            'released_products' => 0,
            'already_released_products' => 0,
            'quarantined_products' => 0,
            'price_rows_created' => 0,
            'stock_rows_created' => 0,
            'movement_rows_created' => 0,
            'images_activated' => 0,
            'backup_reference' => trim((string) $authorization['backup_reference']),
            'started_at' => now()->toIso8601String(),
        ];

        try {
            foreach (array_chunk($quarantined, $chunkSize) as $chunk) {
                foreach ($chunk as $item) {
                    $result['quarantined_products'] += $this->applyQuarantine($item, $plan, $authorization);
                }
            }

            foreach (array_chunk($items, $chunkSize) as $chunk) {
                foreach ($chunk as $item) {
                    if ($this->isAlreadyReleased($item, (string) $plan['plan_hash'])) {
                        $result['already_released_products']++;

                        continue;
                    }
                    $verified = $this->reverifyPlannedImages($item);
                    $applied = $this->applyProduct($item, $verified, $plan, $authorization);
                    foreach ($applied as $key => $value) {
                        $result[$key] += $value;
                    }
                }
            }

            Cache::forever('seo:sitemap-version', (string) now()->getTimestampMs());
            $result['status'] = 'completed';
            $result['completed_at'] = now()->toIso8601String();
            $result['source_notes'] = 'Operator-directed, transactional catalog release. Price, stock and image-file integrity were revalidated; media licensing was not independently verified and all review tasks remain visible/open.';
            $result['confidence_level'] = ($plan['_deterministic']['open_review_tasks_by_type'] ?? []) !== []
                ? 'transactionally_verified_with_open_reviews_and_unverified_media_license'
                : 'high_transactionally_verified';
            $result['last_updated'] = $result['completed_at'];
            $result['advisory_disclaimer'] = self::ADVISORY;
            $result['report_path'] = $this->writeReport($result);

            return $result;
        } catch (Throwable $exception) {
            $result['status'] = 'failed';
            $result['failed_at'] = now()->toIso8601String();
            $result['error'] = $exception->getMessage();
            $result['source_notes'] = 'Release stopped at the first conflict. Previously completed product transactions remain append-only and idempotently marked for a safe resumed dry run.';
            $result['confidence_level'] = 'partial_release_requires_new_dry_run';
            $result['last_updated'] = $result['failed_at'];
            $result['advisory_disclaimer'] = self::ADVISORY;
            try {
                $result['report_path'] = $this->writeReport($result);
            } catch (Throwable $reportException) {
                $result['report_error'] = $reportException->getMessage();
            }

            throw new RuntimeException(
                'Catalog release stopped safely: '.$exception->getMessage().'. Completed product transactions, if any, are recorded in catalog_change_events and audit_logs.',
                0,
                $exception
            );
        }
    }

    /** @param array<string, mixed> $item */
    private function isAlreadyReleased(array $item, string $planHash): bool
    {
        $product = DB::table('products')->where('id', $item['product_id'])->first(['status', 'metadata']);
        if (! $product || ! in_array((string) $product->status, ['approved', 'published'], true)) {
            return false;
        }
        $metadata = $this->jsonArray($product->metadata);

        return hash_equals($planHash, (string) ($metadata['catalog_release']['plan_hash'] ?? ''));
    }

    /** @param array<string, mixed> $plan */
    private function assertApplyAuthorization(array $plan, array $authorization): void
    {
        if (($plan['version'] ?? null) !== self::PLAN_VERSION || ! isset($plan['_deterministic'])) {
            throw new RuntimeException('A complete catalog release plan is required.');
        }
        $actualHash = $this->hash((array) $plan['_deterministic']);
        if (! hash_equals((string) ($plan['plan_hash'] ?? ''), $actualHash)) {
            throw new RuntimeException('The release plan payload was modified after preflight.');
        }
        if (! hash_equals((string) ($authorization['expected_plan_hash'] ?? ''), $actualHash)) {
            throw new RuntimeException('The expected plan hash does not match the current dry run.');
        }
        $eligible = (int) ($plan['summary']['eligible_products'] ?? -1);
        if ((int) ($authorization['expected_count'] ?? -1) !== $eligible) {
            throw new RuntimeException('The expected product count does not match the current dry run.');
        }
        if ($eligible < 1) {
            throw new RuntimeException('There are no eligible draft products to release.');
        }
        if ((int) ($plan['summary']['blocked_products'] ?? 0) !== 0) {
            throw new RuntimeException('Blocked products remain; apply is disabled until a clean dry run is produced.');
        }
        if (trim((string) ($authorization['backup_reference'] ?? '')) === '') {
            throw new RuntimeException('A verified backup reference is required before apply.');
        }
        if (($authorization['acknowledge_media_publication_risk'] ?? false) !== true) {
            throw new RuntimeException('Explicit acknowledgement of unverified media-license publication risk is required before real images can be activated.');
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<array<string, mixed>>  $verifiedImages
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $authorization
     * @return array<string, int>
     */
    private function applyProduct(array $item, array $verifiedImages, array $plan, array $authorization): array
    {
        return DB::transaction(function () use ($item, $verifiedImages, $plan, $authorization): array {
            $product = DB::table('products')->where('id', $item['product_id'])->lockForUpdate()->first();
            if (! $product) {
                throw new RuntimeException("Product {$item['product_id']} disappeared after preflight.");
            }
            $metadata = $this->jsonArray($product->metadata);
            if (($metadata['catalog_release']['plan_hash'] ?? null) === $plan['plan_hash']
                && in_array((string) $product->status, ['approved', 'published'], true)) {
                return $this->zeroResult(['already_released_products' => 1]);
            }
            if ((string) $product->status !== 'draft') {
                throw new RuntimeException("Product {$item['product_id']} is no longer a draft.");
            }
            $this->assertProductFingerprint($product, $item);

            $supplier = DB::table('supplier_products')->where('id', $item['supplier_product_id'])->lockForUpdate()->first();
            $offer = DB::table('supplier_product_offers')->where('id', $item['offer_id'])->lockForUpdate()->first();
            if (! $supplier || ! $offer || (int) $supplier->product_id !== (int) $product->id || (int) $offer->supplier_product_id !== (int) $supplier->id) {
                throw new RuntimeException("Product {$item['product_id']} source linkage changed after preflight.");
            }
            if (! hash_equals((string) $item['supplier_fingerprint'], $this->hash([
                'id' => (int) $supplier->id,
                'product_id' => (int) $supplier->product_id,
                'source_price' => (string) $supplier->source_price,
                'source_currency' => (string) $supplier->source_currency,
                'content_hash' => (string) $supplier->content_hash,
                'updated_at' => $this->dateString($supplier->updated_at),
            ])) || ! hash_equals((string) $item['offer_fingerprint'], $this->hash([
                'id' => (int) $offer->id,
                'source_price' => (string) $offer->source_price,
                'currency' => (string) $offer->currency,
                'source_url' => (string) $offer->source_url,
                'observed_at' => $this->dateString($offer->observed_at),
                'updated_at' => $this->dateString($offer->updated_at),
            ]))) {
                throw new RuntimeException("Product {$item['product_id']} source price changed after preflight.");
            }

            $marketplaceId = (int) $plan['_deterministic']['marketplace']['id'];
            if (DB::table('marketplace_product_prices')->where('product_id', $product->id)->where('marketplace_id', $marketplaceId)->lockForUpdate()->exists()) {
                throw new RuntimeException("Product {$item['product_id']} acquired a GLOBAL price after preflight.");
            }
            if (DB::table('inventory_stocks')->where('product_id', $product->id)->lockForUpdate()->exists()) {
                throw new RuntimeException("Product {$item['product_id']} acquired inventory after preflight.");
            }

            $now = now();
            $normalizedPrice = [
                'cost_price' => $item['cost_price'],
                'sale_price' => $item['sale_price'],
                'margin_percent' => (int) $plan['margin_percent'],
                'currency' => 'USD',
                'plan_hash' => $plan['plan_hash'],
            ];
            $priceId = DB::table('marketplace_product_prices')->insertGetId([
                'product_id' => $product->id,
                'product_variant_id' => null,
                'marketplace_id' => $marketplaceId,
                'base_price' => $item['sale_price'],
                'sale_price' => $item['sale_price'],
                'cost_price' => $item['cost_price'],
                'currency_code' => 'USD',
                'is_tax_inclusive' => false,
                'tax_rate' => null,
                'sale_start_date' => null,
                'sale_end_date' => null,
                'is_active' => true,
                'supplier_product_offer_id' => $offer->id,
                'source_name' => (string) config('catalog_release.source.name'),
                'source_url' => $item['source_url'],
                'source_file' => $item['source_file'],
                'source_page_url' => $item['source_page_url'],
                'downloaded_at' => $item['downloaded_at'],
                'imported_at' => $now,
                'data_year' => $item['data_year'],
                'license_note' => $item['license_note'],
                'confidence_level' => 'source_price_verified_with_open_catalog_reviews',
                'original_raw_value' => $item['original_raw_value'],
                'normalized_value' => $this->json($normalizedPrice),
                'pricing_rule' => 'source_cost_plus_5_percent_exact',
                'source_review_status' => 'operator_directed_publication_open_reviews',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('regional_price_history')->insert([
                'marketplace_product_price_id' => $priceId,
                'product_id' => $product->id,
                'marketplace_id' => $marketplaceId,
                'old_base_price' => null,
                'new_base_price' => $item['sale_price'],
                'old_sale_price' => null,
                'new_sale_price' => $item['sale_price'],
                'currency_code' => 'USD',
                'changed_by' => null,
                'reason' => 'Governed ElecForest catalog release: source cost + 5%.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $stockRows = $movementRows = 0;
            foreach ($plan['_deterministic']['inventory']['warehouses'] as $allocation) {
                $warehouse = DB::table('warehouses')->where('id', $allocation['id'])->lockForUpdate()->first();
                if (! $warehouse || ! (bool) $warehouse->is_active || (string) $warehouse->code !== (string) $allocation['code']) {
                    throw new RuntimeException("Release warehouse {$allocation['code']} changed after preflight.");
                }
                $inventoryProvenance = $this->inventoryProvenance($item, $allocation, $plan, $authorization, $now);
                $stockData = [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'warehouse_id' => $warehouse->id,
                    'vendor_id' => null,
                    'marketplace_id' => $warehouse->marketplace_id ?: $marketplaceId,
                    'country_id' => $warehouse->country_id,
                    'region_id' => $warehouse->region_id,
                    'city_id' => $warehouse->city_id,
                    'sku' => $product->sku,
                    'quantity_available' => $allocation['units'],
                    'quantity_on_hand' => $allocation['units'],
                    'quantity_reserved' => 0,
                    'quantity_damaged' => 0,
                    'quantity_incoming' => 0,
                    'reorder_point' => 10,
                    'reorder_quantity' => 0,
                    'backorder_allowed' => false,
                    'quote_only' => false,
                    'status' => 'active',
                    'is_active' => true,
                    'unit_cost' => $item['cost_price'],
                    'last_movement_at' => $now,
                    'metadata' => $this->json($inventoryProvenance),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $stockId = DB::table('inventory_stocks')->insertGetId($this->existingColumns('inventory_stocks', $stockData));
                $idempotencyKey = $this->movementKey((string) $plan['plan_hash'], (int) $product->id, (int) $warehouse->id);
                if (DB::table('inventory_movements')->where('idempotency_key', $idempotencyKey)->exists()) {
                    throw new RuntimeException("Inventory movement {$idempotencyKey} already exists without a completed product marker.");
                }
                $movementData = [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'warehouse_id' => $warehouse->id,
                    'vendor_id' => null,
                    'inventory_stock_id' => $stockId,
                    'marketplace_id' => $warehouse->marketplace_id ?: $marketplaceId,
                    'movement_type' => 'adjustment',
                    'quantity_change' => $allocation['units'],
                    'quantity_before' => 0,
                    'quantity_after' => $allocation['units'],
                    'unit_cost' => $item['cost_price'],
                    'reference_id' => $offer->id,
                    'reference_type' => 'supplier_product_offer',
                    'notes' => 'Initial governed catalog release allocation.',
                    'user_id' => null,
                    'metadata' => $this->json($inventoryProvenance),
                    'idempotency_key' => $idempotencyKey,
                    'occurred_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                DB::table('inventory_movements')->insert($this->existingColumns('inventory_movements', $movementData));
                $stockRows++;
                $movementRows++;
            }

            $imageIds = array_map(static fn (array $image): int => (int) $image['id'], $verifiedImages);
            $primaryId = min($imageIds);
            foreach ($verifiedImages as $verified) {
                $image = DB::table('product_images')->where('id', $verified['id'])->where('product_id', $product->id)->lockForUpdate()->first();
                if (! $image || ! hash_equals((string) $verified['fingerprint'], $this->imageFingerprint($image, (int) $verified['asset_id']))) {
                    throw new RuntimeException("Product {$product->id} media metadata changed after file verification.");
                }
                $imageMetadata = $this->jsonArray($image->metadata);
                $imageMetadata['catalog_release'] = [
                    'plan_hash' => $plan['plan_hash'],
                    'backup_reference' => $authorization['backup_reference'],
                    'operator_acknowledged_publication_risk' => true,
                    'license_independently_verified' => false,
                    'original_source_license' => $image->source_license,
                    'original_copyright' => $image->copyright,
                    'original_license_note' => $image->license_note,
                    'original_asset_rights_status' => DB::table('supplier_product_assets')->where('id', $verified['asset_id'])->value('rights_status'),
                    'source_notes' => (string) config('catalog_release.media.approval_note'),
                    'confidence_level' => 'checksum_and_image_signature_verified_license_unverified',
                    'last_updated' => $now->toIso8601String(),
                    'advisory_disclaimer' => self::ADVISORY,
                ];
                DB::table('product_images')->where('id', $image->id)->update([
                    'is_active' => true,
                    'is_primary' => (int) $image->id === $primaryId,
                    'storage_disk' => $verified['disk'],
                    'source_file' => $item['source_file'],
                    'source_page_url' => $item['source_page_url'],
                    'imported_at' => $item['imported_at'] ?: $now,
                    'data_year' => is_numeric($item['data_year']) ? (int) $item['data_year'] : (int) $now->format('Y'),
                    'license_note' => $image->license_note ?: (string) config('catalog_release.media.license_note'),
                    'confidence_level' => 'checksum_and_image_signature_verified_license_unverified',
                    'original_raw_value' => $this->json(['original_url' => $image->original_url, 'checksum' => $image->checksum, 'asset_id' => $verified['asset_id']]),
                    'normalized_value' => $this->json(['path' => $image->file_path, 'mime_type' => $verified['mime_type'], 'width' => $verified['width'], 'height' => $verified['height']]),
                    'metadata' => $this->json($imageMetadata),
                    'updated_at' => $now,
                ]);
            }

            $this->recordMediaRiskAcknowledgement((int) $product->id, (int) $supplier->id, (string) $plan['plan_hash'], $authorization, $now);
            DB::table('product_seo_meta')
                ->where('product_id', $product->id)
                ->where(function ($query): void {
                    $query->whereNull('is_manual_override')->orWhere('is_manual_override', false);
                })
                ->where(function ($query): void {
                    $query->whereNull('is_locked')->orWhere('is_locked', false);
                })
                ->where(function ($query): void {
                    $query->whereNull('active_source')->orWhere('active_source', '!=', 'manual');
                })
                ->update([
                    'robots' => 'index,follow',
                    'generated_robots' => 'index,follow',
                    'robots_reason' => 'Operator-released canonical product with verified media-file integrity, price and inventory; catalog reviews remain visible.',
                    'updated_at' => $now,
                ]);

            $before = [
                'status' => $product->status,
                'visibility_status' => $product->visibility_status,
                'base_price' => $product->base_price,
                'cost_price' => $product->cost_price,
                'sale_price' => $product->sale_price,
                'stock_quantity' => $product->stock_quantity,
            ];
            $after = [
                'status' => 'approved',
                'visibility_status' => 'public',
                'base_price' => $item['sale_price'],
                'cost_price' => $item['cost_price'],
                'sale_price' => $item['sale_price'],
                'stock_quantity' => (int) config('catalog_release.inventory.total_units'),
                'plan_hash' => $plan['plan_hash'],
                'open_review_tasks_by_type' => $plan['_deterministic']['open_review_tasks_by_type'],
                'media_license_independently_verified' => false,
            ];
            DB::table('catalog_change_events')->insert([
                'catalog_source_id' => $plan['_deterministic']['source']['id'],
                'supplier_product_id' => $supplier->id,
                'catalog_import_run_id' => null,
                'event_type' => 'catalog_release_available',
                'before_json' => $this->json($before),
                'after_json' => $this->json($after),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('audit_logs')->insert([
                'user_id' => null,
                'action' => 'catalog.release.available',
                'model_type' => 'App\\Models\\Marketplace\\Product',
                'model_id' => $product->id,
                'model_display_name' => $product->name,
                'old_values' => $this->json($before),
                'new_values' => $this->json($after + ['backup_reference' => $authorization['backup_reference']]),
                'ip_address' => null,
                'user_agent' => 'artisan:catalog:release-drafts',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $metadata['catalog_release'] = [
                'version' => self::PLAN_VERSION,
                'plan_hash' => $plan['plan_hash'],
                'released_at' => $now->toIso8601String(),
                'backup_reference' => $authorization['backup_reference'],
                'source_notes' => 'Operator-directed release using verified supplier cost, verified real-media file integrity and fixed warehouse allocation. Media licensing was not independently verified.',
                'open_review_tasks_by_type' => $plan['_deterministic']['open_review_tasks_by_type'],
                'license_independently_verified' => false,
                'confidence_level' => 'transactionally_verified_with_open_reviews_and_unverified_media_license',
                'last_updated' => $now->toIso8601String(),
                'advisory_disclaimer' => self::ADVISORY,
            ];
            $productUpdate = [
                'base_price' => $item['sale_price'],
                'cost_price' => $item['cost_price'],
                'sale_price' => $item['sale_price'],
                'track_inventory' => true,
                'stock_quantity' => (int) config('catalog_release.inventory.total_units'),
                'visibility_status' => 'public',
                'approval_status' => 'approved',
                'approved_at' => $product->approved_at ?: $now,
                'last_verified_at' => $now,
                'metadata' => $this->json($metadata),
                // Publication is deliberately last so no partially provisioned
                // product can become visible inside this transaction.
                'status' => 'approved',
                'updated_at' => $now,
            ];
            DB::table('products')->where('id', $product->id)->update($this->existingColumns('products', $productUpdate));

            return $this->zeroResult([
                'released_products' => 1,
                'price_rows_created' => 1,
                'stock_rows_created' => $stockRows,
                'movement_rows_created' => $movementRows,
                'images_activated' => count($verifiedImages),
            ]);
        }, 3);
    }

    /** @param array<string, mixed> $item @param array<string, mixed> $plan @param array<string, mixed> $authorization */
    private function applyQuarantine(array $item, array $plan, array $authorization): int
    {
        return DB::transaction(function () use ($item, $plan, $authorization): int {
            $product = DB::table('products')->where('id', $item['product_id'])->lockForUpdate()->first();
            if (! $product || (string) $product->sku !== (string) $item['sku']) {
                throw new RuntimeException("Quarantine product {$item['product_id']} changed after preflight.");
            }
            $metadata = $this->jsonArray($product->metadata);
            if (($metadata['catalog_quarantine']['code'] ?? null) === 'elecforest_template_sentinel') {
                return 0;
            }
            $now = now();
            $metadata['catalog_quarantine'] = [
                'code' => 'elecforest_template_sentinel',
                'reason' => 'Source listing is a template/sentinel rather than a sellable product.',
                'plan_hash' => $plan['plan_hash'],
                'backup_reference' => $authorization['backup_reference'],
                'source_notes' => 'Exact SKU quarantine retained as a hidden draft; no price, stock or media was assigned.',
                'confidence_level' => 'high_source_template_verified',
                'last_updated' => $now->toIso8601String(),
                'advisory_disclaimer' => self::ADVISORY,
            ];
            DB::table('products')->where('id', $product->id)->update($this->existingColumns('products', [
                'status' => 'draft',
                'visibility_status' => 'hidden',
                'track_inventory' => false,
                'stock_quantity' => 0,
                'metadata' => $this->json($metadata),
                'updated_at' => $now,
            ]));
            DB::table('product_seo_meta')
                ->where('product_id', $product->id)
                ->where(function ($query): void {
                    $query->whereNull('is_manual_override')->orWhere('is_manual_override', false);
                })
                ->update([
                    'robots' => 'noindex,nofollow',
                    'generated_robots' => 'noindex,nofollow',
                    'robots_reason' => 'Quarantined source template/sentinel; not a sellable product.',
                    'updated_at' => $now,
                ]);
            DB::table('catalog_change_events')->insert([
                'catalog_source_id' => $plan['_deterministic']['source']['id'],
                'supplier_product_id' => $item['supplier_product_id'],
                'catalog_import_run_id' => null,
                'event_type' => 'catalog_release_quarantine',
                'before_json' => $this->json(['status' => $product->status, 'visibility_status' => $product->visibility_status]),
                'after_json' => $this->json(['status' => 'draft', 'visibility_status' => 'hidden', 'reason' => 'elecforest_template_sentinel']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return 1;
        }, 3);
    }

    /** @param array<string, mixed> $item @return list<array<string, mixed>> */
    private function reverifyPlannedImages(array $item): array
    {
        $verified = [];
        foreach ((array) $item['images'] as $planned) {
            $image = DB::table('product_images')->where('id', $planned['id'])->where('product_id', $item['product_id'])->first();
            $asset = DB::table('supplier_product_assets')->where('id', $planned['asset_id'])->first();
            if (! $image || ! $asset) {
                throw new RuntimeException("Product {$item['product_id']} media disappeared after preflight.");
            }
            $current = $this->verifyImage($image, $asset);
            if (! hash_equals((string) $planned['fingerprint'], (string) $current['fingerprint'])) {
                throw new RuntimeException("Product {$item['product_id']} media changed after preflight.");
            }
            $verified[] = $current;
        }
        if ($verified === []) {
            throw new RuntimeException("Product {$item['product_id']} has no verified real media at apply time.");
        }

        return $verified;
    }

    /** @param object $image @param object $asset @return array<string, mixed> */
    private function verifyImage(object $image, object $asset): array
    {
        if ((int) $asset->supplier_product_id < 1 || (string) $asset->download_status !== 'downloaded') {
            throw new RuntimeException('Supplier image asset is not a completed download.');
        }
        $checksum = strtolower(trim((string) $image->checksum));
        if (preg_match('/^[a-f0-9]{64}$/', $checksum) !== 1 || ! hash_equals($checksum, strtolower((string) $asset->checksum))) {
            throw new RuntimeException('Image checksum metadata is invalid or does not match its supplier asset.');
        }
        $url = (string) ($image->original_url ?: $image->source_url ?: $asset->original_url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            throw new RuntimeException('Image does not retain a valid HTTPS source URL.');
        }
        if (strtolower(trim((string) $image->source_name)) !== 'elecforest') {
            throw new RuntimeException('Image source is not ElecForest.');
        }
        $diskName = trim((string) ($image->storage_disk ?: config('elecforest_import.image_disk', 'public')));
        $disk = Storage::disk($diskName);
        $path = (string) $image->file_path;
        if ($path === '' || ! $disk->exists($path)) {
            throw new RuntimeException("Stored image {$path} does not exist.");
        }
        [$actualChecksum, $bytes, $mime, $width, $height] = $this->inspectStoredImage($disk, $path);
        if (! hash_equals($checksum, $actualChecksum)) {
            throw new RuntimeException("Stored image {$path} failed checksum verification.");
        }
        if ((int) $image->file_size > 0 && (int) $image->file_size !== $bytes) {
            throw new RuntimeException("Stored image {$path} size does not match metadata.");
        }
        if (! in_array($mime, self::IMAGE_MIMES, true) || ($image->mime_type && strtolower((string) $image->mime_type) !== $mime)) {
            throw new RuntimeException("Stored image {$path} MIME type is invalid.");
        }
        if ($width < 1 || $height < 1 || ((int) $image->width > 0 && (int) $image->width !== $width) || ((int) $image->height > 0 && (int) $image->height !== $height)) {
            throw new RuntimeException("Stored image {$path} dimensions are invalid.");
        }

        return [
            'id' => (int) $image->id,
            'asset_id' => (int) $asset->id,
            'disk' => $diskName,
            'path' => $path,
            'checksum' => $checksum,
            'mime_type' => $mime,
            'file_size' => $bytes,
            'width' => $width,
            'height' => $height,
            'fingerprint' => $this->imageFingerprint($image, (int) $asset->id),
        ];
    }

    /** @return array{0:string,1:int,2:string,3:int,4:int} */
    private function inspectStoredImage(FilesystemAdapter $disk, string $path): array
    {
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException("Stored image {$path} could not be opened.");
        }
        $hash = hash_init('sha256');
        $body = '';
        $bytes = 0;
        $max = max(1, (int) config('elecforest_import.max_image_bytes', 10 * 1024 * 1024));
        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 1024 * 1024);
                if ($chunk === false) {
                    throw new RuntimeException("Stored image {$path} could not be read.");
                }
                $bytes += strlen($chunk);
                if ($bytes > $max) {
                    throw new RuntimeException("Stored image {$path} exceeds the configured media limit.");
                }
                hash_update($hash, $chunk);
                $body .= $chunk;
            }
        } finally {
            fclose($stream);
        }
        if ($body === '') {
            throw new RuntimeException("Stored image {$path} is empty.");
        }
        $mime = strtolower((new \finfo(FILEINFO_MIME_TYPE))->buffer($body) ?: '');
        $dimensions = @getimagesizefromstring($body);
        if (! is_array($dimensions)) {
            throw new RuntimeException("Stored image {$path} has no valid image signature.");
        }

        return [hash_final($hash), $bytes, $mime, (int) $dimensions[0], (int) $dimensions[1]];
    }

    /** @param object $row @param list<string> $reasons */
    private function validateProductSnapshot(object $row, array &$reasons): void
    {
        foreach (['name', 'slug', 'sku', 'category_id'] as $field) {
            if (trim((string) ($row->{$field} ?? '')) === '') {
                $reasons[] = "Product {$field} is missing.";
            }
        }
        if (trim(strip_tags((string) ($row->short_description ?: $row->description))) === '') {
            $reasons[] = 'Product description is missing.';
        }
        foreach ([
            'product_source_name', 'product_source_url', 'product_source_file', 'product_source_page_url',
            'product_downloaded_at', 'product_imported_at', 'product_data_year', 'product_license_note',
            'product_confidence_level', 'product_original_raw_value', 'product_normalized_value',
        ] as $field) {
            if (trim((string) ($row->{$field} ?? '')) === '') {
                $reasons[] = "Required product provenance {$field} is missing.";
            }
        }
    }

    /** @param object $row @param list<string> $reasons @return array{cost_price:string,sale_price:string} */
    private function pricingSnapshot(object $row, array &$reasons): array
    {
        try {
            $supplierCents = $this->decimalCents($row->supplier_source_price);
            $offerCents = $this->decimalCents($row->offer_source_price);
            if ($supplierCents !== $offerCents) {
                $reasons[] = 'Latest supplier offer price does not match the imported supplier price.';
            }
            if (strtoupper((string) $row->source_currency) !== 'USD' || strtoupper((string) $row->offer_currency) !== 'USD') {
                $reasons[] = 'Supplier price is not a verified USD observation.';
            }
            $saleUnits = $supplierCents * (100 + (int) config('catalog_release.margin_percent'));

            return [
                'cost_price' => $this->formatScaled($supplierCents * 100, 4),
                'sale_price' => $this->formatScaled($saleUnits, 4),
            ];
        } catch (Throwable $exception) {
            $reasons[] = $exception->getMessage();

            return ['cost_price' => '0.0000', 'sale_price' => '0.0000'];
        }
    }

    private function decimalCents(mixed $value): int
    {
        $decimal = trim((string) $value);
        if (preg_match('/^([0-9]+)(?:\.([0-9]+))?$/', $decimal, $matches) !== 1) {
            throw new RuntimeException('Supplier price is missing or not a positive decimal.');
        }
        $fraction = str_pad($matches[2] ?? '', 2, '0');
        if (preg_match('/[1-9]/', substr($fraction, 2)) === 1) {
            throw new RuntimeException('Supplier price has more than two non-zero decimal places; exact 5% pricing needs review.');
        }
        $cents = ((int) $matches[1] * 100) + (int) substr($fraction, 0, 2);
        if ($cents < 1) {
            throw new RuntimeException('Supplier price must be greater than zero.');
        }

        return $cents;
    }

    private function formatScaled(int $value, int $scale): string
    {
        $base = 10 ** $scale;

        return intdiv($value, $base).'.'.str_pad((string) ($value % $base), $scale, '0', STR_PAD_LEFT);
    }

    /** @return array<int, object> */
    private function releaseWarehouses(): array
    {
        $configured = $this->configuredAllocations();
        $rows = DB::table('warehouses')->whereIn('code', array_keys($configured))->get()->keyBy('code');
        if ($rows->count() !== count($configured)) {
            throw new RuntimeException('One or more configured release warehouses do not exist.');
        }
        $result = [];
        foreach ($configured as $code => $units) {
            $warehouse = $rows->get($code);
            if (! $warehouse || ! (bool) $warehouse->is_active) {
                throw new RuntimeException("Configured release warehouse {$code} is inactive.");
            }
            $metadata = $this->jsonArray($warehouse->metadata);
            if (($metadata['purpose'] ?? null) === 'phase_2_inventory_pos_verification') {
                throw new RuntimeException("Verification warehouse {$code} cannot receive catalog release stock.");
            }
            $warehouse->release_units = $units;
            $result[] = $warehouse;
        }

        return $result;
    }

    /** @return array<string, int> */
    private function configuredAllocations(): array
    {
        $central = (array) config('catalog_release.inventory.central_warehouse');
        $allocations = [(string) ($central['code'] ?? '') => (int) ($central['units'] ?? 0)];
        foreach ((array) config('catalog_release.inventory.regional_warehouses') as $code => $units) {
            $allocations[(string) $code] = (int) $units;
        }

        return $allocations;
    }

    /** @param array<int, object> $warehouses @return array<string, mixed> */
    private function inventoryPlan(array $warehouses): array
    {
        return [
            'total_units_per_product' => (int) config('catalog_release.inventory.total_units'),
            'warehouses' => array_map(static fn (object $warehouse): array => [
                'id' => (int) $warehouse->id,
                'code' => (string) $warehouse->code,
                'units' => (int) $warehouse->release_units,
                'country_id' => $warehouse->country_id ? (int) $warehouse->country_id : null,
                'marketplace_id' => $warehouse->marketplace_id ? (int) $warehouse->marketplace_id : null,
            ], $warehouses),
        ];
    }

    /** @param list<int> $productIds @return array<string, object> */
    private function assetsByProductAndChecksum(int $sourceId, array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }
        $result = [];
        $rows = DB::table('supplier_product_assets as asset')
            ->join('supplier_products as sp', 'sp.id', '=', 'asset.supplier_product_id')
            ->where('sp.catalog_source_id', $sourceId)
            ->whereIn('sp.product_id', $productIds)
            ->where('asset.asset_type', 'image')
            ->where('asset.download_status', 'downloaded')
            ->whereNotNull('asset.checksum')
            ->orderBy('asset.id')
            ->select(['asset.*', 'sp.product_id'])
            ->get();
        foreach ($rows as $row) {
            $key = $this->assetKey((int) $row->product_id, (string) $row->checksum);
            $result[$key] ??= $row;
        }

        return $result;
    }

    /** @param list<int> $productIds */
    private function imagesByProduct(array $productIds)
    {
        return $productIds === []
            ? collect()
            : DB::table('product_images')->whereIn('product_id', $productIds)->whereNotNull('checksum')->orderBy('id')->get()->groupBy('product_id');
    }

    private function assetKey(int $productId, string $checksum): string
    {
        return $productId.':'.strtolower(trim($checksum));
    }

    /** @param object $row @return array<string, mixed> */
    private function quarantineSnapshot(object $row): array
    {
        return [
            'product_id' => (int) $row->product_id,
            'supplier_product_id' => (int) $row->supplier_product_id,
            'sku' => (string) $row->sku,
            'name' => (string) $row->name,
            'source_url' => (string) ($row->supplier_source_url ?: $row->product_source_url),
            'reason' => 'Exact configured source template/sentinel SKU.',
        ];
    }

    /** @param object $product @param array<string, mixed> $item */
    private function assertProductFingerprint(object $product, array $item): void
    {
        $fingerprint = $this->hash([
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'slug' => (string) $product->slug,
            'sku' => (string) $product->sku,
            'category_id' => (int) $product->category_id,
            'status' => (string) $product->status,
            'visibility_status' => (string) $product->visibility_status,
            'base_price' => (string) $product->base_price,
            'cost_price' => (string) $product->cost_price,
            'sale_price' => (string) $product->sale_price,
            'stock_quantity' => (int) $product->stock_quantity,
            'updated_at' => $this->dateString($product->updated_at),
        ]);
        if (! hash_equals((string) $item['product_fingerprint'], $fingerprint)) {
            throw new RuntimeException("Product {$product->id} changed after preflight.");
        }
    }

    private function imageFingerprint(object $image, int $assetId): string
    {
        return $this->hash([
            'id' => (int) $image->id,
            'asset_id' => $assetId,
            'product_id' => (int) $image->product_id,
            'file_path' => (string) $image->file_path,
            'storage_disk' => (string) ($image->storage_disk ?: config('elecforest_import.image_disk', 'public')),
            'checksum' => strtolower((string) $image->checksum),
            'mime_type' => strtolower((string) $image->mime_type),
            'file_size' => (int) $image->file_size,
            'width' => (int) $image->width,
            'height' => (int) $image->height,
            'original_url' => (string) $image->original_url,
            'source_url' => (string) $image->source_url,
            'source_name' => (string) $image->source_name,
            'updated_at' => $this->dateString($image->updated_at),
        ]);
    }

    /** @param array<string, mixed> $item @param array<string, mixed> $allocation @param array<string, mixed> $plan @param array<string, mixed> $authorization */
    private function inventoryProvenance(array $item, array $allocation, array $plan, array $authorization, Carbon $now): array
    {
        return [
            'catalog_release' => [
                'plan_hash' => $plan['plan_hash'],
                'backup_reference' => $authorization['backup_reference'],
                'allocation_units' => $allocation['units'],
                'warehouse_code' => $allocation['code'],
            ],
            'source_name' => (string) config('catalog_release.source.name'),
            'source_url' => $item['source_url'],
            'source_file' => $item['source_file'],
            'source_page_url' => $item['source_page_url'],
            'downloaded_at' => $item['downloaded_at'],
            'imported_at' => $now->toIso8601String(),
            'data_year' => $item['data_year'],
            'license_note' => $item['license_note'],
            'confidence_level' => 'operator_configured_exact_allocation',
            'original_raw_value' => ['requested_units' => (int) config('catalog_release.inventory.total_units'), 'allocation' => $this->configuredAllocations()],
            'normalized_value' => ['warehouse_code' => $allocation['code'], 'units' => $allocation['units']],
            'source_notes' => 'Fixed operator-directed 80% China central allocation with the remaining units spread 667/667/666 across existing regional warehouses.',
            'last_updated' => $now->toIso8601String(),
            'advisory_disclaimer' => self::ADVISORY,
        ];
    }

    /** @param array<string, mixed> $authorization */
    private function recordMediaRiskAcknowledgement(int $productId, int $supplierProductId, string $planHash, array $authorization, Carbon $now): void
    {
        $tasks = DB::table('catalog_review_tasks')
            ->where('product_id', $productId)
            ->where('supplier_product_id', $supplierProductId)
            ->where('task_type', 'media_rights_review')
            ->where('status', 'open')
            ->lockForUpdate()
            ->get();
        foreach ($tasks as $task) {
            $evidence = $this->jsonArray($task->evidence_json);
            $evidence['catalog_release'] = [
                'plan_hash' => $planHash,
                'backup_reference' => $authorization['backup_reference'],
                'operator_acknowledged_publication_risk' => true,
                'license_independently_verified' => false,
                'source_notes' => (string) config('catalog_release.media.approval_note'),
                'confidence_level' => 'checksum_verified_license_unverified_review_open',
                'last_updated' => $now->toIso8601String(),
                'advisory_disclaimer' => self::ADVISORY,
            ];
            DB::table('catalog_review_tasks')->where('id', $task->id)->update([
                'evidence_json' => $this->json($evidence),
                'updated_at' => $now,
            ]);
        }
    }

    /** @param array<string, mixed> $report */
    private function writeReport(array $report): string
    {
        $disk = Storage::disk((string) config('catalog_release.reports.disk', 'local'));
        $directory = trim((string) config('catalog_release.reports.directory', 'catalog-releases'), '/');
        $stamp = now()->format('Ymd-His-u');
        $path = ($directory === '' ? '' : $directory.'/').$stamp.'-'.substr((string) $report['plan_hash'], 0, 16).'-'.$report['status'].'.json';
        if ($disk->exists($path)) {
            throw new RuntimeException("Immutable catalog release report {$path} already exists.");
        }
        if (! $disk->put($path, $this->json($report, true))) {
            throw new RuntimeException('Catalog release audit report could not be written.');
        }

        return $path;
    }

    private function movementKey(string $planHash, int $productId, int $warehouseId): string
    {
        return 'catalog-release:'.substr($planHash, 0, 24).':'.$productId.':'.$warehouseId;
    }

    /** @param array<string, int> $overrides @return array<string, int> */
    private function zeroResult(array $overrides = []): array
    {
        return array_replace([
            'released_products' => 0,
            'already_released_products' => 0,
            'price_rows_created' => 0,
            'stock_rows_created' => 0,
            'movement_rows_created' => 0,
            'images_activated' => 0,
        ], $overrides);
    }

    private function assertConfiguration(): void
    {
        $allocations = $this->configuredAllocations();
        if (array_key_exists('', $allocations) || count($allocations) !== 4 || count(array_unique(array_keys($allocations))) !== 4) {
            throw new RuntimeException('Catalog release requires one central and exactly three distinct regional warehouses.');
        }
        foreach ($allocations as $code => $units) {
            if ($units < 1) {
                throw new RuntimeException("Warehouse {$code} has an invalid release allocation.");
            }
        }
        if (array_sum($allocations) !== (int) config('catalog_release.inventory.total_units')) {
            throw new RuntimeException('Warehouse allocations do not equal the configured total units per product.');
        }
        if ((int) config('catalog_release.margin_percent') !== 5) {
            throw new RuntimeException('This governed release supports only the explicitly requested 5% margin.');
        }
        if ((array) config('catalog_release.quarantine_skus') === []) {
            throw new RuntimeException('A configured template-SKU quarantine is required.');
        }
    }

    private function assertSchema(): void
    {
        foreach ([
            'products', 'product_images', 'product_seo_meta', 'catalog_sources', 'supplier_products',
            'supplier_product_offers', 'supplier_product_assets', 'catalog_product_sources', 'catalog_review_tasks',
            'catalog_change_events', 'marketplaces', 'currencies', 'marketplace_product_prices', 'regional_price_history',
            'warehouses', 'inventory_stocks', 'inventory_movements', 'audit_logs',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required catalog release table {$table} is missing.");
            }
        }
        foreach ([
            'supplier_product_offer_id', 'source_name', 'source_url', 'source_file', 'source_page_url',
            'downloaded_at', 'imported_at', 'data_year', 'license_note', 'confidence_level',
            'original_raw_value', 'normalized_value', 'pricing_rule', 'source_review_status',
        ] as $column) {
            if (! Schema::hasColumn('marketplace_product_prices', $column)) {
                throw new RuntimeException("Run the additive catalog release migration; marketplace_product_prices.{$column} is missing.");
            }
        }
        $this->assertProductPriceScale();
    }

    private function assertProductPriceScale(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }
        foreach (['base_price', 'cost_price', 'sale_price'] as $column) {
            if ($driver === 'pgsql') {
                $row = DB::selectOne(
                    'select numeric_scale from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ?',
                    ['products', $column]
                );
            } elseif ($driver === 'mysql') {
                $row = DB::selectOne(
                    'select numeric_scale from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?',
                    ['products', $column]
                );
            } else {
                throw new RuntimeException("Unsupported catalog release database driver {$driver}.");
            }
            if (! $row || (int) $row->numeric_scale < 4) {
                throw new RuntimeException("products.{$column} must be DECIMAL with at least four fractional digits before release.");
            }
        }
    }

    private function isZero(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '' || (float) $value === 0.0;
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private function existingColumns(string $table, array $values): array
    {
        $this->columnCache[$table] ??= array_fill_keys(Schema::getColumnListing($table), true);

        return array_intersect_key($values, $this->columnCache[$table]);
    }

    private function dateString(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return Carbon::parse($value)->toIso8601String();
    }

    /** @return array<string, mixed> */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return json_decode((string) $value, true) ?: [];
    }

    /** @param array<string, mixed> $value */
    private function hash(array $value): string
    {
        return hash('sha256', $this->json($value));
    }

    private function json(mixed $value, bool $pretty = false): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | ($pretty ? JSON_PRETTY_PRINT : 0)
        );
    }
}
