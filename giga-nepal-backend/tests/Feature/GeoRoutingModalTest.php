<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceDomain;
use App\Models\User;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use Database\Seeders\GlobalCommerceMarketplaceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Geo-routing suggestion modal: the client-side, SEO-safe replacement for a
 * server IP redirect. Covers the spec's decision table — who is nudged, in
 * which mode, and which paths/visitors are exempt.
 */
class GeoRoutingModalTest extends TestCase
{
    use RefreshDatabase;

    private function seedMarketplaces(): void
    {
        $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'native_symbol' => '$', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
        $inr = Currency::firstOrCreate(['code' => 'INR'], ['name' => 'Indian Rupee', 'symbol' => '₹', 'native_symbol' => '₹', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
        $npr = Currency::firstOrCreate(['code' => 'NPR'], ['name' => 'Nepalese Rupee', 'symbol' => 'Rs', 'native_symbol' => 'रू', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
        $gl = Country::firstOrCreate(['iso_code_2' => 'GL'], ['name' => 'Global', 'iso_code_3' => 'GLB', 'currency_code' => 'USD', 'is_active' => true]);
        $in = Country::firstOrCreate(['iso_code_2' => 'IN'], ['name' => 'India', 'iso_code_3' => 'IND', 'currency_code' => 'INR', 'is_active' => true]);
        $np = Country::firstOrCreate(['iso_code_2' => 'NP'], ['name' => 'Nepal', 'iso_code_3' => 'NPL', 'currency_code' => 'NPR', 'is_active' => true]);

        Marketplace::firstOrCreate(['code' => 'GLOBAL'], ['name' => 'NeoGiga Global', 'country_id' => $gl->id, 'currency_id' => $usd->id, 'timezone' => 'UTC', 'locale' => 'en', 'is_active' => true, 'is_default' => true]);
        Marketplace::firstOrCreate(['code' => 'NEPAL'], ['name' => 'GigaNepal', 'country_id' => $np->id, 'currency_id' => $npr->id, 'timezone' => 'Asia/Kathmandu', 'locale' => 'en', 'is_active' => true]);
        Marketplace::firstOrCreate(['code' => 'INDIA'], ['name' => 'NeoGiga India', 'country_id' => $in->id, 'currency_id' => $inr->id, 'timezone' => 'Asia/Kolkata', 'locale' => 'en', 'is_active' => true]);

        $this->seed(GlobalCommerceMarketplaceSeeder::class);

        MarketplaceDomain::firstOrCreate(['domain' => 'giganepal.com'], ['marketplace_id' => Marketplace::where('code', 'NEPAL')->value('id'), 'is_primary' => true, 'is_active' => true]);
        MarketplaceDomain::firstOrCreate(['domain' => 'neogiga.in'], ['marketplace_id' => Marketplace::where('code', 'INDIA')->value('id'), 'is_primary' => true, 'is_active' => true]);
        Cache::flush();
    }

    private function context(string $url, array $server = [], array $cookies = []): array
    {
        $request = Request::create($url, 'GET', [], $cookies, [], array_merge([
            'HTTP_HOST' => parse_url($url, PHP_URL_HOST),
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
        ], $server));

        return app(GlobalMarketplaceContextService::class)->context($request);
    }

    public function test_excluded_paths_match_raw_and_prefixed(): void
    {
        $svc = app(GlobalMarketplaceContextService::class);

        foreach (['/admin', '/api/v1/x', '/checkout', '/payment/callback', '/order/9', '/en/rfq', '/np/checkout', '/en/bom', '/webhook/stripe', '/status'] as $path) {
            $this->assertTrue($svc->isExcludedPath($path), "$path must be excluded");
        }

        foreach (['/', '/en', '/en/products', '/products', '/en/categories/robotics'] as $path) {
            $this->assertFalse($svc->isExcludedPath($path), "$path must NOT be excluded");
        }
    }

    public function test_nepal_visitor_on_global_gets_the_modal(): void
    {
        $this->seedMarketplaces();

        $ctx = $this->context('https://neogiga.com/en', ['HTTP_CF_IPCOUNTRY' => 'NP']);

        $this->assertTrue($ctx['show_recommendation']);
        $this->assertSame('modal', $ctx['recommendation_mode']);
        $this->assertSame('nepal', $ctx['recommended']['code']);
    }

    public function test_crawler_is_never_recommended(): void
    {
        $this->seedMarketplaces();

        $ctx = $this->context('https://neogiga.com/en', ['HTTP_CF_IPCOUNTRY' => 'NP', 'HTTP_USER_AGENT' => 'Googlebot/2.1']);

        $this->assertFalse($ctx['show_recommendation']);
    }

    public function test_excluded_path_suppresses_the_modal(): void
    {
        $this->seedMarketplaces();

        $ctx = $this->context('https://neogiga.com/en/rfq', ['HTTP_CF_IPCOUNTRY' => 'NP']);

        $this->assertFalse($ctx['show_recommendation']);
    }

    public function test_country_without_an_active_regional_edition_stays_global(): void
    {
        $this->seedMarketplaces();

        // ZZ has no marketplace; the visitor stays on Global with no nudge.
        $ctx = $this->context('https://neogiga.com/en', ['HTTP_CF_IPCOUNTRY' => 'ZZ']);

        $this->assertFalse($ctx['show_recommendation']);
        $this->assertNull($ctx['recommendation_mode']);
    }

    public function test_seen_cookie_suppresses_the_modal(): void
    {
        $this->seedMarketplaces();

        $ctx = $this->context('https://neogiga.com/en', ['HTTP_CF_IPCOUNTRY' => 'NP'], [
            GlobalMarketplaceContextService::SEEN_COOKIE => '1',
        ]);

        $this->assertFalse($ctx['show_recommendation']);
    }

    public function test_saved_preference_is_respected(): void
    {
        $this->seedMarketplaces();

        $ctx = $this->context('https://neogiga.com/en', ['HTTP_CF_IPCOUNTRY' => 'NP'], [
            GlobalMarketplaceContextService::PREFERENCE_COOKIE => 'global',
        ]);

        $this->assertFalse($ctx['show_recommendation'], 'a manually chosen marketplace is never overridden');
    }

    public function test_visitor_on_a_different_regional_site_gets_a_soft_notice(): void
    {
        $this->seedMarketplaces();

        // On the India edition, detected as Nepal → optional notice, NOT a modal.
        $ctx = $this->context('https://neogiga.in/en', ['HTTP_CF_IPCOUNTRY' => 'NP']);

        $this->assertSame('INDIA', $ctx['current']?->code);
        $this->assertTrue($ctx['show_recommendation']);
        $this->assertSame('notice', $ctx['recommendation_mode']);
    }

    public function test_direct_regional_visitor_is_not_nudged_back(): void
    {
        $this->seedMarketplaces();

        // A Nepal visitor already on the Nepal edition sees nothing.
        $ctx = $this->context('https://giganepal.com/en', ['HTTP_CF_IPCOUNTRY' => 'NP']);

        $this->assertSame('NEPAL', $ctx['current']?->code);
        $this->assertFalse($ctx['show_recommendation']);
    }

    public function test_logged_in_visitor_gets_a_notice_not_an_auto_redirect(): void
    {
        $this->seedMarketplaces();

        $request = Request::create('https://neogiga.com/en', 'GET', [], [], [], [
            'HTTP_HOST' => 'neogiga.com',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
            'HTTP_CF_IPCOUNTRY' => 'NP',
        ]);
        $request->setUserResolver(fn () => new User);

        $ctx = app(GlobalMarketplaceContextService::class)->context($request);

        $this->assertTrue($ctx['show_recommendation']);
        $this->assertSame('notice', $ctx['recommendation_mode'], 'logged-in users are never auto-redirected');
    }

    public function test_modal_renders_end_to_end_for_a_nepal_visitor(): void
    {
        $this->seedMarketplaces();

        $response = $this->get('https://neogiga.com/en', ['CF-IPCountry' => 'NP']);

        $response->assertOk();
        $response->assertSee('ng-geo-overlay', false);
        $response->assertSee('aria-modal="true"', false);
        $response->assertSee('Go to GigaNepal', false);
    }
}
