<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductImage;
use App\Models\Marketplace\ProductSeoMeta;
use App\Models\Role;
use App\Models\User;
use App\Services\Seo\CatalogSeoTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductMediaBrandSeoUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_gated_media_workflow_preserves_files_and_exposes_active_images(): void
    {
        Storage::fake('public');
        config(['filesystems.product_images_disk' => 'public']);
        $product = $this->product('Media Test Product');
        $denied = $this->admin([]);

        $this->actingAs($denied)->post("/admin/products/{$product->id}/images", [
            'images' => [UploadedFile::fake()->image('denied.jpg', 640, 480)],
        ])->assertForbidden();
        $this->assertSame(0, ProductImage::where('product_id', $product->id)->count());

        $admin = $this->admin(['catalog.manage'], 'media-admin@example.com');
        $this->actingAs($admin)->post("/admin/products/{$product->id}/images", [
            'images' => [
                UploadedFile::fake()->image('front.jpg', 800, 600),
                UploadedFile::fake()->image('side.png', 900, 700),
            ],
            'alt_text' => 'Media test product view',
            'caption' => 'Approved admin fixture',
            'source_name' => 'NeoGiga QA',
            'source_url' => 'https://example.com/media-source',
            'source_page_url' => 'https://example.com/product-page',
            'confidence_level' => 'admin_verified',
        ])->assertRedirect();

        $images = ProductImage::where('product_id', $product->id)->orderBy('id')->get();
        $this->assertCount(2, $images);
        $this->assertSame(1, $images->where('is_primary', true)->count());
        $this->assertTrue($images->every(fn (ProductImage $image) => $image->is_active));
        $images->each(fn (ProductImage $image) => Storage::disk('public')->assertExists($image->file_path));

        $this->actingAs($admin)->patch("/admin/products/{$product->id}/images/{$images[1]->id}", [
            'alt_text' => 'Updated side view',
            'caption' => 'Updated caption',
            'is_active' => true,
        ])->assertRedirect();
        $this->assertSame('Updated side view', $images[1]->fresh()->alt_text);

        $this->actingAs($admin)->post("/admin/products/{$product->id}/images/{$images[1]->id}/primary")
            ->assertRedirect();
        $this->assertTrue($images[1]->fresh()->is_primary);
        $this->assertSame(1, ProductImage::where('product_id', $product->id)->where('is_primary', true)->count());

        $this->actingAs($admin)->patch("/admin/products/{$product->id}/images/reorder", [
            'image_ids' => [$images[1]->id, $images[0]->id],
        ])->assertRedirect();
        $this->assertSame($images[1]->id, ProductImage::where('product_id', $product->id)->orderBy('sort_order')->value('id'));

        $preservedPath = $images[1]->file_path;
        $this->actingAs($admin)->delete("/admin/products/{$product->id}/images/{$images[1]->id}")
            ->assertRedirect();
        $this->assertFalse($images[1]->fresh()->is_active);
        Storage::disk('public')->assertExists($preservedPath);
        $this->assertTrue($images[0]->fresh()->is_primary);

        $this->getJson('/api/v1/products/'.$product->slug)
            ->assertOk()
            ->assertJsonCount(1, 'data.images')
            ->assertJsonPath('data.images.0.alt_text', 'Media test product view');
        $this->get('/en/products/'.$product->slug)
            ->assertOk()
            ->assertSee('product-gallery-main-image', false)
            ->assertSee('/storage/product-images/'.$product->id.'/', false);

        $this->actingAs($admin)->post("/admin/products/{$product->id}/images", [
            'images' => [UploadedFile::fake()->create('corrupt.jpg', 10, 'image/jpeg')],
        ])->assertSessionHasErrors('images.0');
        $this->assertSame(2, ProductImage::where('product_id', $product->id)->count());
    }

    public function test_brand_directory_canonical_route_and_empty_brand_state_are_public(): void
    {
        $brand = ProductBrand::create([
            'name' => 'Zero Stock Engineering',
            'slug' => 'zero-stock-engineering',
            'description' => 'A valid published engineering brand.',
            'is_active' => true,
            'landing_page_enabled' => true,
            'hide_when_unavailable' => false,
        ]);

        $this->get('/en/brands')
            ->assertOk()
            ->assertSee('/en/brand/'.$brand->slug, false)
            ->assertSee('Zero Stock Engineering');
        $this->get('/en/brand/'.$brand->slug)
            ->assertOk()
            ->assertSee('No public products are currently listed')
            ->assertSee('https://neogiga.com/en/brand/'.$brand->slug, false);
        $this->get('/brands/'.$brand->slug)
            ->assertRedirect('/brand/'.$brand->slug)
            ->assertStatus(301);
        $this->get('/brand/not-a-real-brand')->assertNotFound();
        $this->getJson('/api/v1/brands/'.$brand->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', $brand->slug)
            ->assertJsonPath('data.seo.canonical', 'https://neogiga.com/en/brand/'.$brand->slug);
    }

    public function test_approved_seo_patterns_manual_preservation_dry_run_and_rollback(): void
    {
        $product = $this->product('Precision Sensor');
        $category = $product->category;
        $templates = app(CatalogSeoTemplateService::class);
        $global = $this->marketplace('Global', 'GLOBAL');
        $nepal = $this->marketplace('Nepal', 'NEPAL', [
            'canonical_domain' => 'giganepal.com',
            'seo_marketplace_name' => 'Nepal',
            'has_local_warehouse' => true,
            'warehouse_display_name' => 'Nepal Warehouse',
        ], 'NP');
        $regional = $this->marketplace('Bangladesh', 'BANGLADESH', [
            'seo_marketplace_name' => 'Bangladesh',
            'seo_fulfilment_phrase' => 'Dhaka Fulfilment Hub',
            'url_prefix' => 'bd',
        ], 'BD');

        $globalProduct = $templates->product($product, $global);
        $this->assertSame('Buy Precision Sensor on NeoGiga Global | NeoGiga Engineering Marketplace', $globalProduct['title']);
        $this->assertSame('Buy Precision Sensor on NeoGiga Engineering Marketplace. Low MOQ, Quality Products, B2B Sourcing from Regional Warehouse.', $globalProduct['description']);
        $this->assertSame('https://neogiga.com/en/products/precision-sensor', $globalProduct['canonical']);
        $this->assertSame('index,follow', $globalProduct['robots']);

        $nepalProduct = $templates->product($product, $nepal);
        $this->assertSame('Buy Precision Sensor on NeoGiga Nepal | NeoGiga Engineering Marketplace', $nepalProduct['title']);
        $this->assertStringContainsString('B2B Sourcing from Nepal Warehouse.', $nepalProduct['description']);
        $this->assertSame('https://giganepal.com/en/products/precision-sensor', $nepalProduct['canonical']);

        $regionalCategory = $templates->category($category, $regional);
        $this->assertSame('Buy Engineering Sensors on NeoGiga Bangladesh | NeoGiga Engineering Marketplace', $regionalCategory['title']);
        $this->assertStringContainsString('Dhaka Fulfilment Hub.', $regionalCategory['description']);
        $this->assertSame('https://neogiga.com/bd/en/categories/engineering-sensors', $regionalCategory['canonical']);

        $manual = ProductSeoMeta::create([
            'product_id' => $product->id,
            'title' => 'Approved manual title',
            'meta_title' => 'Approved manual title',
            'meta_description' => 'Approved manual description',
            'canonical_url' => 'https://neogiga.com/en/products/manual-precision-sensor',
            'robots' => 'index,follow',
            'robots_reason' => 'Approved by catalog editor.',
            'is_manual_override' => true,
            'is_locked' => true,
            'active_source' => 'manual',
            'confidence_level' => 'manual_admin_override',
            'metadata' => ['source' => 'manual_admin_override'],
        ]);
        $active = $templates->activeProduct($product->fresh(), $global);
        $this->assertSame('Approved manual title', $active['title']);
        $this->assertSame('manual', $active['active_source']);

        $beforeCounts = [Product::count(), ProductCategory::count(), ProductImage::count()];
        Artisan::call('seo:catalog-regenerate', ['--dry-run' => true, '--marketplace' => 'GLOBAL']);
        $this->assertSame('Approved manual title', $manual->fresh()->meta_title);
        $this->assertSame($beforeCounts, [Product::count(), ProductCategory::count(), ProductImage::count()]);

        $restorePayload = [
            'title' => 'Earlier approved manual title',
            'description' => 'Earlier approved manual description',
            'canonical' => 'https://neogiga.com/en/products/precision-sensor',
            'robots' => 'index,follow',
            'robots_reason' => 'Earlier approved version.',
            'active_source' => 'manual',
            'template_version' => CatalogSeoTemplateService::TEMPLATE_VERSION,
            'confidence_level' => 'manual_admin_override',
        ];
        $templates->recordVersion('product', $product->id, $restorePayload, 'manual_override', null, $global->id);
        $versionId = (int) DB::table('catalog_seo_versions')->where('entity_type', 'product')->where('entity_id', $product->id)->value('id');
        $templates->rollbackVersion('product', $product->id, $versionId);

        $restored = $manual->fresh();
        $this->assertSame('Earlier approved manual title', $restored->meta_title);
        $this->assertTrue($restored->is_manual_override);
        $this->assertTrue($restored->is_locked, 'rollback must not silently unlock an approved manual record');
        $this->assertSame(3, DB::table('catalog_seo_versions')->where('entity_type', 'product')->where('entity_id', $product->id)->count());
    }

    private function product(string $name): Product
    {
        $category = ProductCategory::firstOrCreate(['slug' => 'engineering-sensors'], [
            'name' => 'Engineering Sensors',
            'description' => 'Technical sensors for engineering applications.',
            'is_active' => true,
        ]);

        return Product::create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'sku' => 'NG-'.str($name)->slug('-')->upper()->toString(),
            'mpn' => 'MPN-100',
            'category_id' => $category->id,
            'description' => 'A complete published technical product used for upgrade verification.',
            'status' => 'approved',
            'track_inventory' => true,
            'stock_quantity' => 0,
        ]);
    }

    private function admin(array $permissions, string $email = 'denied-admin@example.com'): User
    {
        $role = Role::firstOrCreate([
            'name' => 'admin',
        ], [
            'display_name' => 'Admin',
            'permissions' => $permissions,
            'is_active' => true,
        ]);
        $role->update(['permissions' => $permissions]);

        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => 'secret-for-tests',
            'role_id' => $role->id,
        ]);
    }

    private function marketplace(string $name, string $code, array $extra = [], ?string $countryCode = null): Marketplace
    {
        $countryCode ??= 'US';
        $country = Country::firstOrCreate([
            'iso_code_2' => $countryCode,
        ], [
            'name' => $name,
            'iso_code_3' => $countryCode.'X',
            'is_active' => true,
        ]);
        $currency = Currency::firstOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'exchange_rate' => 1,
        ]);

        return Marketplace::create(array_merge([
            'name' => $name,
            'code' => $code,
            'country_id' => $country?->id,
            'currency_id' => $currency->id,
            'timezone' => 'UTC',
            'locale' => 'en',
            'is_active' => true,
            'is_visible' => true,
            'indexable' => true,
        ], $extra));
    }
}
