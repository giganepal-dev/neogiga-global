<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage for the admin + public marketplace API (codex §8): the admin.token
 * gate is fail-closed, generate-domain previews before saving, generate-seo
 * fills fields, validate-launch + status enforce the launch rules, actions are
 * audited, and the public selector excludes hidden marketplaces.
 */
class MarketplaceAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-admin-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.admin_api_token' => self::TOKEN, 'services.admin_api_token_hash' => null]);
    }

    private function auth(): array
    {
        return ['X-Admin-Token' => self::TOKEN];
    }

    private function marketplace(array $extra = []): Marketplace
    {
        $c = Country::firstOrCreate(['iso_code_2' => 'BD'], ['name' => 'Bangladesh', 'iso_code_3' => 'BGD', 'is_active' => true]);
        $cur = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true, 'exchange_rate' => 1.0]);

        return Marketplace::create(array_merge([
            'name' => 'Bangladesh', 'code' => 'BANGLADESH',
            'country_id' => $c->id, 'currency_id' => $cur->id, 'country_iso2' => 'BD',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'default_language' => 'en',
            'is_active' => false, 'is_visible' => false, 'ssl_status' => 'pending',
        ], $extra));
    }

    public function test_admin_routes_are_fail_closed_without_token(): void
    {
        $this->getJson('/api/v1/admin/marketplaces')->assertStatus(401);
    }

    public function test_index_lists_marketplaces_with_token(): void
    {
        $this->marketplace();
        $this->getJson('/api/v1/admin/marketplaces', $this->auth())
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'domain', 'is_active', 'seo_complete']]]);
    }

    public function test_generate_domain_previews_then_saves_on_confirm(): void
    {
        $m = $this->marketplace();

        // preview: does not save
        $this->postJson("/api/v1/admin/marketplaces/{$m->id}/generate-domain", [], $this->auth())
            ->assertOk()
            ->assertJson(['suggested' => 'bd.neogiga.com', 'saved' => false]);
        $this->assertNull($m->fresh()->generated_domain);

        // confirm: saves + audits
        $this->postJson("/api/v1/admin/marketplaces/{$m->id}/generate-domain", ['confirm' => true], $this->auth())
            ->assertOk()
            ->assertJson(['suggested' => 'bd.neogiga.com', 'saved' => true]);
        $this->assertSame('bd.neogiga.com', $m->fresh()->generated_domain);
        $this->assertSame(1, MarketplaceAuditLog::where('action', 'domain_generated')->count());
    }

    public function test_generate_seo_fills_fields(): void
    {
        $m = $this->marketplace();
        $this->postJson("/api/v1/admin/marketplaces/{$m->id}/generate-seo", ['only_empty' => true], $this->auth())
            ->assertOk();
        $this->assertStringContainsString('Bangladesh', $m->fresh()->seo_title);
    }

    public function test_validate_launch_reports_blocking_checklist(): void
    {
        $m = $this->marketplace();
        $this->postJson("/api/v1/admin/marketplaces/{$m->id}/validate-launch", [], $this->auth())
            ->assertOk()
            ->assertJson(['can_activate' => false]);
    }

    public function test_enable_is_blocked_by_validation_and_disable_requires_reason(): void
    {
        $m = $this->marketplace();

        // enable blocked (unverified domain, no SEO)
        $this->patchJson("/api/v1/admin/marketplaces/{$m->id}/status", ['action' => 'enable'], $this->auth())
            ->assertStatus(422);
        $this->assertFalse((bool) $m->fresh()->is_active);

        // disable requires a reason
        $this->patchJson("/api/v1/admin/marketplaces/{$m->id}/status", ['action' => 'disable'], $this->auth())
            ->assertStatus(422);

        // disable with reason succeeds + audits
        $this->patchJson("/api/v1/admin/marketplaces/{$m->id}/status", ['action' => 'disable', 'reason' => 'Not ready'], $this->auth())
            ->assertOk();
        $this->assertSame(1, MarketplaceAuditLog::where('action', 'marketplace_disabled')->count());
    }

    public function test_audit_history_returns_entries(): void
    {
        $m = $this->marketplace();
        $this->postJson("/api/v1/admin/marketplaces/{$m->id}/generate-seo", [], $this->auth());

        $this->getJson("/api/v1/admin/marketplaces/{$m->id}/audit-history", $this->auth())
            ->assertOk()
            ->assertJsonStructure(['data' => [['action', 'created_at']]]);
    }

    public function test_public_active_list_excludes_hidden(): void
    {
        $this->marketplace(['code' => 'HIDDEN', 'is_active' => true, 'is_visible' => false]);
        $this->marketplace(['code' => 'SHOWN', 'is_active' => true, 'is_visible' => true]);

        $res = $this->getJson('/api/v1/marketplaces/active')->assertOk()->json('data');
        $codes = collect($res)->pluck('code');
        $this->assertContains('SHOWN', $codes);
        $this->assertNotContains('HIDDEN', $codes);
    }
}
