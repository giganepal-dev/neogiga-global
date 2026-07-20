<?php

namespace App\Services\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class JlcpcbCommerceEnrichmentService
{
    private const PLAN_VERSION = 'jlcpcb-commerce-enrichment-v2';

    private const ALLOCATION_POLICY = 'fixed_10000_desired_60_percent_shenzhen_40_percent_rotated_regional_capped_by_observed_stock';

    private const MANAGED_BY = 'jlcpcb_commerce_enrichment';

    private const ADVISORY = 'Advisory only. Supplier availability is not physical NeoGiga inventory, cannot be reserved or fulfilled, and must be confirmed by quotation.';

    /** @return array<string, mixed> */
    public function plan(int $limit = 0, ?int $chunkSize = null): array
    {
        $chunkSize = $this->validatedChunkSize($chunkSize);
        $limit = $this->validatedLimit($limit);
        $this->assertSchema();
        $context = $this->context();
        $summary = $this->emptyPlanSummary();
        $digest = hash_init('sha256');

        $this->eachBatch($context, $limit, $chunkSize, function ($rows) use (&$summary, $context, $digest): void {
            foreach ($rows as $row) {
                $analysis = $this->analyze($row, $context);
                $summary['products_scanned']++;
                $summary['first_source_link_id'] ??= (int) $row->source_link_id;
                $summary['last_source_link_id'] = (int) $row->source_link_id;

                if ($analysis['price'] === null) {
                    $summary['price_rows_invalid']++;
                } elseif ($row->existing_price_id !== null) {
                    $summary['price_rows_skipped_existing']++;
                } else {
                    $summary['price_rows_to_create']++;
                }

                if ($analysis['total_available_quantity'] > 0) {
                    $summary['availability_products']++;
                    $summary['availability_rows_to_upsert'] += count($analysis['allocations']);
                    if ($analysis['total_available_quantity'] < (int) config('jlcpcb_commerce.availability.minimum_quantity')) {
                        $summary['availability_products_capped_below_minimum']++;
                    }
                } else {
                    $summary['availability_products_without_observed_stock']++;
                }

                hash_update($digest, $this->json([
                    'source_link_id' => (int) $row->source_link_id,
                    'source_link_updated_at' => (string) $row->source_link_updated_at,
                    'product_id' => (int) $row->product_id,
                    'offer_id' => $row->offer_id === null ? null : (int) $row->offer_id,
                    'offer_updated_at' => (string) $row->offer_updated_at,
                    'price_breaks' => $row->price_breaks,
                    'offer_stock' => $row->offer_stock,
                    'currency' => $row->offer_currency,
                    'existing_price_id' => $row->existing_price_id === null ? null : (int) $row->existing_price_id,
                    'analysis' => $analysis,
                ]));
            }
        });

        $stablePlan = [
            'version' => self::PLAN_VERSION,
            'limit' => $limit,
            'marketplace' => [
                'id' => (int) $context['marketplace']->id,
                'code' => (string) $context['marketplace']->code,
                'currency' => (string) $context['currency_code'],
            ],
            'warehouses' => [
                'central' => (string) $context['central']->code,
                'regional' => array_map(static fn (object $warehouse): string => (string) $warehouse->code, $context['regionals']),
            ],
            'summary' => $summary,
            'data_digest' => hash_final($digest),
        ];

        return $stablePlan + [
            'plan_hash' => hash('sha256', $this->json($stablePlan)),
            'dry_run' => true,
            'generated_at' => now()->toIso8601String(),
            'disclaimer' => self::ADVISORY,
        ];
    }

    /** @return array<string, mixed> */
    public function apply(
        string $expectedPlanHash,
        string $backupReference,
        int $limit = 0,
        ?int $chunkSize = null,
    ): array {
        if (preg_match('/^[a-f0-9]{64}$/', strtolower(trim($expectedPlanHash))) !== 1) {
            throw new RuntimeException('A valid dry-run --expected-plan-hash is required.');
        }
        $verifiedBackup = $this->verifiedBackupReference($backupReference);

        $chunkSize = $this->validatedChunkSize($chunkSize);
        $limit = $this->validatedLimit($limit);
        $plan = $this->plan($limit, $chunkSize);
        if (! hash_equals((string) $plan['plan_hash'], strtolower(trim($expectedPlanHash)))) {
            throw new RuntimeException('The JLC commerce data changed after dry-run; refusing to apply a stale plan.');
        }

        $context = $this->context();
        $result = $this->emptyApplySummary();

        $this->eachBatch($context, $limit, $chunkSize, function ($rows) use (&$result, $context): void {
            DB::transaction(function () use ($rows, &$result, $context): void {
                foreach ($rows as $row) {
                    $this->lockAndVerifyRow($row);
                    $analysis = $this->analyze($row, $context);
                    $result['products_processed']++;

                    if ($analysis['price'] === null) {
                        $result['price_rows_invalid']++;
                    } elseif ($this->globalPriceExists((int) $row->product_id, (int) $context['marketplace']->id)) {
                        // Every pre-existing row is treated as manually managed,
                        // including rows created by a previous idempotent run.
                        $result['price_rows_skipped_existing']++;
                    } else {
                        $this->insertPrice($row, $analysis['price'], $context);
                        $result['price_rows_created']++;
                    }

                    $availability = $this->upsertAvailability($row, $analysis, $context);
                    foreach ($availability as $key => $count) {
                        $result[$key] += $count;
                    }
                }
            }, 3);
        });

        return [
            'status' => 'completed',
            'plan_hash' => $plan['plan_hash'],
            'backup_reference' => $verifiedBackup,
            'result' => $result,
            'inventory_stocks_written' => 0,
            'completed_at' => now()->toIso8601String(),
            'disclaimer' => self::ADVISORY,
        ];
    }

