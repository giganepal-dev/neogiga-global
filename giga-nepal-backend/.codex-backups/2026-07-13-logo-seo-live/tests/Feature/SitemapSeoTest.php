<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
