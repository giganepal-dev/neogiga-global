<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceAuditLog;
use App\Services\Marketplace\MarketplaceLaunchValidator;
use App\Services\Marketplace\MarketplaceStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Coverage for marketplace enable/disable + pre-launch validation (codex §4):
 * critical validation blocks activation, a fully-configured marketplace passes,
 * Super-Admin force overrides, disable requires a reason and turns off
 * registrations/checkout while preserving data, and both audit.
 */
class MarketplaceStatusTest extends TestCase
{
    use RefreshDatabase;

    private function base(array $extra = []): Marketplace
    {
        $c = Country::firstOrCreate(['iso_code_2' => 'BD'], ['name' => 'Bangladesh', 'iso_code_3' => 'BGD', 'is_active' => true]);
        $cur = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);

        return Marketplace::create(array_merge([
            'name' => 'Bangladesh', 'code' => 'BANGLADESH',
            'country_id' => $c->id, 'currency_id' => $cur->id,
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'default_language' => 'en',
            'is_active' => false, 'is_visible' => false,
            'generated_domain' => 'bd.neogiga.com',
            'ssl_status' => 'pending',
        ], $extra));
    }

    /** A marketplace that passes every critical check. */
    private function launchable(array $extra = []): Marketplace
    {
        return $this->base(array_merge([
            'domain' => 'bd.neogiga.com',
            'ssl_status' => 'active',
            'seo_title' => 'Components in Bangladesh | NeoGiga',
            'seo_description' => 'Buy components in Bangladesh through NeoGiga.',
            'seo_canonical_url' => 'https://bd.neogiga.com/',
        ], $extra));
    }

    private function statusService(): MarketplaceStatusService
    {
        return app(MarketplaceStatusService::class);
    }

    public function test_fresh_preview_marketplace_fails_validation(): void
    {
        $m = $this->base(); // no SEO, ssl pending, unverified
        $result = app(MarketplaceLaunchValidator::class)->validate($m);

        $this->assertFalse($result['can_activate']);
        $failedKeys = collect($result['checklist'])->where('passed', false)->pluck('key');
        $this->assertContains('domain_verified', $failedKeys);
        $this->assertContains('seo_title', $failedKeys);
    }

    public function test_enable_is_blocked_when_critical_validation_fails(): void
    {
        $m = $this->base();
        $result = $this->statusService()->enable($m);

        $this->assertFalse($result['ok']);
        $m->refresh();
        $this->assertFalse((bool) $m->is_active, 'marketplace must NOT be activated when critical validation fails');
    }

    public function test_fully_configured_marketplace_can_be_enabled(): void
    {
        $m = $this->launchable();
        $result = $this->statusService()->enable($m, userId: 7);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['can_activate']);
        $m->refresh();
        $this->assertTrue((bool) $m->is_active);
        $this->assertTrue((bool) $m->is_visible);
        $this->assertSame(1, MarketplaceAuditLog::where('action', 'marketplace_enabled')->count());
    }

    public function test_super_admin_can_force_enable_despite_warnings(): void
    {
        $m = $this->base(); // fails validation

        $blocked = $this->statusService()->enable($m, force: true, isSuperAdmin: false);
        $this->assertFalse($blocked['ok'], 'force without super-admin must still be blocked');
        $m->refresh();
        $this->assertFalse((bool) $m->is_active);

        $forced = $this->statusService()->enable($m, force: true, isSuperAdmin: true, userId: 1);
        $this->assertTrue($forced['ok']);
        $m->refresh();
        $this->assertTrue((bool) $m->is_active);
        $this->assertSame(1, MarketplaceAuditLog::where('action', 'marketplace_force_enabled')->count());
    }

    public function test_disable_requires_a_reason(): void
    {
        $m = $this->launchable(['is_active' => true]);
        $this->expectException(InvalidArgumentException::class);
        $this->statusService()->disable($m, '   ');
    }

    public function test_disable_turns_off_commerce_and_preserves_data(): void
    {
        $m = $this->launchable(['is_active' => true, 'is_visible' => true, 'checkout_enabled' => true, 'allow_customer_registration' => true]);
        $before = $m->id;

        $this->statusService()->disable($m, 'Compliance review', userId: 9);
        $m->refresh();

        $this->assertFalse((bool) $m->is_active);
        $this->assertFalse((bool) $m->checkout_enabled);
        $this->assertFalse((bool) $m->allow_customer_registration);
        $this->assertFalse((bool) $m->allow_vendor_registration);
        $this->assertNotNull($m->disabled_at);
        $this->assertSame('Compliance review', $m->disabled_reason);
        $this->assertNotNull(Marketplace::find($before), 'disable must NOT delete the marketplace');
        $this->assertSame(1, MarketplaceAuditLog::where('action', 'marketplace_disabled')->count());
    }
}