    /** @return array<string, int|null> */
    private function emptyPlanSummary(): array
    {
        return [
            'products_scanned' => 0,
            'first_source_link_id' => null,
            'last_source_link_id' => null,
            'price_rows_to_create' => 0,
            'price_rows_skipped_existing' => 0,
            'price_rows_invalid' => 0,
            'availability_products' => 0,
            'availability_rows_to_upsert' => 0,
            'availability_products_capped_below_minimum' => 0,
            'availability_products_without_observed_stock' => 0,
        ];
    }

    /** @return array<string, int> */
    private function emptyApplySummary(): array
    {
        return [
            'products_processed' => 0,
            'price_rows_created' => 0,
            'price_rows_skipped_existing' => 0,
            'price_rows_invalid' => 0,
            'availability_rows_created' => 0,
            'availability_rows_updated' => 0,
            'availability_rows_unchanged' => 0,
            'availability_rows_preserved_manual' => 0,
            'availability_rows_deactivated' => 0,
        ];
    }

    /** @return array<string, mixed> */
    private function context(): array
    {
        $source = DB::table('catalog_sources')
            ->where('code', (string) config('jlcpcb_commerce.source.code'))
            ->where('active', true)
            ->first();
        if (! $source) {
            throw new RuntimeException('The active JLCPCB catalog source was not found.');
        }

        $marketplace = DB::table('marketplaces')
            ->where('code', (string) config('jlcpcb_commerce.marketplace.code'))
            ->where('is_active', true)
            ->first();
        if (! $marketplace) {
            throw new RuntimeException('The active GLOBAL marketplace was not found.');
        }
        $currencyCode = $marketplace->currency_id
            ? DB::table('currencies')->where('id', $marketplace->currency_id)->value('code')
            : null;
        if (strtoupper((string) $currencyCode) !== strtoupper((string) config('jlcpcb_commerce.marketplace.currency'))) {
            throw new RuntimeException('The GLOBAL marketplace must use USD before JLC commerce enrichment.');
        }

        $centralCode = (string) config('jlcpcb_commerce.availability.central_warehouse_code');
        $regionalCodes = array_values(array_unique(array_map('strval', (array) config('jlcpcb_commerce.availability.regional_warehouse_codes'))));
        $warehouses = DB::table('warehouses')
            ->whereIn('code', array_merge([$centralCode], $regionalCodes))
            ->where('is_active', true)
            ->get()
            ->keyBy('code');
        $central = $warehouses->get($centralCode);
        if (! $central || $this->isVerificationWarehouse($central)) {
            throw new RuntimeException("The active Shenzhen supplier-overlay warehouse {$centralCode} was not found.");
        }
        $regionals = [];
        foreach ($regionalCodes as $code) {
            $warehouse = $warehouses->get($code);
            if ($warehouse && ! $this->isVerificationWarehouse($warehouse)) {
                $regionals[] = $warehouse;
            }
        }
        if ($regionals === []) {
            throw new RuntimeException('At least one configured active regional warehouse is required for supplier availability.');
        }

        return compact('source', 'marketplace', 'central', 'regionals') + [
            'currency_code' => strtoupper((string) $currencyCode),
        ];
    }

    private function isVerificationWarehouse(object $warehouse): bool
    {
        $metadata = $this->jsonArray($warehouse->metadata ?? null);

        return ($metadata['purpose'] ?? null) === 'phase_2_inventory_pos_verification';
    }

