<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceAuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage for the server-rendered marketplace config admin UI (codex §3, §11):
 * the editor loads, tab saves persist + audit, generate-seo works, disable
 * requires a reason, and enable is blocked by validation — all behind admin.web.
 */
class MarketplaceAdminUiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'super_admin'): User
    {
        $r = Role::firstOrCreate(['name' => $role], ['display_name' => $role, 'is_active' => true]);

        return User::create([
            'name' => 'Admin', 'email' => $role . '@example.com',
            'password' => bcrypt('secret'), 'role_id' => $r->id,
        ]);
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

    public function test_config_page_requires_admin(): void
    {
        $m = $this->marketplace();
        $this->get("/admin/marketplaces/{$m->id}/config")->assertRedirect('/admin/login');
    }

    public function test_editor_loads_for_admin(): void
    {
        $m = $this->marketplace();
        $this->actingAs($this->admin())
            ->get("/admin/marketplaces/{$m->id}/config")
            ->assertOk()
            ->assertSee('Domain & Routing')
            ->assertSee('Pre-launch checklist');
    }

    public function test_general_tab_save_persists_and_audits(): void
    {
        $m = $this->marketplace();
        $this->actingAs($this->admin())
            ->post("/admin/marketplaces/{$m->id}/config", ['tab' => 'general', 'name' => 'Bangladesh Store', 'timezone' => 'Asia/Dhaka'])
            ->assertRedirect();

        $this->assertSame('Bangladesh Store', $m->fresh()->name);
        $this->assertSame(1, MarketplaceAuditLog::where('action', 'config_general_updated')->count());
    }

    public function test_generate_seo_fills_title(): void
    {
        $m = $this->marketplace();
        $this->actingAs($this->admin())
            ->post("/admin/marketplaces/{$m->id}/generate-seo", [])
            ->assertRedirect();

        $this->assertStringContainsString('Bangladesh', $m->fresh()->seo_title);
    }

    public function test_disable_requires_reason(): void
    {
        $m = $this->marketplace(['is_active' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post("/admin/marketplaces/{$m->id}/disable", [])
            ->assertSessionHasErrors('reason');
        $this->assertTrue((bool) $m->fresh()->is_active, 'no-reason disable must not disable');

        $this->actingAs($admin)
            ->post("/admin/marketplaces/{$m->id}/disable", ['reason' => 'Compliance'])
            ->assertRedirect();
        $this->assertFalse((bool) $m->fresh()->is_active);
    }

    public function test_enable_blocked_by_validation(): void
    {
        $m = $this->marketplace(); // unverified domain, no SEO
        $this->actingAs($this->admin('admin')) // non-super-admin cannot force
            ->post("/admin/marketplaces/{$m->id}/enable", [])
            ->assertSessionHasErrors('enable');
        $this->assertFalse((bool) $m->fresh()->is_active);
    }

    public function test_list_search_filters_results(): void
    {
        $this->marketplace(['code' => 'ALPHA', 'name' => 'Alpha Store']);
        $this->marketplace(['code' => 'BETA', 'name' => 'Beta Store']);

        $this->actingAs($this->admin())
            ->get('/admin/marketplaces?q=Alpha')
            ->assertOk()
            ->assertSee('Alpha Store')
            ->assertDontSee('Beta Store');
    }

    public function test_list_missing_seo_filter(): void
    {
        $withSeo = $this->marketplace(['code' => 'HASSEO', 'name' => 'Has SEO', 'seo_title' => 'T', 'seo_description' => 'D']);
        $noSeo = $this->marketplace(['code' => 'NOSEO', 'name' => 'No SEO']);

        $this->actingAs($this->admin())
            ->get('/admin/marketplaces?missing_seo=1')
            ->assertOk()
            ->assertSee('No SEO')
            ->assertDontSee('Has SEO');
    }

    public function test_bulk_set_visible(): void
    {
        $a = $this->marketplace(['code' => 'A', 'is_visible' => false]);
        $b = $this->marketplace(['code' => 'B', 'is_visible' => false]);

        $this->actingAs($this->admin())
            ->post('/admin/marketplaces/bulk', ['action' => 'set_visible', 'ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertTrue((bool) $a->fresh()->is_visible);
        $this->assertTrue((bool) $b->fresh()->is_visible);
    }

    public function test_bulk_generate_default_seo(): void
    {
        $m = $this->marketplace(['code' => 'C']);
        $this->actingAs($this->admin())
            ->post('/admin/marketplaces/bulk', ['action' => 'generate_default_seo', 'ids' => [$m->id]])
            ->assertRedirect();

        $this->assertNotEmpty($m->fresh()->seo_title);
    }

    public function test_bulk_disable_requires_reason(): void
    {
        $m = $this->marketplace(['is_active' => true]);
        $this->actingAs($this->admin())
            ->post('/admin/marketplaces/bulk', ['action' => 'disable', 'ids' => [$m->id]])
            ->assertSessionHasErrors('reason');
        $this->assertTrue((bool) $m->fresh()->is_active);
    }
}
