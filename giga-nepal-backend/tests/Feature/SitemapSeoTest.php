<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SitemapSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_sitemap_is_a_sharded_sitemap_index(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false);
        $response->assertSee('/sitemaps/pages-1.xml', false);
    }

    public function test_pages_sitemap_includes_catalog_hubs_without_fake_last_modified_dates(): void
    {
        $response = $this->get('/sitemaps/pages-1.xml');

        $response->assertOk();
        $response->assertSee('/en/products</loc>', false);
        $response->assertSee('/en/categories</loc>', false);
        $response->assertDontSee('<lastmod>', false);
    }

    public function test_unknown_sitemap_sections_are_not_published(): void
    {
        $this->get('/sitemaps/private-1.xml')->assertNotFound();
    }

    public function test_catalog_sitemaps_cover_brands_manufacturers_and_nested_categories(): void
    {
        $parentId = DB::table('product_categories')->insertGetId([
            'name' => 'Components', 'slug' => 'components', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('product_categories')->insert([
            'parent_id' => $parentId, 'name' => 'Semiconductors', 'slug' => 'semiconductors', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('product_brands')->insert([
            'name' => 'Acme', 'slug' => 'acme', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('manufacturers')->insert([
            'name' => 'Acme Components', 'slug' => 'acme-components', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/sitemaps/categories-1.xml', false)
            ->assertSee('/sitemaps/brands-1.xml', false)
            ->assertSee('/sitemaps/manufacturers-1.xml', false);

        $this->get('/sitemaps/categories-1.xml')
            ->assertOk()
            ->assertSee('/en/categories/components</loc>', false)
            ->assertSee('/en/categories/semiconductors</loc>', false);
        $this->get('/sitemaps/brands-1.xml')->assertOk()->assertSee('/en/brand/acme</loc>', false);
        $this->get('/sitemaps/manufacturers-1.xml')->assertOk()->assertSee('/en/manufacturer/acme-components</loc>', false);
    }

    public function test_manufacturer_sitemap_merges_public_product_identities_without_duplicate_urls(): void
    {
        DB::table('manufacturers')->insert([
            'name' => 'Acme Components', 'slug' => 'acme-components', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('products')->insert([
            [
                'name' => 'Acme Existing Identity Part', 'slug' => 'acme-existing-identity-part', 'sku' => 'NG-ACME-IDENTITY',
                'manufacturer_name' => 'Acme Components', 'status' => 'approved', 'visibility_status' => 'public',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Virtual Devices Part', 'slug' => 'virtual-devices-part', 'sku' => 'NG-VIRTUAL-DEVICES',
                'manufacturer_name' => 'Virtual Devices', 'status' => 'approved', 'visibility_status' => 'public',
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
        Cache::flush();

        $response = $this->get('/sitemaps/manufacturers-1.xml')->assertOk();
        $response->assertSee('/en/manufacturer/acme-components</loc>', false)
            ->assertSee('/en/manufacturer/virtual-devices</loc>', false);
        $this->assertSame(1, substr_count($response->getContent(), '/en/manufacturer/acme-components</loc>'));
        $this->assertSame(1, substr_count($response->getContent(), '/en/manufacturer/virtual-devices</loc>'));
    }

    public function test_new_neogiga_icon_and_placeholder_assets_exist(): void
    {
        $this->assertFileExists(public_path('images/brand/neogiga-favicon-32.png'));
        $this->assertFileExists(public_path('images/brand/neogiga-icon-192.png'));
        $this->assertFileExists(public_path('images/products/neogiga-product-placeholder-2026.png'));
        $this->assertFileExists(public_path('images/og/neogiga-default-2026.png'));
    }

    public function test_categories_without_media_use_the_global_neogiga_fallback(): void
    {
        $rootId = DB::table('product_categories')->insertGetId([
            'name' => 'Semiconductors',
            'slug' => 'semiconductors',
            'is_active' => true,
            'image_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_categories')->insert([
            'name' => 'Fallback Category',
            'slug' => 'fallback-category',
            'parent_id' => $rootId,
            'is_active' => true,
            'image_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/en/categories')
            ->assertOk()
            ->assertSee('/images/brand/neogiga-icon-512.png', false)
            ->assertSee('"@type":"ItemList"', false);

        $this->get('/en/categories/fallback-category')
            ->assertOk()
            ->assertSee('/images/brand/neogiga-icon-512.png', false)
            ->assertSee('Fallback Category category');
    }
}