    /** @param array<string, mixed> $context */
    private function query(array $context): Builder
    {
        $existingPrices = DB::table('marketplace_product_prices')
            ->where('marketplace_id', (int) $context['marketplace']->id)
            ->selectRaw('product_id, MIN(id) AS existing_price_id')
            ->groupBy('product_id');

        return DB::table('catalog_product_sources as cps')
            ->join('products as product', 'product.id', '=', 'cps.product_id')
            ->leftJoin('catalog_distributor_offers as offer', function ($join): void {
                $join->on('offer.product_id', '=', 'cps.product_id')
                    ->on('offer.sku', '=', 'cps.source_part_id')
                    ->where('offer.distributor', '=', 'LCSC/JLCPCB');
            })
            ->leftJoin('catalog_import_batches as batch', 'batch.id', '=', 'cps.import_batch_id')
            ->leftJoinSub($existingPrices, 'existing_price', 'existing_price.product_id', '=', 'cps.product_id')
            ->where('cps.source_id', (int) $context['source']->id)
            ->select([
                'cps.id as source_link_id',
                'cps.product_id',
                'cps.source_part_id',
                'cps.import_batch_id',
                'cps.source_url as source_link_url',
                'cps.imported_at as source_link_imported_at',
                'cps.created_at as source_link_created_at',
                'cps.updated_at as source_link_updated_at',
                'product.sku as product_sku',
                'offer.id as offer_id',
                'offer.distributor',
                'offer.price_breaks',
                'offer.stock as offer_stock',
                'offer.currency as offer_currency',
                'offer.fetched_at as offer_fetched_at',
                'offer.updated_at as offer_updated_at',
                'batch.checksum as source_checksum',
                'batch.started_at as batch_started_at',
                'existing_price.existing_price_id',
            ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  callable(Collection<int, object>):void  $callback
     */
    private function eachBatch(array $context, int $limit, int $chunkSize, callable $callback): void
    {
        $lastId = 0;
        $processed = 0;
        do {
            $take = $limit > 0 ? min($chunkSize, $limit - $processed) : $chunkSize;
            if ($take < 1) {
                break;
            }
            $rows = (clone $this->query($context))
                ->where('cps.id', '>', $lastId)
                ->orderBy('cps.id')
                ->limit($take)
                ->get();
            if ($rows->isEmpty()) {
                break;
            }
            $callback($rows);
            $processed += $rows->count();
            $lastId = (int) $rows->last()->source_link_id;
        } while ($limit === 0 || $processed < $limit);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function analyze(object $row, array $context): array
    {
        $price = null;
        if ($row->offer_id !== null && strtoupper((string) $row->offer_currency) === 'USD') {
            try {
                $price = $this->priceSnapshot($row->price_breaks);
            } catch (RuntimeException) {
                $price = null;
            }
        }

        $desired = $this->deterministicDesiredQuantity((string) $row->source_part_id);
        $observed = $this->observedStock($row->offer_stock);
        $total = min($desired, $observed);
        $allocations = $this->allocations($total, (string) $row->source_part_id, $context);

        return [
            'price' => $price,
            'desired_quantity' => $desired,
            'observed_offer_stock' => $observed,
            'total_available_quantity' => $total,
            'allocations' => $allocations,
        ];
    }

    /** @return array{q_from:int,source_unit_price:string,cost_price:string,base_price:string,storage_scale:int,pricing_rule:string,raw_break:array<string,mixed>} */
    private function priceSnapshot(mixed $rawBreaks): array
    {
        $breaks = $this->jsonArray($rawBreaks);
        $first = $breaks[0] ?? null;
        if (! is_array($first)) {
            throw new RuntimeException('The first source price break is missing.');
        }
        $quantity = $first['qFrom'] ?? $first['q_from'] ?? $first['quantity'] ?? $first['minimum_quantity'] ?? null;
        if (filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new RuntimeException('The first source price-break quantity is invalid.');
        }
        $rawPrice = $first['price'] ?? $first['unitPrice'] ?? $first['unit_price'] ?? null;
        $sourceUnitPrice = $this->roundDecimal($rawPrice, 6);
        $microunits = $this->scaledInteger($sourceUnitPrice, 6);
        if ($microunits < 1 || $microunits > intdiv(PHP_INT_MAX, 105)) {
            throw new RuntimeException('The first source price is outside the safe pricing range.');
        }

        $costScaled = intdiv($microunits + 50, 100);
        $baseScaled = intdiv(($microunits * 105) + 5_000, 10_000);
        $storageScale = 4;
        if ($costScaled < 1 || $baseScaled < 1) {
            // A verified source unit cost below $0.00005 cannot be represented
            // at four decimals. Retain the same rounded-six-decimal source
            // cost and exact 1.05 formula at eight-decimal storage precision.
            $storageScale = 8;
            $costScaled = $microunits * 100;
            $baseScaled = $microunits * 105;
        }
        $qFrom = (int) $quantity;

        return [
            'q_from' => $qFrom,
            'source_unit_price' => $sourceUnitPrice,
            'cost_price' => $this->formatScaledInteger($costScaled, $storageScale),
            'base_price' => $this->formatScaledInteger($baseScaled, $storageScale),
            'storage_scale' => $storageScale,
            'pricing_rule' => "source_minimum_quantity_{$qFrom}_price_x_1_05",
            'raw_break' => $first,
        ];
    }

    private function roundDecimal(mixed $value, int $scale): string
    {
        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new RuntimeException('The source price is not finite.');
            }
            $value = rtrim(rtrim(sprintf('%.12F', $value), '0'), '.');
        }
        $decimal = trim((string) $value);
        if (preg_match('/^\+?([0-9]+)(?:\.([0-9]+))?$/', $decimal, $matches) !== 1) {
            throw new RuntimeException('The source price is not a positive decimal.');
        }
        $whole = ltrim($matches[1], '0') ?: '0';
        $fraction = $matches[2] ?? '';
        $padded = str_pad($fraction, $scale + 1, '0');
        $kept = substr($padded, 0, $scale);
        $scaled = ltrim($whole.$kept, '0') ?: '0';
        if ((int) ($padded[$scale] ?? '0') >= 5) {
            $scaled = $this->incrementDecimalString($scaled);
        }

        if (strlen($scaled) > 18) {
            throw new RuntimeException('The source price exceeds supported marketplace precision.');
        }

        return $this->formatScaledInteger((int) $scaled, $scale);
    }

    private function incrementDecimalString(string $number): string
    {
        $digits = str_split($number);
        for ($index = count($digits) - 1; $index >= 0; $index--) {
            if ($digits[$index] !== '9') {
                $digits[$index] = (string) ((int) $digits[$index] + 1);

                return implode('', $digits);
            }
            $digits[$index] = '0';
        }

        return '1'.implode('', $digits);
    }

    private function scaledInteger(string $value, int $scale): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return (int) ($whole.str_pad($fraction, $scale, '0'));
    }

    private function formatScaledInteger(int $value, int $scale): string
    {
        $digits = str_pad((string) $value, $scale + 1, '0', STR_PAD_LEFT);

        return substr($digits, 0, -$scale).'.'.substr($digits, -$scale);
    }

    private function deterministicDesiredQuantity(string $sourcePartId): int
    {
        return (int) config('jlcpcb_commerce.availability.desired_quantity');
    }

    private function observedStock(mixed $value): int
    {
        $stock = trim((string) $value);
        if ($stock === '' || preg_match('/^[0-9]+$/', $stock) !== 1 || strlen($stock) > 18) {
            return 0;
        }

        return min((int) $stock, PHP_INT_MAX);
    }

    /** @param array<string, mixed> $context @return list<array{warehouse:object,quantity:int,percent:string}> */
    private function allocations(int $total, string $sourcePartId, array $context): array
    {
        if ($total < 1) {
            return [];
        }
        $centralPercent = (int) config('jlcpcb_commerce.availability.central_percent');
        $centralQuantity = intdiv($total * $centralPercent, 100);
        if ($centralQuantity < 1) {
            $centralQuantity = 1;
        }
        $centralQuantity = min($centralQuantity, $total);
        $remaining = $total - $centralQuantity;
        $allocations = [[
            'warehouse' => $context['central'],
            'quantity' => $centralQuantity,
            'percent' => number_format(($centralQuantity / $total) * 100, 2, '.', ''),
        ]];

        $regionals = $context['regionals'];
        $count = count($regionals);
        $startHash = unpack('Nvalue', substr(hash('sha256', 'jlcpcb-regional:'.$sourcePartId, true), 0, 4));
        $start = (int) $startHash['value'] % $count;
        $base = intdiv($remaining, $count);
        $extra = $remaining % $count;
        for ($position = 0; $position < $count; $position++) {
            $index = ($start + $position) % $count;
            $quantity = $base + ($position < $extra ? 1 : 0);
            if ($quantity < 1) {
                continue;
            }
            $allocations[] = [
                'warehouse' => $regionals[$index],
                'quantity' => $quantity,
                'percent' => number_format(($quantity / $total) * 100, 2, '.', ''),
            ];
        }

        return $allocations;
    }

    private function lockAndVerifyRow(object $row): void
    {
        if (! DB::table('products')->where('id', $row->product_id)->lockForUpdate()->exists()) {
            throw new RuntimeException("Product {$row->product_id} disappeared during commerce enrichment.");
        }
        $sourceUpdatedAt = DB::table('catalog_product_sources')
            ->where('id', $row->source_link_id)
            ->lockForUpdate()
            ->value('updated_at');
        if ((string) $sourceUpdatedAt !== (string) $row->source_link_updated_at) {
            throw new RuntimeException("JLC source link {$row->source_link_id} changed after planning.");
        }
        if ($row->offer_id !== null) {
            $offerUpdatedAt = DB::table('catalog_distributor_offers')
                ->where('id', $row->offer_id)
                ->lockForUpdate()
                ->value('updated_at');
            if ((string) $offerUpdatedAt !== (string) $row->offer_updated_at) {
                throw new RuntimeException("JLC offer {$row->offer_id} changed after planning.");
            }
        }
    }

    private function globalPriceExists(int $productId, int $marketplaceId): bool
    {
        return DB::table('marketplace_product_prices')
            ->where('product_id', $productId)
            ->where('marketplace_id', $marketplaceId)
            ->lockForUpdate()
            ->exists();
    }

    /** @param array<string, mixed> $price @param array<string, mixed> $context */
    private function insertPrice(object $row, array $price, array $context): void
    {
        $now = now();
        $provenance = $this->provenance($row);
        $original = [
            'source_part_id' => (string) $row->source_part_id,
            'catalog_distributor_offer_id' => (int) $row->offer_id,
            'first_price_break' => $price['raw_break'],
            'currency' => (string) $row->offer_currency,
            'source_checksum' => $row->source_checksum,
            'import_batch_id' => $row->import_batch_id,
            'source_observed_at' => $row->offer_fetched_at,
            'downloaded_at_basis' => $provenance['downloaded_at_basis'],
        ];
        $normalized = [
            'source_unit_price' => $price['source_unit_price'],
            'cost_price' => $price['cost_price'],
            'base_price' => $price['base_price'],
            'sale_price' => null,
            'currency' => 'USD',
            'pricing_rule' => $price['pricing_rule'],
            'storage_scale' => $price['storage_scale'],
            'source_notes' => 'The first source price break was rounded to six decimals, then cost and 1.05 base price were rounded to four decimals. Existing prices are never overwritten.',
            'confidence_level' => 'source_provided_pending_commercial_verification',
            'last_updated' => $now->toIso8601String(),
            'disclaimer' => self::ADVISORY,
        ];

        DB::table('marketplace_product_prices')->insert([
            'product_id' => (int) $row->product_id,
            'product_variant_id' => null,
            'marketplace_id' => (int) $context['marketplace']->id,
            'base_price' => $price['base_price'],
            'sale_price' => null,
            'cost_price' => $price['cost_price'],
            'currency_code' => 'USD',
            'is_tax_inclusive' => false,
            'tax_rate' => null,
            'sale_start_date' => null,
            'sale_end_date' => null,
            'is_active' => true,
            'source_name' => $provenance['source_name'],
            'source_url' => $provenance['source_url'],
            'source_offer_id' => (int) $row->offer_id,
            'source_fetched_at' => $row->offer_fetched_at,
            'source_unit_price' => $price['source_unit_price'],
            'source_file' => $provenance['source_file'],
            'source_page_url' => $provenance['source_page_url'],
            'downloaded_at' => $provenance['downloaded_at'],
            'imported_at' => $now,
            'data_year' => $provenance['data_year'],
            'license_note' => $provenance['license_note'],
            'confidence_level' => 'source_provided_pending_commercial_verification',
            'original_raw_value' => $this->json($original),
            'normalized_value' => $this->json($normalized),
            'pricing_rule' => $price['pricing_rule'],
            'source_review_status' => 'source_imported_pending_commercial_review',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param  array<string, mixed>  $analysis
     * @param  array<string, mixed>  $context
     * @return array{availability_rows_created:int,availability_rows_updated:int,availability_rows_unchanged:int,availability_rows_preserved_manual:int,availability_rows_deactivated:int}
     */
    private function upsertAvailability(object $row, array $analysis, array $context): array
    {
        $counts = [
            'availability_rows_created' => 0,
            'availability_rows_updated' => 0,
            'availability_rows_unchanged' => 0,
            'availability_rows_preserved_manual' => 0,
            'availability_rows_deactivated' => 0,
        ];
        if ($row->offer_id === null) {
            return $counts;
        }

        $now = now();
        $provenance = $this->provenance($row);
        $activeWarehouseIds = [];
        foreach ($analysis['allocations'] as $allocation) {
            $warehouse = $allocation['warehouse'];
            $activeWarehouseIds[] = (int) $warehouse->id;
            $original = [
                'source_part_id' => (string) $row->source_part_id,
                'catalog_distributor_offer_id' => (int) $row->offer_id,
                'observed_offer_stock' => $analysis['observed_offer_stock'],
                'source_checksum' => $row->source_checksum,
                'import_batch_id' => $row->import_batch_id,
                'source_observed_at' => $row->offer_fetched_at,
                'downloaded_at_basis' => $provenance['downloaded_at_basis'],
            ];
            $normalized = [
                'desired_quantity' => $analysis['desired_quantity'],
                'capped_total_available_quantity' => $analysis['total_available_quantity'],
                'warehouse_code' => (string) $warehouse->code,
                'allocated_quantity' => $allocation['quantity'],
                'allocation_percent' => $allocation['percent'],
                'allocation_policy' => self::ALLOCATION_POLICY,
                'source_notes' => 'This is a deterministic supplier-availability overlay. It is separate from physical inventory and does not permit reservation or fulfillment.',
                'confidence_level' => 'source_observed_quote_only',
                'last_updated' => Carbon::parse($row->offer_updated_at ?: $row->source_link_updated_at)->toIso8601String(),
                'disclaimer' => self::ADVISORY,
            ];
            $values = [
                'product_id' => (int) $row->product_id,
                'catalog_source_id' => (int) $context['source']->id,
                'marketplace_id' => (int) $context['marketplace']->id,
                'source_part_id' => (string) $row->source_part_id,
                'supplier_name' => (string) ($row->distributor ?: 'LCSC/JLCPCB'),
                'observed_offer_stock' => $analysis['observed_offer_stock'],
                'desired_quantity' => $analysis['desired_quantity'],
                'total_available_quantity' => $analysis['total_available_quantity'],
                'allocated_quantity' => $allocation['quantity'],
                'allocation_percent' => $allocation['percent'],
                'stock_type' => 'supplier_virtual',
                'availability_status' => 'available_for_quote',
                'quote_only' => true,
                'is_reservable' => false,
                'is_fulfillable' => false,
                'is_active' => true,
                'allocation_policy' => self::ALLOCATION_POLICY,
                'source_observed_at' => $row->offer_fetched_at,
                'source_name' => $provenance['source_name'],
                'source_url' => $provenance['source_url'],
                'source_file' => $provenance['source_file'],
                'source_page_url' => $provenance['source_page_url'],
                'downloaded_at' => $provenance['downloaded_at'],
                'imported_at' => $now,
                'data_year' => $provenance['data_year'],
                'license_note' => $provenance['license_note'],
                'confidence_level' => 'source_observed_quote_only',
                'managed_by' => self::MANAGED_BY,
                'original_raw_value' => $this->json($original),
                'normalized_value' => $this->json($normalized),
                'updated_at' => $now,
            ];

            $existing = DB::table('supplier_availabilities')
                ->where('catalog_distributor_offer_id', $row->offer_id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();
            if (! $existing) {
                DB::table('supplier_availabilities')->insert($values + [
                    'catalog_distributor_offer_id' => (int) $row->offer_id,
                    'warehouse_id' => (int) $warehouse->id,
                    'is_manual_override' => false,
                    'is_locked' => false,
                    'created_at' => $now,
                ]);
                $counts['availability_rows_created']++;
            } elseif ($this->availabilityIsProtected($existing)) {
                $counts['availability_rows_preserved_manual']++;
            } elseif ($this->availabilityChanged($existing, $values)) {
                DB::table('supplier_availabilities')->where('id', $existing->id)->update($values);
                $counts['availability_rows_updated']++;
            } else {
                $counts['availability_rows_unchanged']++;
            }
        }

        $stale = DB::table('supplier_availabilities')
            ->where('catalog_distributor_offer_id', $row->offer_id)
            ->where('source_name', (string) config('jlcpcb_commerce.source.name'))
            ->where('managed_by', self::MANAGED_BY)
            ->where('is_manual_override', false)
            ->where('is_locked', false)
            ->where('is_active', true);
        if ($activeWarehouseIds !== []) {
            $stale->whereNotIn('warehouse_id', $activeWarehouseIds);
        }
        $counts['availability_rows_deactivated'] += $stale->update([
            'allocated_quantity' => 0,
            'allocation_percent' => '0.00',
            'availability_status' => 'unavailable_for_quote',
            'is_active' => false,
            'updated_at' => $now,
        ]);

        return $counts;
    }

    /** @param array<string, mixed> $values */
    private function availabilityChanged(object $existing, array $values): bool
    {
        foreach ([
            'product_id', 'catalog_source_id', 'marketplace_id', 'source_part_id', 'supplier_name',
            'observed_offer_stock', 'desired_quantity', 'total_available_quantity', 'allocated_quantity',
            'stock_type', 'availability_status', 'quote_only', 'is_reservable', 'is_fulfillable', 'is_active',
            'allocation_policy', 'source_name', 'source_url', 'source_file', 'source_page_url', 'data_year',
            'license_note', 'confidence_level', 'managed_by', 'original_raw_value', 'normalized_value',
        ] as $field) {
            if (in_array($field, ['original_raw_value', 'normalized_value'], true)) {
                if (! $this->jsonValuesEqual($existing->{$field} ?? null, $values[$field] ?? null)) {
                    return true;
                }

                continue;
            }
            if (in_array($field, ['quote_only', 'is_reservable', 'is_fulfillable', 'is_active'], true)) {
                // Booleans: pgsql PDO returns native bool, sqlite returns 0/1 —
                // string-casting false ('' vs '0') made every replay a phantom update.
                if ((bool) ($existing->{$field} ?? false) !== (bool) ($values[$field] ?? false)) {
                    return true;
                }

                continue;
            }
            if ((string) ($existing->{$field} ?? '') !== (string) ($values[$field] ?? '')) {
                return true;
            }
        }
        if (number_format((float) $existing->allocation_percent, 2, '.', '') !== (string) $values['allocation_percent']) {
            return true;
        }

        // imported_at is intentionally excluded: replaying identical source
        // facts must remain a no-op.
        return false;
    }

    private function availabilityIsProtected(object $availability): bool
    {
        return ! $this->availabilityIsServiceManaged($availability);
    }

    private function availabilityIsServiceManaged(object $availability): bool
    {
        return (string) ($availability->managed_by ?? '') === self::MANAGED_BY
            && $this->isExplicitDatabaseFalse($availability->is_manual_override ?? null)
            && $this->isExplicitDatabaseFalse($availability->is_locked ?? null);
    }

    private function isExplicitDatabaseFalse(mixed $value): bool
    {
        return $value === false
            || $value === 0
            || $value === '0'
            || $value === 'f'
            || $value === 'false';
    }

    private function jsonValuesEqual(mixed $left, mixed $right): bool
    {
        $normalize = static function (mixed $value): mixed {
            if (is_string($value)) {
                $decoded = json_decode($value, true);

                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            }

            return $value;
        };

        return $normalize($left) == $normalize($right);
    }

    /** @return array<string, string> */
    private function provenance(object $row): array
    {
        $configuredDownloadedAt = trim((string) config('jlcpcb_commerce.source.downloaded_at'));
        if ($configuredDownloadedAt !== '') {
            try {
                $downloadedAt = Carbon::parse($configuredDownloadedAt)->toIso8601String();
            } catch (\Throwable) {
                throw new RuntimeException('JLCPCB_SOURCE_DOWNLOADED_AT must be a valid date/time.');
            }
            $basis = 'verified preserved-source provenance manifest';
        } elseif ($row->batch_started_at) {
            $downloadedAt = (string) $row->batch_started_at;
            $basis = 'catalog import batch start; the original upstream download timestamp was not retained';
        } elseif ($row->source_link_imported_at) {
            $downloadedAt = (string) $row->source_link_imported_at;
            $basis = 'catalog source-link import timestamp; the original upstream download timestamp was not retained';
        } else {
            $downloadedAt = (string) $row->source_link_created_at;
            $basis = 'catalog source-link creation timestamp; the original upstream download timestamp was not retained';
        }
        $configuredYear = (int) config('jlcpcb_commerce.source.data_year', 0);
        $year = (string) ($configuredYear > 0
            ? $configuredYear
            : Carbon::parse($row->offer_fetched_at ?: $downloadedAt)->year);

        return [
            'source_name' => (string) config('jlcpcb_commerce.source.name'),
            'source_url' => (string) config('jlcpcb_commerce.source.url'),
            'source_file' => (string) config('jlcpcb_commerce.source.file'),
            'source_page_url' => (string) config('jlcpcb_commerce.source.page_url'),
            'downloaded_at' => $downloadedAt,
            'downloaded_at_basis' => $basis,
            'data_year' => $year,
            'license_note' => (string) config('jlcpcb_commerce.source.license_note'),
        ];
    }

    /** @return array<mixed> */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function verifiedBackupReference(string $reference): string
    {
        $reference = trim($reference);
        if ($reference === '') {
            throw new RuntimeException('A verified database backup reference is required before applying commerce enrichment.');
        }

        $root = realpath((string) config('jlcpcb_commerce.backup.root'));
        $path = realpath($reference);
        if ($root === false || ! is_dir($root)) {
            throw new RuntimeException('The configured JLC backup root does not exist.');
        }
        if ($path === false || ! is_dir($path)) {
            throw new RuntimeException('The supplied backup reference is not an existing directory.');
        }
        $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if ($path !== $root && ! str_starts_with($path.DIRECTORY_SEPARATOR, $rootPrefix)) {
            throw new RuntimeException('The supplied backup reference is outside the configured backup root.');
        }

        $manifest = $this->keyValueFile($path.DIRECTORY_SEPARATOR.'MANIFEST.txt');
        if (($manifest['status'] ?? null) !== 'verified') {
            throw new RuntimeException('The supplied backup manifest is not marked verified.');
        }
        $restore = $this->keyValueFile($path.DIRECTORY_SEPARATOR.'RESTORE_VERIFICATION.txt');
        if (($restore['result'] ?? null) !== 'passed') {
            throw new RuntimeException('The supplied backup has no passed isolated-restore verification.');
        }

        $this->verifyChecksumManifest(
            $path,
            'SHA256SUMS',
            (array) config('jlcpcb_commerce.backup.required_files', []),
        );
        $this->verifyChecksumManifest(
            $path,
            'RESTORE_VERIFICATION_SHA256SUMS',
            ['RESTORE_VERIFICATION_COUNTS.tsv', 'RESTORE_VERIFICATION.txt'],
        );

        return $path;
    }

    /** @return array<string, string> */
    private function keyValueFile(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Required verified-backup metadata is missing: '.basename($path));
        }
        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value);
        }

        return $values;
    }

    /** @param list<string> $requiredFiles */
    private function verifyChecksumManifest(string $directory, string $manifestName, array $requiredFiles): void
    {
        $manifestPath = $directory.DIRECTORY_SEPARATOR.$manifestName;
        if (! is_file($manifestPath) || ! is_readable($manifestPath)) {
            throw new RuntimeException("Required backup checksum manifest {$manifestName} is missing.");
        }
        $checksums = [];
        foreach (file($manifestPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (preg_match('/^([a-f0-9]{64})\\s+\\*?(.+)$/i', trim($line), $matches) !== 1) {
                throw new RuntimeException("Invalid line in backup checksum manifest {$manifestName}.");
            }
            $file = ltrim(trim($matches[2]), './');
            if ($file === '' || basename($file) !== $file) {
                throw new RuntimeException("Unsafe filename in backup checksum manifest {$manifestName}.");
            }
            $checksums[$file] = strtolower($matches[1]);
        }

        foreach ($requiredFiles as $file) {
            $file = (string) $file;
            $target = $directory.DIRECTORY_SEPARATOR.$file;
            if (basename($file) !== $file || ! isset($checksums[$file]) || ! is_file($target) || ! is_readable($target)) {
                throw new RuntimeException("Verified backup file {$file} is missing or is not checksummed.");
            }
            $actual = hash_file('sha256', $target);
            if ($actual === false || ! hash_equals($checksums[$file], strtolower($actual))) {
                throw new RuntimeException("Verified backup checksum mismatch for {$file}.");
            }
        }
    }

    private function validatedLimit(int $limit): int
    {
        if ($limit < 0) {
            throw new RuntimeException('The enrichment limit cannot be negative.');
        }

        return $limit;
    }

    private function validatedChunkSize(?int $chunkSize): int
    {
        $chunkSize ??= (int) config('jlcpcb_commerce.chunk_size', 500);
        if ($chunkSize < 1 || $chunkSize > 2_000) {
            throw new RuntimeException('The enrichment chunk size must be between 1 and 2000.');
        }

        return $chunkSize;
    }

    private function assertSchema(): void
    {
        foreach ([
            'products', 'catalog_sources', 'catalog_product_sources', 'catalog_import_batches',
            'catalog_distributor_offers', 'marketplaces', 'currencies', 'marketplace_product_prices',
            'warehouses', 'supplier_availabilities',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required commerce enrichment table {$table} is missing.");
            }
        }
        foreach ([
            'source_name', 'source_url', 'source_file', 'source_page_url', 'downloaded_at', 'imported_at',
            'data_year', 'license_note', 'confidence_level', 'original_raw_value', 'normalized_value',
            'pricing_rule', 'source_review_status',
        ] as $column) {
            if (! Schema::hasColumn('marketplace_product_prices', $column)) {
                throw new RuntimeException("Run the additive provenance migration; marketplace_product_prices.{$column} is missing.");
            }
        }
        foreach (['managed_by', 'is_manual_override', 'is_locked'] as $column) {
            if (! Schema::hasColumn('supplier_availabilities', $column)) {
                throw new RuntimeException("Run the additive availability-governance migration; supplier_availabilities.{$column} is missing.");
            }
        }
        // Decimal scale is a Postgres/MySQL invariant; SQLite (tests) has no
        // real column scale to assert — same skip as DraftCatalogReleaseService.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $priceScales = collect(DB::select(
                <<<'SQL'
                    SELECT column_name, numeric_scale
                    FROM information_schema.columns
                    WHERE table_schema = current_schema()
                      AND table_name = 'marketplace_product_prices'
                      AND column_name IN ('base_price', 'sale_price', 'cost_price')
                SQL
            ))->pluck('numeric_scale', 'column_name');
            foreach (['base_price', 'sale_price', 'cost_price'] as $column) {
                if ((int) ($priceScales[$column] ?? 0) < 8) {
                    throw new RuntimeException(
                        "Run the additive sub-cent precision migration; marketplace_product_prices.{$column} must retain at least eight decimal places."
                    );
                }
            }
        }
        $minimum = (int) config('jlcpcb_commerce.availability.minimum_quantity');
        $maximum = (int) config('jlcpcb_commerce.availability.maximum_quantity');
        $desired = (int) config('jlcpcb_commerce.availability.desired_quantity');
        if ($desired !== 10_000 || $minimum !== 10_000 || $maximum !== 10_000) {
            throw new RuntimeException('JLC supplier availability must retain the requested fixed 10,000 desired quantity.');
        }
        if ((int) config('jlcpcb_commerce.availability.central_percent') !== 60) {
            throw new RuntimeException('JLC supplier availability must retain the requested 60% Shenzhen policy.');
        }
        if ((int) config('jlcpcb_commerce.pricing.margin_numerator') !== 105
            || (int) config('jlcpcb_commerce.pricing.margin_denominator') !== 100) {
            throw new RuntimeException('JLC commerce pricing must retain the verified 1.05 formula.');
        }
    }
}
