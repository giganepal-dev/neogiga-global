<?php

namespace Tests\Feature;

use App\Services\Catalog\JlcpcbCommerceEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class JlcpcbCommerceEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    private int $sourceId;

    private int $marketplaceId;

    /** @var array<string, int> */
    private array $warehouseIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCommerceContext();
        config([
            'jlcpcb_commerce.backup.root' => storage_path('framework/testing/jlc-commerce-backups'),
            'jlcpcb_commerce.backup.required_files' => ['database.dump'],
        ]);
    }

    public function test_dry_run_is_read_only_and_apply_adds_exact_price_and_quote_only_overlay(): void
    {
        $first = $this->seedJlcProduct('C1002', 0.0136741, 100_000);
        $second = $this->seedJlcProduct('C1003', 0.0095556, 1_435);
        DB::table('marketplace_product_prices')->insert([
            'product_id' => $second['product_id'],
            'product_variant_id' => null,
            'marketplace_id' => $this->marketplaceId,
            'base_price' => '99.0000',
            'cost_price' => '90.0000',
            'currency_code' => 'USD',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $inventoryBefore = DB::table('inventory_stocks')->count();

        $service = app(JlcpcbCommerceEnrichmentService::class);
        $plan = $service->plan(limit: 2, chunkSize: 1);

        $this->assertTrue($plan['dry_run']);
        $this->assertSame(2, $plan['summary']['products_scanned']);
        $this->assertSame(1, $plan['summary']['price_rows_to_create']);
        $this->assertSame(1, $plan['summary']['price_rows_skipped_existing']);
        $this->assertSame(8, $plan['summary']['availability_rows_to_upsert']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $plan['plan_hash']);
        $this->assertDatabaseCount('marketplace_product_prices', 1);
        $this->assertDatabaseCount('supplier_availabilities', 0);
        $this->assertSame($inventoryBefore, DB::table('inventory_stocks')->count());

        $result = $service->apply($plan['plan_hash'], $this->verifiedBackup('first-apply'), limit: 2, chunkSize: 1);
        $this->assertSame('completed', $result['status']);
        $this->assertSame(1, $result['result']['price_rows_created']);
        $this->assertSame(1, $result['result']['price_rows_skipped_existing']);
        $this->assertSame(8, $result['result']['availability_rows_created']);
        $this->assertSame(0, $result['inventory_stocks_written']);
        $this->assertSame($inventoryBefore, DB::table('inventory_stocks')->count());

        $price = DB::table('marketplace_product_prices')
            ->where('product_id', $first['product_id'])
            ->where('marketplace_id', $this->marketplaceId)
            ->first();
        $this->assertNotNull($price);
        $this->assertSame('0.0137', number_format((float) $price->cost_price, 4, '.', ''));
        $this->assertSame('0.0144', number_format((float) $price->base_price, 4, '.', ''));
        $this->assertNull($price->sale_price);
        $this->assertSame('source_minimum_quantity_20_price_x_1_05', $price->pricing_rule);
        $this->assertSame('USD', $price->currency_code);
        $this->assertSame($first['offer_id'], (int) $price->source_offer_id);
        $this->assertNotEmpty($price->source_fetched_at);
        $this->assertSame('0.013674', number_format((float) $price->source_unit_price, 6, '.', ''));
        foreach ([
            'source_name', 'source_url', 'source_file', 'source_page_url', 'downloaded_at', 'imported_at',
            'data_year', 'license_note', 'confidence_level', 'original_raw_value', 'normalized_value',
        ] as $field) {
            $this->assertNotEmpty($price->{$field}, "Missing price provenance {$field}");
        }
        $this->assertSame('99.0000', number_format((float) DB::table('marketplace_product_prices')
            ->where('product_id', $second['product_id'])->value('base_price'), 4, '.', ''));

        $rows = DB::table('supplier_availabilities')
            ->where('product_id', $first['product_id'])
            ->get();
        $this->assertCount(4, $rows);
        $total = (int) $rows->sum('allocated_quantity');
        $this->assertSame(10_000, $total);
        $this->assertSame([10_000], $rows->pluck('desired_quantity')->unique()->values()->all());
        $central = $rows->firstWhere('warehouse_id', $this->warehouseIds['NG-SHENZHEN-CN']);
        $this->assertNotNull($central);
        $this->assertSame(intdiv($total * 60, 100), (int) $central->allocated_quantity);
        foreach ($rows as $row) {
            $this->assertTrue((bool) $row->quote_only);
            $this->assertFalse((bool) $row->is_reservable);
            $this->assertFalse((bool) $row->is_fulfillable);
            $this->assertSame('jlcpcb_commerce_enrichment', $row->managed_by);
            $this->assertFalse((bool) $row->is_manual_override);
            $this->assertFalse((bool) $row->is_locked);
            $this->assertSame('supplier_virtual', $row->stock_type);
            $this->assertSame('available_for_quote', $row->availability_status);
            foreach ([
                'source_name', 'source_url', 'source_file', 'source_page_url', 'downloaded_at', 'imported_at',
                'data_year', 'license_note', 'confidence_level', 'original_raw_value', 'normalized_value',
            ] as $field) {
                $this->assertNotEmpty($row->{$field}, "Missing availability provenance {$field}");
            }
        }
    }

    public function test_replay_is_idempotent_and_low_supplier_stock_is_never_inflated(): void
    {
        $catalog = $this->seedJlcProduct('C1005', 0.0035111, 628);
        $service = app(JlcpcbCommerceEnrichmentService::class);
        $firstPlan = $service->plan(limit: 1);
        $service->apply($firstPlan['plan_hash'], $this->verifiedBackup('idempotency-first'), limit: 1);

        $this->assertSame(628, (int) DB::table('supplier_availabilities')
            ->where('product_id', $catalog['product_id'])
            ->sum('allocated_quantity'));
        $this->assertSame(628, (int) DB::table('supplier_availabilities')
            ->where('product_id', $catalog['product_id'])
            ->max('total_available_quantity'));
        $this->assertSame(10_000, (int) DB::table('supplier_availabilities')
            ->where('product_id', $catalog['product_id'])
            ->max('desired_quantity'));
        $this->assertSame(376, (int) DB::table('supplier_availabilities')
            ->where('product_id', $catalog['product_id'])
            ->where('warehouse_id', $this->warehouseIds['NG-SHENZHEN-CN'])
            ->value('allocated_quantity'));
        $this->assertDatabaseCount('marketplace_product_prices', 1);
        $this->assertDatabaseCount('supplier_availabilities', 4);

        $manualRow = DB::table('supplier_availabilities')
            ->where('product_id', $catalog['product_id'])
            ->where('warehouse_id', $this->warehouseIds['NG-KATHMANDU-NP'])
            ->first();
        DB::table('supplier_availabilities')->where('id', $manualRow->id)->update([
            'allocated_quantity' => 123,
            'is_manual_override' => true,
            'managed_by' => 'admin',
        ]);

        $secondPlan = $service->plan(limit: 1);
        $replay = $service->apply($secondPlan['plan_hash'], $this->verifiedBackup('idempotency-second'), limit: 1);
        $this->assertSame(0, $replay['result']['price_rows_created']);
        $this->assertSame(1, $replay['result']['price_rows_skipped_existing']);
        $this->assertSame(0, $replay['result']['availability_rows_created']);
        $this->assertSame(0, $replay['result']['availability_rows_updated']);
        $this->assertSame(3, $replay['result']['availability_rows_unchanged']);
        $this->assertSame(1, $replay['result']['availability_rows_preserved_manual']);
        $this->assertSame(123, (int) DB::table('supplier_availabilities')->where('id', $manualRow->id)->value('allocated_quantity'));
        $this->assertDatabaseCount('marketplace_product_prices', 1);
        $this->assertDatabaseCount('supplier_availabilities', 4);
        $this->assertDatabaseCount('inventory_stocks', 0);
    }

    public function test_preexisting_ambiguous_manual_and_locked_availability_rows_are_never_adopted_or_changed(): void
    {
        $catalog = $this->seedJlcProduct('C1007', 0.0123456, 100_000);
        $ambiguousId = $this->seedPreexistingAvailability(
            $catalog,
            'NG-SHENZHEN-CN',
            111,
        );
        $partialOwnerId = $this->seedPreexistingAvailability(
            $catalog,
            'NG-KATHMANDU-NP',
            222,
            ['managed_by' => 'jlcpcb_commerce_enrichment'],
        );
        $manualId = $this->seedPreexistingAvailability(
            $catalog,
            'NG-NEWDELHI-IN',
            333,
            [
                'managed_by' => 'jlcpcb_commerce_enrichment',
                'is_manual_override' => true,
                'is_locked' => false,
            ],
        );
        $lockedId = $this->seedPreexistingAvailability(
            $catalog,
            'NG-DUBAI-AE',
            444,
            [
                'managed_by' => 'jlcpcb_commerce_enrichment',
                'is_manual_override' => false,
                'is_locked' => true,
            ],
        );

        $ambiguous = DB::table('supplier_availabilities')->where('id', $ambiguousId)->first();
        $this->assertNull($ambiguous->managed_by);
        $this->assertNull($ambiguous->is_manual_override);
        $this->assertNull($ambiguous->is_locked);
        $this->assertNull(DB::table('supplier_availabilities')->where('id', $partialOwnerId)->value('is_manual_override'));
        $this->assertNull(DB::table('supplier_availabilities')->where('id', $partialOwnerId)->value('is_locked'));

        $service = app(JlcpcbCommerceEnrichmentService::class);
        $plan = $service->plan(limit: 1);
        $result = $service->apply(
            $plan['plan_hash'],
            $this->verifiedBackup('legacy-availability-ownership'),
            limit: 1,
        );

        $this->assertSame(0, $result['result']['availability_rows_created']);
        $this->assertSame(0, $result['result']['availability_rows_updated']);
        $this->assertSame(0, $result['result']['availability_rows_unchanged']);
        $this->assertSame(4, $result['result']['availability_rows_preserved_manual']);
        $this->assertSame(0, $result['result']['availability_rows_deactivated']);
        $this->assertSame([
            $ambiguousId => 111,
            $partialOwnerId => 222,
            $manualId => 333,
            $lockedId => 444,
        ], DB::table('supplier_availabilities')
            ->whereIn('id', [$ambiguousId, $partialOwnerId, $manualId, $lockedId])
            ->orderBy('id')
            ->pluck('allocated_quantity', 'id')
            ->map(static fn ($quantity): int => (int) $quantity)
            ->all());
        $this->assertNull(DB::table('supplier_availabilities')->where('id', $ambiguousId)->value('managed_by'));
        $this->assertTrue((bool) DB::table('supplier_availabilities')->where('id', $manualId)->value('is_manual_override'));
        $this->assertTrue((bool) DB::table('supplier_availabilities')->where('id', $lockedId)->value('is_locked'));
        $this->assertDatabaseCount('supplier_availabilities', 4);
        $this->assertDatabaseCount('inventory_stocks', 0);
    }

    public function test_governance_migration_adopts_only_the_complete_prior_generator_signature(): void
    {
        $catalog = $this->seedJlcProduct('C1008', 0.0123456, 100_000);
        $generatedId = $this->seedPreexistingAvailability(
            $catalog,
            'NG-SHENZHEN-CN',
            8_000,
            [
                'allocation_policy' => 'deterministic_80_percent_shenzhen_20_percent_rotated_regional_capped_by_observed_stock',
                'source_name' => 'CDFER JLCPCB/LCSC in-stock SQLite',
            ],
        );
        $externalNearMatchId = $this->seedPreexistingAvailability(
            $catalog,
            'NG-KATHMANDU-NP',
            2_000,
            [
                'allocation_policy' => 'deterministic_80_percent_shenzhen_20_percent_rotated_regional_capped_by_observed_stock',
                'source_name' => 'External operator source',
            ],
        );
        $lockedNearMatchId = $this->seedPreexistingAvailability(
            $catalog,
            'NG-NEWDELHI-IN',
            2_000,
            [
                'allocation_policy' => 'deterministic_80_percent_shenzhen_20_percent_rotated_regional_capped_by_observed_stock',
                'source_name' => 'CDFER JLCPCB/LCSC in-stock SQLite',
                'is_locked' => true,
            ],
        );

        $migration = require database_path('migrations/2026_07_15_000600_add_governance_to_supplier_availabilities.php');
        $migration->up();

        $generated = DB::table('supplier_availabilities')->where('id', $generatedId)->first();
        $this->assertSame('jlcpcb_commerce_enrichment', $generated->managed_by);
        $this->assertFalse((bool) $generated->is_manual_override);
        $this->assertFalse((bool) $generated->is_locked);

        $externalNearMatch = DB::table('supplier_availabilities')->where('id', $externalNearMatchId)->first();
        $this->assertNull($externalNearMatch->managed_by);
        $this->assertNull($externalNearMatch->is_manual_override);
        $this->assertNull($externalNearMatch->is_locked);

        $lockedNearMatch = DB::table('supplier_availabilities')->where('id', $lockedNearMatchId)->first();
        $this->assertNull($lockedNearMatch->managed_by);
        $this->assertNull($lockedNearMatch->is_manual_override);
        $this->assertTrue((bool) $lockedNearMatch->is_locked);

        // The adoption is idempotent and does not broaden ownership on replay.
        $migration->up();
        $this->assertSame(1, DB::table('supplier_availabilities')
            ->where('managed_by', 'jlcpcb_commerce_enrichment')
            ->count());
    }

    public function test_sub_cent_source_cost_keeps_exact_five_percent_formula_at_safe_precision(): void
    {
        $catalog = $this->seedJlcProduct('C301912', 0.0000296, 9_000);
        $service = app(JlcpcbCommerceEnrichmentService::class);
        $plan = $service->plan(limit: 1);

        $this->assertSame(1, $plan['summary']['price_rows_to_create']);
        $this->assertSame(0, $plan['summary']['price_rows_invalid']);

        $result = $service->apply($plan['plan_hash'], $this->verifiedBackup('tiny-price'), limit: 1);
        $this->assertSame(1, $result['result']['price_rows_created']);

        $price = DB::table('marketplace_product_prices')
            ->where('product_id', $catalog['product_id'])
            ->where('marketplace_id', $this->marketplaceId)
            ->first();
        $this->assertSame('0.00003000', number_format((float) $price->cost_price, 8, '.', ''));
        $this->assertSame('0.00003150', number_format((float) $price->base_price, 8, '.', ''));
        $this->assertSame('0.000030', number_format((float) $price->source_unit_price, 6, '.', ''));
        $this->assertSame($catalog['offer_id'], (int) $price->source_offer_id);
        $normalized = json_decode((string) $price->normalized_value, true);
        $this->assertSame(8, $normalized['storage_scale']);
        $this->assertSame('source_minimum_quantity_20_price_x_1_05', $price->pricing_rule);
    }

    public function test_command_defaults_to_dry_run_and_apply_requires_backup_and_plan_hash(): void
    {
        $this->seedJlcProduct('C1006', 0.0083111, 18_013);

        $this->artisan('jlcpcb:enrich-commerce', ['--limit' => 1])
            ->expectsOutputToContain('Dry run only')
            ->assertSuccessful();
        $this->assertDatabaseCount('marketplace_product_prices', 0);
        $this->assertDatabaseCount('supplier_availabilities', 0);

        $this->artisan('jlcpcb:enrich-commerce', ['--limit' => 1, '--apply' => true])
            ->expectsOutputToContain('--yes is required')
            ->assertFailed();
        $this->assertDatabaseCount('marketplace_product_prices', 0);
        $this->assertDatabaseCount('supplier_availabilities', 0);
    }

    private function verifiedBackup(string $name): string
    {
        $root = (string) config('jlcpcb_commerce.backup.root');
        $path = $root.DIRECTORY_SEPARATOR.$name;
        File::ensureDirectoryExists($path);
        File::put($path.DIRECTORY_SEPARATOR.'database.dump', 'verified test database dump');
        File::put($path.DIRECTORY_SEPARATOR.'MANIFEST.txt', "status=verified\n");
        File::put($path.DIRECTORY_SEPARATOR.'RESTORE_VERIFICATION.txt', "result=passed\n");
        File::put($path.DIRECTORY_SEPARATOR.'RESTORE_VERIFICATION_COUNTS.tsv', "table\trow_count\nproducts\t1\n");
        File::put(
            $path.DIRECTORY_SEPARATOR.'SHA256SUMS',
            hash_file('sha256', $path.DIRECTORY_SEPARATOR.'database.dump')."  database.dump\n",
        );
        File::put(
            $path.DIRECTORY_SEPARATOR.'RESTORE_VERIFICATION_SHA256SUMS',
            hash_file('sha256', $path.DIRECTORY_SEPARATOR.'RESTORE_VERIFICATION_COUNTS.tsv')."  RESTORE_VERIFICATION_COUNTS.tsv\n".
            hash_file('sha256', $path.DIRECTORY_SEPARATOR.'RESTORE_VERIFICATION.txt')."  RESTORE_VERIFICATION.txt\n",
        );

        return $path;
    }

    /** @return array{product_id:int,offer_id:int} */
    private function seedJlcProduct(string $sourcePartId, float $price, int $stock): array
    {
        $productId = DB::table('products')->insertGetId([
            'name' => "JLC Test {$sourcePartId}",
            'slug' => strtolower("jlc-test-{$sourcePartId}"),
            'sku' => "NG-JLC-{$sourcePartId}",
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('catalog_product_sources')->insert([
            'product_id' => $productId,
            'source_id' => $this->sourceId,
            'source_part_id' => $sourcePartId,
            'source_url' => config('jlcpcb_commerce.source.url'),
            'source_payload_hash' => hash('sha256', $sourcePartId),
            'imported_at' => now()->subDay(),
            'last_synced_at' => now()->subDay(),
            'data_quality_score' => '1.00',
            'review_status' => 'source_imported_pending_approval',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $offerId = DB::table('catalog_distributor_offers')->insertGetId([
            'product_id' => $productId,
            'distributor' => 'LCSC/JLCPCB',
            'sku' => $sourcePartId,
            'price_breaks' => json_encode([
                ['qFrom' => 20, 'qTo' => 3980, 'price' => $price],
                ['qFrom' => 4000, 'qTo' => null, 'price' => round($price * 0.8, 7)],
            ]),
            'stock' => $stock,
            'currency' => 'USD',
            'fetched_at' => now()->subDay(),
            'review_status' => 'pending_review',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        return ['product_id' => $productId, 'offer_id' => $offerId];
    }

    /**
     * @param  array{product_id:int,offer_id:int}  $catalog
     * @param  array<string, mixed>  $governance
     */
    private function seedPreexistingAvailability(
        array $catalog,
        string $warehouseCode,
        int $allocatedQuantity,
        array $governance = [],
    ): int {
        $now = now()->subHours(2);

        return DB::table('supplier_availabilities')->insertGetId(array_merge([
            'product_id' => $catalog['product_id'],
            'catalog_source_id' => $this->sourceId,
            'catalog_distributor_offer_id' => $catalog['offer_id'],
            'warehouse_id' => $this->warehouseIds[$warehouseCode],
            'marketplace_id' => $this->marketplaceId,
            'source_part_id' => 'C1007',
            'supplier_name' => 'Legacy operator record',
            'observed_offer_stock' => 100_000,
            'desired_quantity' => 9_999,
            'total_available_quantity' => 9_999,
            'allocated_quantity' => $allocatedQuantity,
            'allocation_percent' => '1.11',
            'allocation_policy' => 'legacy_or_operator_owned_policy',
            'source_observed_at' => $now,
            'source_name' => 'Legacy source',
            'source_url' => 'https://legacy.example.test',
            'source_file' => 'legacy-source.sqlite3',
            'source_page_url' => 'https://legacy.example.test/C1007',
            'downloaded_at' => $now,
            'imported_at' => $now,
            'data_year' => '2026',
            'license_note' => 'Legacy operator-supplied license note.',
            'confidence_level' => 'legacy_operator_reviewed',
            'original_raw_value' => json_encode(['legacy' => true], JSON_THROW_ON_ERROR),
            'normalized_value' => json_encode(['allocated_quantity' => $allocatedQuantity], JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ], $governance));
    }

    private function seedCommerceContext(): void
    {
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'China',
            'iso_code_2' => 'CN',
            'iso_code_3' => 'CHN',
            'currency_code' => 'USD',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $currencyId = DB::table('currencies')->insertGetId([
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->marketplaceId = DB::table('marketplaces')->insertGetId([
            'name' => 'NeoGiga Global',
            'code' => 'GLOBAL',
            'country_id' => $countryId,
            'currency_id' => $currencyId,
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([
            'NG-SHENZHEN-CN' => 'Shenzhen Central',
            'NG-KATHMANDU-NP' => 'Kathmandu Regional',
            'NG-NEWDELHI-IN' => 'New Delhi Regional',
            'NG-DUBAI-AE' => 'Dubai Regional',
        ] as $code => $name) {
            $this->warehouseIds[$code] = DB::table('warehouses')->insertGetId([
                'marketplace_id' => $this->marketplaceId,
                'name' => $name,
                'code' => $code,
                'country_id' => $countryId,
                'address_line1' => 'Test warehouse address',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->sourceId = DB::table('catalog_sources')->insertGetId([
            'code' => 'jlcpcb_parts_database',
            'name' => 'JLCPCB/LCSC open parts database',
            'source_url' => config('jlcpcb_commerce.source.url'),
            'license_notes' => config('jlcpcb_commerce.source.license_note'),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
