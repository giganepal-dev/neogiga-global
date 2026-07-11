<?php

namespace Tests\Feature;

use App\Http\Middleware\ForceMarketplaceRecommendationRedirect;
use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceDomain;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\MarketplacePathResolver;
use Database\Seeders\GlobalCommerceMarketplaceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;

/**
 * Global Commerce Stage 1 coverage: marketplace seeding, path-prefix
 * resolution, resolution order, and feature-flagged regional redirect
 * guarantees from GLOBAL_COMMERCE_IMPLEMENTATION_PLAN.md.
 */
class GlobalCommerceMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private const ALL_PREFIXES = ['in', 'np', 'bd', 'lk', 'pk', 'bt', 'mv', 'ae', 'sa', 'qa', 'om', 'kw', 'us', 'ca', 'uk', 'de', 'fr', 'it', 'es', 'nl', 'au', 'nz', 'br', 'za', 'ke'];

    private function seedBaselineAndGlobalCommerce(): void
    {
        $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'native_symbol' => '$', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
        $inr = Currency::firstOrCreate(['code' => 'INR'], ['name' => 'Indian Rupee', 'symbol' => '₹', 'native_symbol' => '₹', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
        $npr = Currency::firstOrCreate(['code' => 'NPR'], ['name' => 'Nepalese Rupee', 'symbol' => 'Rs', 'native_symbol' => 'रू', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
        $globalCountry = Country::firstOrCreate(['iso_code_2' => 'GL'], ['name' => 'Global', 'iso_code_3' => 'GLB', 'currency_code' => 'USD', 'is_active' => true]);
        $in = Country::firstOrCreate(['iso_code_2' => 'IN'], ['name' => 'India', 'iso_code_3' => 'IND', 'currency_code' => 'INR', 'is_active' => true]);
        $np = Country::firstOrCreate(['iso_code_2' => 'NP'], ['name' => 'Nepal', 'iso_code_3' => 'NPL', 'currency_code' => 'NPR', 'is_active' => true]);

        Marketplace::firstOrCreate(['code' => 'GLOBAL'], ['name' => 'NeoGiga Global', 'country_id' => $globalCountry->id, 'currency_id' => $usd->id, 'timezone' => 'UTC', 'locale' => 'en', 'is_active' => true, 'is_default' => true]);
        Marketplace::firstOrCreate(['code' => 'NEPAL'], ['name' => 'GigaNepal', 'country_id' => $np->id, 'currency_id' => $npr->id, 'timezone' => 'Asia/Kathmandu', 'locale' => 'en', 'is_active' => true]);
        Marketplace::firstOrCreate(['code' => 'INDIA'], ['name' => 'NeoGiga India', 'country_id' => $in->id, 'currency_id' => $inr->id, 'timezone' => 'Asia/Kolkata', 'locale' => 'en', 'is_active' => true]);

        $this->seed(GlobalCommerceMarketplaceSeeder::class);
        Cache::flush();

        MarketplaceDomain::firstOrCreate(
            ['domain' => 'giganepal.com'],
            ['marketplace_id' => Marketplace::where('code', 'NEPAL')->value('id'), 'is_primary' => true, 'is_active' => true],
        );
        MarketplaceDomain::firstOrCreate(
            ['domain' => 'neogiga.in'],
            ['marketplace_id' => Marketplace::where('code', 'INDIA')->value('id'), 'is_primary' => true, 'is_active' => true],
        );
        Cache::flush();
    }

    public function test_seeder_creates_all_25_marketplaces_with_unique_prefixes(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        $this->assertSame(26, Marketplace::count()); // 3 existing + 23 new
        $this->assertSame(25, Marketplace::whereNotNull('url_prefix')->count()); // all but GLOBAL
        $this->assertSame(
            25,
            Marketplace::whereNotNull('url_prefix')->distinct('url_prefix')->count('url_prefix'),
            'every url_prefix must be unique'
        );
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seedBaselineAndGlobalCommerce();
        $this->seed(GlobalCommerceMarketplaceSeeder::class);

        $this->assertSame(26, Marketplace::count());
    }

    public function test_existing_nepal_and_india_become_active_with_prefix(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        $nepal = Marketplace::where('code', 'NEPAL')->first();
        $india = Marketplace::where('code', 'INDIA')->first();
        $global = Marketplace::where('code', 'GLOBAL')->first();

        $this->assertSame('np', $nepal->url_prefix);
        $this->assertSame('active', $nepal->launch_status);
        $this->assertTrue($nepal->checkout_enabled);

        $this->assertSame('in', $india->url_prefix);
        $this->assertSame('active', $india->launch_status);

        $this->assertNull($global->url_prefix);
        $this->assertTrue($global->global_fallback);
    }

    public function test_new_countries_are_seeded_as_preview_and_checkout_disabled(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        $bangladesh = Marketplace::where('code', 'BANGLADESH')->first();

        $this->assertNotNull($bangladesh);
        $this->assertSame('preview', $bangladesh->launch_status);
        $this->assertFalse($bangladesh->is_active);
        $this->assertFalse($bangladesh->checkout_enabled);
        $this->assertFalse($bangladesh->redirect_enabled);
    }

    public function test_all_25_prefixes_resolve_to_a_landing_page(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        foreach (self::ALL_PREFIXES as $prefix) {
            $this->get('/' . $prefix)->assertOk();
        }
    }

    public function test_preview_marketplace_landing_shows_coming_soon_not_a_storefront(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        $response = $this->get('/bd');

        $response->assertOk();
        $response->assertSee('Coming soon');
        $response->assertDontSee('Add to Cart', escape: false);
    }

    public function test_active_marketplace_landing_resolves(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        $response = $this->get('/np');

        $response->assertOk();
    }

    public function test_unknown_prefix_returns_404_not_a_redirect(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        $this->get('/xx')->assertNotFound();
    }

    public function test_unsupported_country_leaves_global_routes_unaffected(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        // Global/default routes now canonicalize to the /en storefront.
        $this->get('/')->assertRedirect('/en');
        $this->get('/categories')->assertRedirect('/en/categories');
    }

    public function test_no_route_collision_with_existing_top_level_routes(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        // These are NOT in the 25-prefix whitelist, so the new catch-style
        // route must never intercept them; canonical global redirects still apply.
        $this->get('/products')->assertRedirect('/en/products');
        $this->get('/rfq')->assertRedirect('/en/rfq');
    }

    public function test_path_prefix_resolver_only_matches_active_marketplaces(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        $resolver = app(MarketplacePathResolver::class);

        $activeRequest = Request::create('/np/anything');
        $this->assertNotNull($resolver->resolve($activeRequest));
        $this->assertSame('NEPAL', $resolver->resolve($activeRequest)->code);

        $previewRequest = Request::create('/bd/anything');
        $this->assertNull($resolver->resolve($previewRequest), 'preview marketplaces must not resolve as the governing context');
    }

    public function test_context_resolution_order_prefix_beats_cookie(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        $request = Request::create('/np/products');
        $request->cookies->set(GlobalMarketplaceContextService::PREFERENCE_COOKIE, 'india');

        $context = app(GlobalMarketplaceContextService::class)->context($request);

        $this->assertSame('NEPAL', $context['current']?->code, 'URL prefix must win over the cookie preference');
    }

    public function test_context_resolution_falls_back_to_cookie_preference_without_prefix(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        $request = Request::create('/');
        $request->cookies->set(GlobalMarketplaceContextService::PREFERENCE_COOKIE, 'india');

        $context = app(GlobalMarketplaceContextService::class)->context($request);

        $this->assertSame('INDIA', $context['current']?->code);
    }

    public function test_all_editions_includes_preview_marketplaces_for_the_selector(): void
    {
        $this->seedBaselineAndGlobalCommerce();

        $all = app(GlobalMarketplaceContextService::class)->allEditions();

        $this->assertSame(26, $all->count());
        $this->assertTrue($all->contains(fn ($edition) => $edition['code'] === 'bangladesh' && $edition['launch_status'] === 'preview'));
    }

    public function test_force_marketplace_redirect_is_disabled_by_default(): void
    {
        $this->seedBaselineAndGlobalCommerce();
        config(['neogiga_global.features.geo_recommendation_redirect' => false]);

        $response = $this->passForceRedirect(Request::create('https://neogiga.com/en', 'GET', [], [], [], [
            'HTTP_HOST' => 'neogiga.com',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
            'HTTP_CF_IPCOUNTRY' => 'IN',
        ]));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_force_marketplace_redirect_sends_india_to_regional_homepage(): void
    {
        $this->seedBaselineAndGlobalCommerce();
        config(['neogiga_global.features.geo_recommendation_redirect' => true]);

        $response = $this->passForceRedirect(Request::create('https://neogiga.com/en/products?q=esp32', 'GET', [], [], [], [
            'HTTP_HOST' => 'neogiga.com',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
            'HTTP_CF_IPCOUNTRY' => 'IN',
        ]));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://neogiga.in/?q=esp32', $response->headers->get('Location'));
    }

    public function test_force_marketplace_redirect_skips_crawlers(): void
    {
        $this->seedBaselineAndGlobalCommerce();
        config(['neogiga_global.features.geo_recommendation_redirect' => true]);

        $response = $this->passForceRedirect(Request::create('https://neogiga.com/en', 'GET', [], [], [], [
            'HTTP_HOST' => 'neogiga.com',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
            'HTTP_CF_IPCOUNTRY' => 'IN',
            'HTTP_USER_AGENT' => 'Googlebot/2.1',
        ]));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_force_marketplace_redirect_respects_user_preference_cookie(): void
    {
        $this->seedBaselineAndGlobalCommerce();
        config(['neogiga_global.features.geo_recommendation_redirect' => true]);

        $response = $this->passForceRedirect(Request::create('https://neogiga.com/en', 'GET', [], [
            GlobalMarketplaceContextService::PREFERENCE_COOKIE => 'global',
        ], [], [
            'HTTP_HOST' => 'neogiga.com',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
            'HTTP_CF_IPCOUNTRY' => 'IN',
        ]));

        $this->assertSame(200, $response->getStatusCode());
    }

    private function passForceRedirect(Request $request): SymfonyResponse
    {
        return app(ForceMarketplaceRecommendationRedirect::class)
            ->handle($request, fn () => new Response('ok', 200));
    }
}
