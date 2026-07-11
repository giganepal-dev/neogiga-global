<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceAuditLog;
use App\Services\Marketplace\MarketplaceDomainService;
use App\Services\Marketplace\MarketplaceSeoService;
use Database\Seeders\MarketplaceDomainSeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage for the marketplace domain/SEO configuration system: ISO-based
 * domain generation, custom-domain preservation, duplicate + malformed host
 * rejection, hostname sanitization, SEO default generation, manual-override
 * preservation, inactive-noindex, seeder idempotency, and audit logging.
 */
class MarketplaceDomainSeoTest extends TestCase
{
    use RefreshDatabase;

    private function country(string $iso2, string $iso3, string $name): Country
    {
        return Country::firstOrCreate(['iso_code_2' => $iso2], ['name' => $name, 'iso_code_3' => $iso3, 'is_active' => true]);
    }

    private function currency(string $code, string $symbol): Currency
    {
        return Currency::firstOrCreate(['code' => $code], ['name' => $code, 'symbol' => $symbol, 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
    }

    private function marketplace(string $code, ?Country $c, ?Currency $cur, array $extra = []): Marketplace
    {
        // marketplaces.country_id / currency_id are NOT NULL; fall back to a
        // neutral "world" country + USD for GLOBAL-style rows.
        $c ??= $this->country('XX', 'XXX', 'Worldwide');
        $cur ??= $this->currency('USD', '$');

        return Marketplace::create(array_merge([
            'name' => $code,
            'code' => $code,
            'country_id' => $c->id,
            'currency_id' => $cur->id,
            'timezone' => 'UTC',
            'locale' => 'en',
            'is_active' => false,
        ], $extra));
    }

    private function domains(): MarketplaceDomainService
    {
        return app(MarketplaceDomainService::class);
    }

    public function test_iso_based_domain_generation(): void
    {
        $bd = $this->country('BD', 'BGD', 'Bangladesh');
        $m = $this->marketplace('BANGLADESH', $bd, null, ['country_iso2' => 'BD']);

        $this->assertSame('bd.neogiga.com', $this->domains()->suggestGeneratedDomain($m));
    }

    public function test_global_stays_on_root_domain(): void
    {
        $m = $this->marketplace('GLOBAL', null, null);
        $this->assertSame('neogiga.com', $this->domains()->suggestGeneratedDomain($m));
    }

    public function test_custom_domain_is_preserved_by_seeder(): void
    {
        $np = $this->country('NP', 'NPL', 'Nepal');
        $npr = $this->currency('NPR', 'Rs');
        $nepal = $this->marketplace('NEPAL', $np, $npr, ['domain' => 'giganepal.com', 'domain_mode' => 'custom_domain']);
        $bd = $this->country('BD', 'BGD', 'Bangladesh');
        $this->marketplace('BANGLADESH', $bd, null, ['country_iso2' => 'BD']);

        $this->seed(MarketplaceDomainSeoSeeder::class);

        $nepal->refresh();
        $this->assertSame('giganepal.com', $nepal->domain, 'custom domain must never be overwritten');
        $this->assertTrue((bool) $nepal->is_domain_locked);

        $bangladesh = Marketplace::where('code', 'BANGLADESH')->first();
        $this->assertSame('bd.neogiga.com', $bangladesh->generated_domain);
        $this->assertFalse((bool) $bangladesh->is_active, 'seeding a generated domain must not activate the marketplace');
        $this->assertSame('pending', $bangladesh->ssl_status, 'a generated domain is not verified');
    }

    public function test_duplicate_domain_rejection(): void
    {
        $np = $this->country('NP', 'NPL', 'Nepal');
        $this->marketplace('NEPAL', $np, null, ['domain' => 'giganepal.com']);

        $this->assertTrue($this->domains()->isDuplicateDomain('giganepal.com'));
        $this->assertFalse($this->domains()->isDuplicateDomain('unused.neogiga.com'));
    }

    public function test_hostname_sanitization(): void
    {
        $svc = $this->domains();
        $this->assertSame('bd.neogiga.com', $svc->sanitizeHostname('  HTTPS://BD.neogiga.com/path?x=1  '));
        $this->assertSame('bd.neogiga.com', $svc->sanitizeHostname('bd.neogiga.com:8443'));
    }

    public function test_invalid_hosts_rejected(): void
    {
        $svc = $this->domains();
        $this->assertNull($svc->sanitizeHostname('localhost'));
        $this->assertNull($svc->sanitizeHostname('127.0.0.1'));
        $this->assertNull($svc->sanitizeHostname('*.neogiga.com'));
        $this->assertNull($svc->sanitizeHostname('has spaces.com'));
        $this->assertNull($svc->sanitizeHostname('nodot'));
    }

    public function test_seo_default_generation(): void
    {
        $bd = $this->country('BD', 'BGD', 'Bangladesh');
        $m = $this->marketplace('BANGLADESH', $bd, null, ['country_iso2' => 'BD']);

        app(MarketplaceSeoService::class)->apply($m);
        $m->refresh();

        $this->assertStringContainsString('Bangladesh', $m->seo_title);
        $this->assertStringContainsString('NeoGiga', $m->seo_title);
        $this->assertTrue((bool) $m->seo_is_auto_generated);
        $this->assertNotNull($m->seo_last_generated_at);
    }

    public function test_inactive_marketplace_is_noindex(): void
    {
        $bd = $this->country('BD', 'BGD', 'Bangladesh');
        $m = $this->marketplace('BANGLADESH', $bd, null, ['is_active' => false, 'is_visible' => false, 'indexable' => false]);

        $this->assertSame('noindex,nofollow', app(MarketplaceSeoService::class)->robotsFor($m));

        $m->update(['is_active' => true, 'is_visible' => true, 'indexable' => true]);
        $this->assertSame('index,follow', app(MarketplaceSeoService::class)->robotsFor($m));
    }

    public function test_manual_seo_override_is_preserved(): void
    {
        $bd = $this->country('BD', 'BGD', 'Bangladesh');
        $m = $this->marketplace('BANGLADESH', $bd, null, [
            'country_iso2' => 'BD',
            'seo_title' => 'My Hand-Written Title',
            'seo_manual_override_fields' => ['seo_title'],
        ]);

        app(MarketplaceSeoService::class)->apply($m);
        $m->refresh();

        $this->assertSame('My Hand-Written Title', $m->seo_title, 'manually overridden SEO field must not be regenerated');
        $this->assertStringContainsString('Bangladesh', $m->seo_h1, 'non-overridden fields are still generated');
    }

    public function test_regenerate_only_empty_keeps_existing_content(): void
    {
        $bd = $this->country('BD', 'BGD', 'Bangladesh');
        $m = $this->marketplace('BANGLADESH', $bd, null, ['country_iso2' => 'BD', 'seo_h1' => 'Existing H1']);

        app(MarketplaceSeoService::class)->apply($m, onlyEmpty: true);
        $m->refresh();

        $this->assertSame('Existing H1', $m->seo_h1, 'onlyEmpty must not overwrite an existing value');
        $this->assertNotEmpty($m->seo_title, 'empty fields are still filled');
    }

    public function test_seeder_is_idempotent_and_audits(): void
    {
        $g = $this->marketplace('GLOBAL', null, null);
        $bd = $this->country('BD', 'BGD', 'Bangladesh');
        $this->marketplace('BANGLADESH', $bd, null, ['country_iso2' => 'BD']);

        $this->seed(MarketplaceDomainSeoSeeder::class);
        $firstBd = Marketplace::where('code', 'BANGLADESH')->first()->generated_domain;
        $this->seed(MarketplaceDomainSeoSeeder::class);
        $secondBd = Marketplace::where('code', 'BANGLADESH')->first()->generated_domain;

        $this->assertSame($firstBd, $secondBd, 'seeder must be idempotent');
        $this->assertSame(2, MarketplaceAuditLog::where('action', 'domain_seo_backfill_seeded')->count());
    }
}
