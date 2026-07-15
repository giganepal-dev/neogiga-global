<?php

namespace Tests\Feature;

use App\Models\Marketplace\Product;
use App\Services\Catalog\JlcpcbQualifiedPublicationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class JlcpcbQualifiedPublicationTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $backupDirectories = [];

    private string $backupRoot;

    private int $sourceId;

    private int $marketplaceId;

    private int $categoryId;

    private int $brandId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePublicationColumnsExist();
        config()->set('jlcpcb_qualified_publication.minimum_data_quality_score', 0.65);
        config()->set('jlcpcb_qualified_publication.batch_size', 1);
        $this->backupRoot = storage_path('framework/testing/jlcpcb-qualified-backups');
        File::ensureDirectoryExists($this->backupRoot);
        config()->set('jlcpcb_qualified_publication.backup_root', $this->backupRoot);
        config()->set('jlcpcb_qualified_publication.plan_root', $this->backupRoot.'/plans');
        $this->seedCatalogContext();
    }

    protected function tearDown(): void
    {
        foreach ($this->backupDirectories as $directory) {
            File::deleteDirectory($directory);
        }
        File::deleteDirectory($this->backupRoot);
        parent::tearDown();
    }

    public function test_dry_run_is_deterministic_and_reports_each_blocker_without_publishing_pending_rows(): void
    {
        $eligible = $this->productFixture('eligible');
        $blocked = $this->productFixture('blocked', withLocalImage: false);
        $service = app(JlcpcbQualifiedPublicationService::class);

        $this->assertFalse(Product::query()->published()->whereKey($eligible)->exists());
        $this->assertFalse(Product::query()->published()->whereKey($blocked)->exists());

        $first = $service->plan();
        $second = $service->plan();

        $this->assertSame(2, $first['source_products']);
        $this->assertSame(1, $first['eligible_products']);
        $this->assertSame(1, $first['blocked_products']);
        $this->assertSame(1, $first['blocker_counts']['missing_active_local_image']);
        $this->assertSame($first['plan_hash'], $second['plan_hash']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first['plan_hash']);

        $this->artisan('catalog:jlcpcb-publish-qualified')
            ->expectsOutputToContain('"eligible_products": 1')
            ->expectsOutputToContain('Dry run only: no products, source reviews, offers, images, datasheets, prices, or UI state were changed.')
            ->assertSuccessful();

        $this->assertSame('pending', DB::table('products')->where('id', $eligible)->value('status'));
        $this->assertSame('pending_review', DB::table('catalog_product_sources')->where('product_id', $eligible)->value('review_status'));
    }

    public function test_apply_publishes_only_the_eligible_row_preserves_manual_visibility_and_replays_as_no_op(): void
    {
        $eligible = $this->productFixture('eligible');
        $blocked = $this->productFixture('blocked', withLocalImage: false);
        $quoteOnly = $this->productFixture('quote-only', visibility: 'quote_only');
        $backup = $this->verifiedBackupDirectory();
        $service = app(JlcpcbQualifiedPublicationService::class);
        $plan = $service->plan();

        $this->assertSame(2, $plan['eligible_products']);
        $result = $service->apply($plan['plan_hash'], $backup, null, 1);

        $this->assertSame('completed', $result['status']);
        $this->assertSame(2, $result['changed_products']);
        $this->assertSame(0, $result['unchanged_products']);
        $this->assertSame(2, $result['batches_committed']);
        $this->assertTrue(Product::query()->published()->whereKey($eligible)->exists());
        $this->assertTrue(Product::query()->published()->whereKey($quoteOnly)->exists());
        $this->assertFalse(Product::query()->published()->whereKey($blocked)->exists());

        $published = DB::table('products')->where('id', $eligible)->first();
        $this->assertSame('approved', $published->status);
        $this->assertSame('approved', $published->approval_status);
        $this->assertSame('marketplace_only', $published->visibility_status);
        $this->assertSame('quote_only', DB::table('products')->where('id', $quoteOnly)->value('visibility_status'));
        $this->assertSame('hidden', DB::table('products')->where('id', $blocked)->value('visibility_status'));
        $this->assertSame('pending_review', DB::table('catalog_product_sources')->where('product_id', $blocked)->value('review_status'));
        $this->assertSame('pending_review', DB::table('catalog_distributor_offers')->where('product_id', $eligible)->where('distributor', 'Mouser')->value('review_status'));

        $metadata = json_decode((string) $published->metadata, true);
        $this->assertTrue((bool) ($metadata['existing']['preserved'] ?? false));
        $audit = $metadata['jlcpcb_qualified_publication_v1'] ?? [];
        $this->assertNotEmpty($audit['source_notes'] ?? null);
        $this->assertNotEmpty($audit['confidence_level'] ?? null);
        $this->assertNotEmpty($audit['last_updated'] ?? null);
        $this->assertSame('Advisory only', $audit['advisory_disclaimer'] ?? null);

        $this->assertFalse((bool) DB::table('product_documents')->where('product_id', $eligible)->value('is_public'));
        $this->assertSame('pending', DB::table('product_documents')->where('product_id', $eligible)->value('status'));
        $this->assertFalse((bool) DB::table('product_images')->where('product_id', $eligible)->where('file_path', 'https://external.example/eligible.jpg')->value('is_active'));
        $this->assertTrue((bool) DB::table('product_images')->where('product_id', $blocked)->where('file_path', 'https://external.example/blocked.jpg')->value('is_active'));

        $replay = $service->apply($plan['plan_hash'], $backup, null, 1);
        $this->assertSame(0, $replay['changed_products']);
        $this->assertSame(2, $replay['unchanged_products']);
        $this->assertSame($plan['plan_hash'], $service->plan()['plan_hash']);
        $this->assertSame(1, DB::table('products')->where('id', $eligible)->whereNotNull('metadata')->count());
    }

    public function test_apply_requires_yes_exact_plan_hash_and_a_verified_untampered_backup(): void
    {
        $productId = $this->productFixture('gated');
        $service = app(JlcpcbQualifiedPublicationService::class);
        $plan = $service->plan();
        $backup = $this->verifiedBackupDirectory();

        $this->artisan('catalog:jlcpcb-publish-qualified', [
            '--apply' => true,
            '--plan-hash' => $plan['plan_hash'],
            '--backup-dir' => $backup,
        ])->expectsOutputToContain('--apply requires --yes.')->assertFailed();

        $this->artisan('catalog:jlcpcb-publish-qualified', [
            '--apply' => true,
            '--yes' => true,
            '--plan-hash' => str_repeat('0', 64),
            '--backup-dir' => $backup,
        ])->expectsOutputToContain('does not match the current qualified-publication plan')->assertFailed();

        $unverified = $this->verifiedBackupDirectory('pending');
        $this->assertApplyFailsWith($service, $plan['plan_hash'], $unverified, 'status=verified');

        file_put_contents($backup.'/catalog.dump', 'tampered after verification');
        $this->assertApplyFailsWith($service, $plan['plan_hash'], $backup, 'checksum verification failed');

        $this->assertSame('pending', DB::table('products')->where('id', $productId)->value('status'));
        $this->assertSame('pending_review', DB::table('catalog_product_sources')->where('product_id', $productId)->value('review_status'));
    }

    public function test_apply_cannot_substitute_a_newly_eligible_product_after_the_read_only_preflight(): void
    {
        $plannedProduct = $this->productFixture('planned-product');
        $blockedProduct = $this->productFixture('blocked-substitute', withLocalImage: false);
        $service = app(JlcpcbQualifiedPublicationService::class);
        $plan = $service->plan();
        $backup = $this->verifiedBackupDirectory();
        $swapped = false;

        $this->assertSame(1, $plan['eligible_products']);
        DB::listen(function ($query) use (&$swapped, $plannedProduct, $blockedProduct): void {
            $sql = strtolower((string) $query->sql);
            if ($swapped || ! str_contains($sql, 'catalog_product_sources') || ! str_contains($sql, 'source_link_id')) {
                return;
            }

            // QueryExecuted fires after the preflight SELECT has captured its
            // rows, simulating a writer racing between preflight and apply.
            $swapped = true;
            DB::table('product_images')->where('product_id', $plannedProduct)->update(['is_active' => false]);
            DB::table('product_images')->where('product_id', $blockedProduct)->update([
                'file_path' => 'images/products/concurrent-substitute.png',
                'is_active' => true,
            ]);
        });

        $this->assertApplyFailsWith(
            $service,
            $plan['plan_hash'],
            $backup,
            'immutable dry-run plan changed during apply',
        );

        $this->assertTrue($swapped);
        $this->assertSame('pending', DB::table('products')->where('id', $plannedProduct)->value('status'));
        $this->assertSame('pending', DB::table('products')->where('id', $blockedProduct)->value('status'));
        $this->assertSame('pending_review', DB::table('catalog_product_sources')->where('product_id', $plannedProduct)->value('review_status'));
        $this->assertSame('pending_review', DB::table('catalog_product_sources')->where('product_id', $blockedProduct)->value('review_status'));
    }

    public function test_apply_rejects_a_planned_fingerprint_mutation_before_any_publication_write(): void
    {
        $productId = $this->productFixture('mutated-fingerprint');
        $service = app(JlcpcbQualifiedPublicationService::class);
        $plan = $service->plan();
        $backup = $this->verifiedBackupDirectory();

        DB::table('catalog_product_sources')->where('product_id', $productId)->update([
            'source_payload_hash' => hash('sha256', 'changed-after-dry-run'),
        ]);

        $this->assertApplyFailsWith(
            $service,
            $plan['plan_hash'],
            $backup,
            'does not match the current qualified-publication plan',
        );

        $this->assertSame('pending', DB::table('products')->where('id', $productId)->value('status'));
        $this->assertSame('pending_review', DB::table('catalog_product_sources')->where('product_id', $productId)->value('review_status'));
        $this->assertSame('pending_review', DB::table('catalog_distributor_offers')->where('product_id', $productId)->where('distributor', 'LCSC/JLCPCB')->value('review_status'));
    }

    public function test_apply_rejects_a_tampered_immutable_dry_run_artifact(): void
    {
        $productId = $this->productFixture('tampered-plan-artifact');
        $service = app(JlcpcbQualifiedPublicationService::class);
        $plan = $service->plan();
        $backup = $this->verifiedBackupDirectory();
        $artifactPath = config('jlcpcb_qualified_publication.plan_root').'/'.$plan['plan_hash'].'.jsonl';
        $entry = json_decode(trim((string) file_get_contents($artifactPath)), true, 512, JSON_THROW_ON_ERROR);
        $entry['fingerprint'] = $entry['fingerprint'] === str_repeat('0', 64)
            ? str_repeat('1', 64)
            : str_repeat('0', 64);
        chmod($artifactPath, 0640);
        file_put_contents($artifactPath, json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR)."\n");
        chmod($artifactPath, 0440);

        $this->assertApplyFailsWith(
            $service,
            $plan['plan_hash'],
            $backup,
            'does not match the supplied plan hash',
        );

        $this->assertSame('pending', DB::table('products')->where('id', $productId)->value('status'));
        $this->assertSame('pending_review', DB::table('catalog_product_sources')->where('product_id', $productId)->value('review_status'));
    }

    public function test_apply_locks_and_rechecks_all_source_links_when_one_is_added_after_preflight(): void
    {
        $productId = $this->productFixture('concurrent-source-link');
        $secondarySourceId = DB::table('catalog_sources')->insertGetId([
            'code' => 'secondary_concurrent_source',
            'name' => 'Secondary concurrent source',
            'source_url' => 'https://secondary.example.test/',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $service = app(JlcpcbQualifiedPublicationService::class);
        $plan = $service->plan();
        $backup = $this->verifiedBackupDirectory();
        $sourceAdded = false;

        DB::listen(function ($query) use (&$sourceAdded, $productId, $secondarySourceId): void {
            $sql = strtolower((string) $query->sql);
            if ($sourceAdded || ! str_contains($sql, 'catalog_product_sources') || ! str_contains($sql, 'source_link_id')) {
                return;
            }

            $sourceAdded = true;
            DB::table('catalog_product_sources')->insert([
                'product_id' => $productId,
                'source_id' => $secondarySourceId,
                'source_part_id' => 'SECONDARY-'.$productId,
                'source_url' => 'https://secondary.example.test/products/'.$productId,
                'source_payload_hash' => hash('sha256', 'secondary-'.$productId),
                'imported_at' => now(),
                'last_synced_at' => now(),
                'data_quality_score' => 0.90,
                'review_status' => 'pending_review',
                'raw_snapshot' => json_encode(['secondary' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->assertApplyFailsWith(
            $service,
            $plan['plan_hash'],
            $backup,
            'immutable dry-run plan changed during apply',
        );

        $this->assertTrue($sourceAdded);
        $this->assertSame(2, DB::table('catalog_product_sources')->where('product_id', $productId)->count());
        $this->assertSame('pending', DB::table('products')->where('id', $productId)->value('status'));
        $this->assertSame(0, DB::table('catalog_product_sources')->where('product_id', $productId)->where('review_status', 'approved')->count());
    }

    private function ensurePublicationColumnsExist(): void
    {
        if (! Schema::hasColumn('products', 'approval_status')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->string('approval_status', 40)->default('draft')->index();
            });
        }
        if (! Schema::hasColumn('products', 'visibility_status')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->string('visibility_status', 40)->default('public')->index();
            });
        }
    }

    private function seedCatalogContext(): void
    {
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Global', 'iso_code_2' => 'GL', 'iso_code_3' => 'GLB', 'currency_code' => 'USD',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $currencyId = DB::table('currencies')->insertGetId([
            'name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$', 'is_active' => true, 'is_default' => true,
            'exchange_rate' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->marketplaceId = DB::table('marketplaces')->insertGetId([
            'name' => 'NeoGiga Global', 'code' => 'GLOBAL', 'country_id' => $countryId, 'currency_id' => $currencyId,
            'timezone' => 'UTC', 'locale' => 'en', 'is_active' => true, 'is_default' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->categoryId = DB::table('product_categories')->insertGetId([
            'name' => 'Integrated Circuits', 'slug' => 'integrated-circuits', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->brandId = DB::table('product_brands')->insertGetId([
            'name' => 'Test Semiconductor', 'slug' => 'test-semiconductor', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->sourceId = DB::table('catalog_sources')->insertGetId([
            'code' => 'jlcpcb_parts_database', 'name' => 'JLCPCB parts database',
            'source_url' => 'https://cdfer.github.io/jlcpcb-parts-database/', 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function productFixture(string $suffix, bool $withLocalImage = true, string $visibility = 'hidden'): int
    {
        $sku = 'NG-JLC-'.strtoupper($suffix);
        $productId = DB::table('products')->insertGetId([
            'name' => 'Qualified '.$suffix.' component',
            'slug' => 'qualified-'.$suffix.'-component',
            'sku' => $sku,
            'mpn' => 'MPN-'.strtoupper($suffix),
            'brand_id' => $this->brandId,
            'category_id' => $this->categoryId,
            'manufacturer_name' => 'Test Semiconductor',
            'description' => 'Complete identity fixture for the governed JLCPCB publication command.',
            'status' => 'pending',
            'approval_status' => 'pending_review',
            'visibility_status' => $visibility,
            'metadata' => json_encode(['existing' => ['preserved' => true]]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('catalog_product_sources')->insert([
            'product_id' => $productId,
            'source_id' => $this->sourceId,
            'source_part_id' => 'C'.str_pad((string) $productId, 6, '0', STR_PAD_LEFT),
            'source_url' => 'https://example.test/source/'.$suffix,
            'source_payload_hash' => hash('sha256', $suffix),
            'imported_at' => now(),
            'last_synced_at' => now(),
            'data_quality_score' => 0.90,
            'review_status' => 'pending_review',
            'raw_snapshot' => json_encode(['lcsc' => $suffix, 'manufacturer' => 'Test Semiconductor']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('catalog_distributor_offers')->insert([
            'product_id' => $productId,
            'distributor' => 'LCSC/JLCPCB',
            'sku' => 'C'.str_pad((string) $productId, 6, '0', STR_PAD_LEFT),
            'price_breaks' => json_encode([['qFrom' => 1, 'price' => 1]]),
            'stock' => 1000,
            'currency' => 'USD',
            'review_status' => 'pending_review',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('catalog_distributor_offers')->insert([
            'product_id' => $productId,
            'distributor' => 'Mouser',
            'sku' => 'MOUSER-'.$productId,
            'currency' => 'USD',
            'review_status' => 'pending_review',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('marketplace_product_prices')->insert([
            'product_id' => $productId,
            'product_variant_id' => null,
            'marketplace_id' => $this->marketplaceId,
            'base_price' => 1.05,
            'currency_code' => 'USD',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($withLocalImage) {
            DB::table('product_images')->insert([
                'product_id' => $productId,
                'file_path' => 'images/products/neogiga-product-placeholder-2026.png',
                'file_name' => 'neogiga-product-placeholder-2026.png',
                'is_primary' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('product_images')->insert([
            'product_id' => $productId,
            'file_path' => 'https://external.example/'.$suffix.'.jpg',
            'is_primary' => false,
            'is_active' => ! $withLocalImage,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_documents')->insert([
            'product_id' => $productId,
            'title' => 'External datasheet',
            'document_type' => 'datasheet',
            'source_url' => 'https://external.example/'.$suffix.'.pdf',
            'status' => 'pending',
            'is_public' => false,
            'metadata' => json_encode(['review_status' => 'pending_review']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $productId;
    }

    private function verifiedBackupDirectory(string $status = 'verified'): string
    {
        $directory = $this->backupRoot.'/backup-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($directory);
        $manifest = json_encode(['status' => $status, 'created_at' => now()->toIso8601String()], JSON_UNESCAPED_SLASHES);
        file_put_contents($directory.'/MANIFEST.txt', $manifest);
        file_put_contents($directory.'/catalog.dump', 'immutable catalog backup fixture');
        file_put_contents($directory.'/RESTORE_VERIFICATION.txt', "verified_at_utc=2026-07-15T00:00:00Z\nresult=passed\n");
        file_put_contents($directory.'/RESTORE_VERIFICATION_COUNTS.tsv', "products\t2\n");
        file_put_contents($directory.'/SHA256SUMS', sprintf(
            "%s  MANIFEST.txt\n%s  catalog.dump\n",
            hash_file('sha256', $directory.'/MANIFEST.txt'),
            hash_file('sha256', $directory.'/catalog.dump'),
        ));
        file_put_contents($directory.'/RESTORE_VERIFICATION_SHA256SUMS', sprintf(
            "%s  RESTORE_VERIFICATION_COUNTS.tsv\n%s  RESTORE_VERIFICATION.txt\n",
            hash_file('sha256', $directory.'/RESTORE_VERIFICATION_COUNTS.tsv'),
            hash_file('sha256', $directory.'/RESTORE_VERIFICATION.txt'),
        ));
        $this->backupDirectories[] = $directory;

        return $directory;
    }

    private function assertApplyFailsWith(
        JlcpcbQualifiedPublicationService $service,
        string $planHash,
        string $backupDirectory,
        string $message,
    ): void {
        try {
            $service->apply($planHash, $backupDirectory, null, 1);
            $this->fail('Expected the governed apply preflight to fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }
}
