<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAllowedHost;
use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Coverage for the host-spoofing allow-list guard (codex §6): disabled by
 * default (any host passes), and when enabled it allows configured + marketplace
 * domains (with www variants) while rejecting unknown hosts — fail-open.
 */
class HostGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('marketplace:host-allowlist');
    }

    private function pass(string $url): Response
    {
        return (new EnsureAllowedHost())->handle(Request::create($url), fn ($r) => new Response('ok'));
    }

    public function test_disabled_by_default_allows_any_host(): void
    {
        config(['marketplace.host_guard_enabled' => false]);
        $this->assertSame('ok', $this->pass('http://evil.example.com/')->getContent());
    }

    public function test_enabled_allows_configured_host_and_www_variant(): void
    {
        config(['marketplace.host_guard_enabled' => true, 'marketplace.allowed_hosts' => ['neogiga.com'], 'marketplace.always_allow' => []]);
        Cache::forget('marketplace:host-allowlist');

        $this->assertSame('ok', $this->pass('http://neogiga.com/')->getContent());
        $this->assertSame('ok', $this->pass('http://www.neogiga.com/')->getContent());
    }

    public function test_enabled_blocks_unknown_host(): void
    {
        config(['marketplace.host_guard_enabled' => true, 'marketplace.allowed_hosts' => ['neogiga.com'], 'marketplace.always_allow' => []]);
        Cache::forget('marketplace:host-allowlist');

        $this->expectException(NotFoundHttpException::class);
        $this->pass('http://spoofed.attacker.test/');
    }

    public function test_enabled_allows_marketplace_domain_from_db(): void
    {
        $c = Country::firstOrCreate(['iso_code_2' => 'BD'], ['name' => 'Bangladesh', 'iso_code_3' => 'BGD', 'is_active' => true]);
        $cur = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);
        Marketplace::create([
            'name' => 'BD', 'code' => 'BANGLADESH', 'country_id' => $c->id, 'currency_id' => $cur->id,
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'generated_domain' => 'bd.neogiga.com',
        ]);

        config(['marketplace.host_guard_enabled' => true, 'marketplace.allowed_hosts' => [], 'marketplace.always_allow' => []]);
        Cache::forget('marketplace:host-allowlist');

        $this->assertSame('ok', $this->pass('http://bd.neogiga.com/')->getContent());
    }
}
