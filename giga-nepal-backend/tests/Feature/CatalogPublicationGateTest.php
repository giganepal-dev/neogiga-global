<?php

namespace Tests\Feature;

use App\Models\Marketplace\Product;
use App\Services\Product\ProductVisibilityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogPublicationGateTest extends TestCase
{
    use RefreshDatabase;

    private Product $pendingProduct;

    private Product $approvedProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensurePublicationColumnsExist();

        $sourceId = DB::table('catalog_sources')->insertGetId([
            'code' => 'publication-gate-fixture',
            'name' => 'Publication gate fixture',
            'source_url' => 'https://example.com/catalog',
            'license_notes' => 'Test fixture only.',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->pendingProduct = $this->product(
            'Pending Source Product',
            'pending-source-product',
            'NG-PENDING-SOURCE',
            'pending_review',
        );
        $this->approvedProduct = $this->product(
            'Approved Source Product',
            'approved-source-product',
            'NG-APPROVED-SOURCE',
            'approved',
        );

        $this->sourceLink($sourceId, $this->pendingProduct, 'pending_review');
        $this->sourceLink($sourceId, $this->approvedProduct, 'approved');

        Cache::flush();
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

    public function test_published_scope_excludes_product_pending_catalog_source_approval(): void
    {
        $this->assertFalse(
            Product::query()->published()->whereKey($this->pendingProduct->id)->exists(),
            'An imported product must not satisfy Product::published() until both product and source approvals are complete.',
        );
    }

    public function test_public_product_service_excludes_product_pending_catalog_source_approval(): void
    {
        $this->assertFalse(
            app(ProductVisibilityService::class)->publicProducts()->where('id', $this->pendingProduct->id)->exists(),
            'The public catalog query must exclude an imported product whose product/source review remains pending.',
        );
    }

    public function test_public_product_detail_returns_not_found_while_catalog_source_is_unapproved(): void
    {
        $this->get('/en/products/'.$this->pendingProduct->slug)->assertNotFound();
    }

    public function test_product_sitemap_excludes_product_while_catalog_source_is_unapproved(): void
    {
        $this->get('/sitemaps/products-1.xml')
            ->assertOk()
            ->assertDontSee('/en/products/'.$this->pendingProduct->slug.'</loc>', false);
    }

    public function test_virtual_manufacturer_sitemap_uses_the_same_import_publication_gate(): void
    {
        DB::table('products')->where('id', $this->pendingProduct->id)->update(['manufacturer_name' => 'Pending Devices']);
        DB::table('products')->where('id', $this->approvedProduct->id)->update(['manufacturer_name' => 'Approved Devices']);
        Cache::flush();

        $this->get('/sitemaps/manufacturers-1.xml')
            ->assertOk()
            ->assertSee('/en/manufacturer/approved-devices</loc>', false)
            ->assertDontSee('/en/manufacturer/pending-devices</loc>', false);
    }

    public function test_approved_product_with_approved_source_remains_public_and_indexed(): void
    {
        $this->assertTrue(Product::query()->published()->whereKey($this->approvedProduct->id)->exists());
        $this->assertTrue(
            app(ProductVisibilityService::class)->publicProducts()->where('id', $this->approvedProduct->id)->exists(),
        );

        $this->get('/en/products/'.$this->approvedProduct->slug)->assertOk();
        $this->get('/sitemaps/products-1.xml')
            ->assertOk()
            ->assertSee('/en/products/'.$this->approvedProduct->slug.'</loc>', false);
    }

    public function test_product_and_source_approvals_are_independent_required_gates(): void
    {
        DB::table('products')->where('id', $this->pendingProduct->id)->update([
            'approval_status' => 'approved',
        ]);
        $this->assertFalse(Product::query()->published()->whereKey($this->pendingProduct->id)->exists());

        DB::table('products')->where('id', $this->pendingProduct->id)->update([
            'approval_status' => 'pending_review',
        ]);
        DB::table('catalog_product_sources')->where('product_id', $this->pendingProduct->id)->update([
            'review_status' => 'approved',
        ]);
        $this->assertFalse(Product::query()->published()->whereKey($this->pendingProduct->id)->exists());

        DB::table('products')->where('id', $this->pendingProduct->id)->update([
            'approval_status' => 'approved',
        ]);
        $this->assertTrue(Product::query()->published()->whereKey($this->pendingProduct->id)->exists());
    }

    public function test_public_search_inventory_pos_and_review_routes_exclude_pending_import(): void
    {
        $this->get('/en/products?q=Pending+Source')
            ->assertOk()
            ->assertDontSee($this->pendingProduct->name);

        $this->getJson('/api/v1/products/search?q=Pending%20Source')
            ->assertOk()
            ->assertJsonMissing(['id' => $this->pendingProduct->id]);

        $this->getJson('/api/v1/pos/products/search?q=Pending')
            ->assertOk()
            ->assertJsonMissing(['id' => $this->pendingProduct->id]);

        $this->getJson('/api/v1/inventory/product/'.$this->pendingProduct->id)->assertNotFound();
        $this->getJson('/api/v1/products/'.$this->pendingProduct->id.'/reviews')->assertNotFound();
    }

    public function test_existing_manual_product_without_source_link_keeps_legacy_publication_path(): void
    {
        $manual = $this->product(
            'Existing Manual Product',
            'existing-manual-product',
            'NG-EXISTING-MANUAL',
            'pending_review',
        );

        $this->assertTrue(Product::query()->published()->whereKey($manual->id)->exists());
        $this->get('/en/products/'.$manual->slug)->assertOk();
    }

    private function product(
        string $name,
        string $slug,
        string $sku,
        string $approvalStatus,
    ): Product {
        $product = Product::create([
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'mpn' => $sku.'-MPN',
            'description' => 'A complete catalog product used to verify publication approval boundaries.',
            'status' => 'approved',
            'track_inventory' => true,
            'stock_quantity' => 10,
        ]);

        DB::table('products')->where('id', $product->id)->update([
            'approval_status' => $approvalStatus,
            'visibility_status' => 'public',
            'updated_at' => now(),
        ]);

        return $product->refresh();
    }

    private function sourceLink(int $sourceId, Product $product, string $reviewStatus): void
    {
        DB::table('catalog_product_sources')->insert([
            'product_id' => $product->id,
            'source_id' => $sourceId,
            'source_part_id' => 'source-'.$product->id,
            'source_url' => 'https://example.com/catalog/'.$product->slug,
            'source_payload_hash' => hash('sha256', $product->slug),
            'imported_at' => now(),
            'last_synced_at' => now(),
            'data_quality_score' => 100,
            'review_status' => $reviewStatus,
            'raw_snapshot' => json_encode(['slug' => $product->slug]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
