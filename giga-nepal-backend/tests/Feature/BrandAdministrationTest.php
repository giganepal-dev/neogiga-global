<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\Role;
use App\Models\User;
use App\Services\Catalog\BrandVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_configured_brand(): void
    {
        $country = $this->country();
        $marketplace = $this->marketplace($country);

        $this->actingAs($this->admin())
            ->post('/admin/brands', [
                'name' => 'Neo Components',
                'slug' => 'neo-components',
                'marketplace_ids' => [$marketplace->id],
                'country_ids' => [$country->id],
                'is_active' => 1,
                'is_menu_visible' => 1,
                'display_desktop' => 1,
                'display_mobile' => 1,
                'landing_page_enabled' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_brands', ['slug' => 'neo-components', 'is_menu_visible' => true]);
    }

    public function test_brand_menu_honors_marketplace_country_and_product_availability_rules(): void
    {
        $india = $this->country('India', 'IN', 'IND');
        $nepal = $this->country('Nepal', 'NP', 'NPL');
        $indiaMarketplace = $this->marketplace($india, 'INDIA');
        $nepalMarketplace = $this->marketplace($nepal, 'NEPAL');
        $visible = ProductBrand::create(['name' => 'Visible Brand', 'slug' => 'visible-brand', 'is_active' => true, 'is_menu_visible' => true, 'display_desktop' => true, 'display_mobile' => true, 'landing_page_enabled' => true, 'marketplace_visibility' => [$indiaMarketplace->id], 'country_visibility' => [$india->id]]);
        $hidden = ProductBrand::create(['name' => 'Hidden Brand', 'slug' => 'hidden-brand', 'is_active' => true, 'is_menu_visible' => true, 'display_desktop' => true, 'display_mobile' => true, 'landing_page_enabled' => true, 'marketplace_visibility' => [$nepalMarketplace->id], 'country_visibility' => [$nepal->id]]);
        $needsProduct = ProductBrand::create(['name' => 'Needs Product', 'slug' => 'needs-product', 'is_active' => true, 'is_menu_visible' => true, 'display_desktop' => true, 'display_mobile' => true, 'landing_page_enabled' => true, 'hide_when_unavailable' => true]);
        Product::create(['name' => 'Visible Product', 'slug' => 'visible-product', 'sku' => 'NG-BRAND-001', 'type' => 'simple', 'status' => 'approved', 'base_price' => 0, 'brand_id' => $visible->id]);

        app(BrandVisibilityService::class)->clear();
        $brands = app(BrandVisibilityService::class)->visibleFor($indiaMarketplace)->pluck('id');

        $this->assertTrue($brands->contains($visible->id));
        $this->assertFalse($brands->contains($hidden->id));
        $this->assertFalse($brands->contains($needsProduct->id));
    }

    public function test_public_brand_landing_only_shows_menu_visible_brands(): void
    {
        $brand = ProductBrand::create(['name' => 'Landing Brand', 'slug' => 'landing-brand', 'is_active' => true, 'is_menu_visible' => true, 'display_desktop' => true, 'display_mobile' => true, 'landing_page_enabled' => true]);
        Product::create(['name' => 'Landing Product', 'slug' => 'landing-product', 'sku' => 'NG-BRAND-002', 'type' => 'simple', 'status' => 'approved', 'base_price' => 0, 'brand_id' => $brand->id]);
        ProductBrand::create(['name' => 'Non Menu Brand', 'slug' => 'non-menu-brand', 'is_active' => true, 'is_menu_visible' => false, 'display_desktop' => true, 'display_mobile' => true, 'landing_page_enabled' => true]);

        app(BrandVisibilityService::class)->clear();
        $this->get('/en/brands')->assertOk()->assertSee('Landing Brand')->assertDontSee('Non Menu Brand');
        $this->get('/en/brands/landing-brand')->assertOk()->assertSee('Landing Product');
    }

    private function country(string $name = 'Australia', string $iso2 = 'AU', string $iso3 = 'AUS'): Country
    {
        return Country::create(['name' => $name, 'iso_code_2' => $iso2, 'iso_code_3' => $iso3, 'is_active' => true]);
    }

    private function marketplace(Country $country, string $code = 'AUSTRALIA'): Marketplace
    {
        $currency = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'is_active' => true]);

        return Marketplace::create(['name' => 'NeoGiga '.$country->name, 'code' => $code, 'country_id' => $country->id, 'currency_id' => $currency->id, 'is_active' => true]);
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super admin', 'is_active' => true]);

        return User::create(['name' => 'Brand Admin', 'email' => uniqid('brand-admin-', true).'@example.com', 'password' => bcrypt('secret-password'), 'role_id' => $role->id]);
    }
}
