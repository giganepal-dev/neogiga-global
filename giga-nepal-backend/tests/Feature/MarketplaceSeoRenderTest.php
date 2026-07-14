<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Services\Marketplace\MarketplaceSeoRenderer;
use Database\Seeders\MarketplaceLiveAlignmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage for frontend SEO rendering (codex §7): the renderer never regresses
 * a page with no marketplace, emits noindex only for a non-indexable resolved
 * marketplace, and index,follow for an active+visible+indexable one. Also that
 * the live-alignment seeder makes the production custom domains indexable
 * without touching preview marketplaces.
 */
class MarketplaceSeoRenderTest extends TestCase
{
    use RefreshDatabase;

    private function make(array $extra = []): Marketplace
    {
        $c = Country::firstOrCreate(['iso_code_2' => 'BD'], ['name' => 'Bangladesh', 'iso_code_3' => 'BGD', 'is_active' => true]);
        $cur = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);

        return Marketplace::create(array_merge([
            'name' => 'Bangladesh', 'code' => 'BANGLADESH',
            'country_id' => $c->id, 'currency_id' => $cur->id,
            'timezone' => 'Asia/Dhaka', 'locale' => 'en',
            'is_active' => false, 'is_visible' => false, 'indexable' => false,
        ], $extra));
    }

    private function renderer(): MarketplaceSeoRenderer
    {
        return app(MarketplaceSeoRenderer::class);
    }

    public function test_no_marketplace_preserves_legacy_defaults(): void
    {
        $tags = $this->renderer()->tags(null, 'https://neogiga.com/');
        $this->assertSame('index, follow', $tags['robots']);
        $this->assertStringContainsString('NeoGiga', $tags['title']);
        $this->assertSame('https://neogiga.com/', $tags['canonical']);
    }

    public function test_active_visible_indexable_marketplace_is_index_follow(): void
    {
        $m = $this->make([
            'is_active' => true, 'is_visible' => true, 'indexable' => true,
            'seo_title' => 'Components in Bangladesh | NeoGiga',
            'seo_robots' => 'index,follow',
        ]);

        $tags = $this->renderer()->tags($m, 'https://bd.neogiga.com/');
        $this->assertSame('index,follow', $tags['robots']);
        $this->assertSame('Components in Bangladesh | NeoGiga', $tags['title']);
    }

    public function test_marketplace_home_canonical_is_extended_with_the_current_catalog_path(): void
    {
        $m = $this->make([
            'is_active' => true,
            'is_visible' => true,
            'indexable' => true,
            'seo_canonical_url' => 'https://bd.neogiga.com/',
        ]);

        $tags = $this->renderer()->tags($m, 'https://bd.neogiga.com/en/products/example-part?ref=ignored');

        $this->assertSame('https://bd.neogiga.com/en/products/example-part', $tags['canonical']);
    }

    public function test_live_host_override_wins_until_branded_domain_cutover_and_preserves_the_page_path(): void
    {
        $m = $this->make([
            'code' => 'NEPAL',
            'is_active' => true,
            'is_visible' => true,
            'indexable' => true,
            'domain' => 'np.neogiga.com',
            'canonical_domain' => 'giganepal.com',
        ]);

        $tags = $this->renderer()->tags($m, 'https://np.neogiga.com/en/categories/sensors');

        $this->assertSame('https://np.neogiga.com/en/categories/sensors', $tags['canonical']);
    }

    public function test_dedicated_regional_canonical_normalizes_marketplace_prefix_aliases(): void
    {
        $m = $this->make([
            'code' => 'NEPAL',
            'url_prefix' => 'np',
            'is_active' => true,
            'is_visible' => true,
            'indexable' => true,
            'canonical_domain' => 'giganepal.com',
        ]);

        $tags = $this->renderer()->tags($m, 'https://neogiga.com/in/products/example-part?ref=ignored');

        $this->assertSame('https://np.neogiga.com/en/products/example-part', $tags['canonical']);
    }

    public function test_non_indexable_marketplace_is_noindex(): void
    {
        $m = $this->make(['is_active' => true, 'is_visible' => false, 'indexable' => false]);

        $tags = $this->renderer()->tags($m, 'https://bd.neogiga.com/');
        $this->assertSame('noindex,nofollow', $tags['robots']);
    }

    public function test_schema_json_is_passed_through(): void
    {
        $m = $this->make(['seo_schema_json' => ['@context' => 'https://schema.org', '@type' => 'Organization']]);

        $tags = $this->renderer()->tags($m, 'https://bd.neogiga.com/');
        $this->assertStringContainsString('schema.org', $tags['schema_json']);
    }

    public function test_alignment_seeder_makes_custom_domains_indexable_only(): void
    {
        $live = $this->make([
            'code' => 'NEPAL', 'domain' => 'giganepal.com',
            'domain_mode' => 'custom_domain', 'is_domain_locked' => true,
        ]);
        $preview = $this->make([
            'code' => 'PREVIEW', 'generated_domain' => 'bd.neogiga.com',
            'domain_mode' => 'subdomain', 'is_domain_locked' => false,
        ]);

        $this->seed(MarketplaceLiveAlignmentSeeder::class);

        $live->refresh();
        $preview->refresh();
        $this->assertTrue((bool) $live->is_visible);
        $this->assertTrue((bool) $live->indexable);
        $this->assertSame('index,follow', $live->seo_robots);

        $this->assertFalse((bool) $preview->is_visible, 'preview subdomain must remain hidden/non-indexable');
        $this->assertFalse((bool) $preview->indexable);
    }
}
