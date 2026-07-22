<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PartnerCountryScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_options_only_include_active_marketplace_countries_and_lock_active_geolocation(): void
    {
        [$nepal, $india, $usa] = $this->countries();

        $response = $this->withHeader('CF-IPCountry', 'NP')->getJson('/partner-country-options');

        $response->assertOk()
            ->assertJsonPath('data.detected_country_id', $nepal)
            ->assertJsonPath('data.country_locked', true)
            ->assertJsonCount(2, 'data.countries')
            ->assertJsonCount(2, 'data.marketplaces')
            ->assertJsonMissing(['id' => $usa]);

        $fallback = $this->withHeader('CF-IPCountry', 'US')->getJson('/api/partner-country-options');
        $fallback->assertOk()
            ->assertJsonPath('data.detected_country_id', null)
            ->assertJsonPath('data.country_locked', false);
    }

    public function test_geolocated_active_country_overrides_submitted_country_for_global_seller_application(): void
    {
        [$nepal, $india] = $this->countries();
        $response = $this->withHeader('CF-IPCountry', 'NP')->postJson('/api/v1/vendors/register', [
            'name' => 'Global Components',
            'email' => 'neogiga.partner.test@gmail.com',
            'phone' => '+9779800000000',
            'country_id' => $india,
            'operating_scope' => 'global',
            'type' => 'manufacturer',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('vendors', [
            'email' => 'neogiga.partner.test@gmail.com',
            'country_id' => $nepal,
            'operating_scope' => 'global',
        ]);
    }

    public function test_inactive_geolocation_requires_an_active_dropdown_country(): void
    {
        [, , $usa] = $this->countries();

        $this->withHeader('CF-IPCountry', 'US')->postJson('/api/v1/distributors/apply', [
            'name' => 'Fallback Distribution',
            'email' => 'neogiga.distributor.test@gmail.com',
            'phone' => '+12025550123',
            'country_id' => $usa,
            'operating_scope' => 'country',
            'type' => 'regional_distributor',
        ])->assertUnprocessable()->assertJsonValidationErrors('country_id');
    }

    public function test_public_seller_and_distributor_forms_persist_scope_and_active_country(): void
    {
        [$nepal, $india] = $this->countries();
        $nepalMarketplace = (int) DB::table('marketplaces')->where('country_id', $nepal)->value('id');
        $indiaMarketplace = (int) DB::table('marketplaces')->where('country_id', $india)->value('id');

        $this->withHeader('CF-IPCountry', 'NP')->postJson('/partner-applications/seller', [
            'business_name' => 'Public Seller', 'contact_person' => 'Seller Owner',
            'email' => 'neogiga.public.seller@gmail.com', 'phone' => '+9779800000001',
            'country_id' => $india, 'operating_scope' => 'global',
            'target_marketplace_ids' => [$nepalMarketplace, $indiaMarketplace],
            'annual_turnover_range' => '100000_500000',
            'business_type' => 'Company', 'seller_type' => 'manufacturer',
        ])->assertCreated();
        $this->assertDatabaseHas('seller_applications', [
            'email' => 'neogiga.public.seller@gmail.com', 'country_id' => $nepal, 'operating_scope' => 'global',
            'target_marketplace_ids' => json_encode([$nepalMarketplace, $indiaMarketplace]),
            'annual_turnover_range' => '100000_500000',
        ]);

        $this->withHeader('CF-IPCountry', 'US')->postJson('/partner-applications/distributor', [
            'business_name' => 'Public Distributor', 'contact_person' => 'Distributor Owner',
            'email' => 'neogiga.public.distributor@gmail.com', 'phone' => '+919800000001',
            'country_id' => $india, 'operating_scope' => 'country',
            'target_marketplace_ids' => [$indiaMarketplace],
            'annual_turnover_range' => '500000_1000000',
            'distributor_type' => 'regional_distributor',
        ])->assertCreated();
        $this->assertDatabaseHas('distributor_applications', [
            'email' => 'neogiga.public.distributor@gmail.com', 'country_id' => $india, 'operating_scope' => 'country',
            'target_marketplace_ids' => json_encode([$indiaMarketplace]),
            'annual_turnover_range' => '500000_1000000',
        ]);
    }

    public function test_public_partner_pages_use_same_origin_csrf_protected_submission_routes(): void
    {
        $this->get('/en/sell-on-neogiga')->assertOk()
            ->assertSee('data-endpoint="/partner-applications/seller"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('Seller base country')
            ->assertSee('data-marketplace-toggle', false)
            ->assertSee('name="annual_turnover_range"', false)
            ->assertDontSee('data-partner-marketplaces multiple', false)
            ->assertDontSee('data-endpoint="/api/seller-applications"', false);

        $this->get('/en/distributors')->assertOk()
            ->assertSee('data-endpoint="/partner-applications/distributor"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('Distributor base country')
            ->assertSee('data-marketplace-toggle', false)
            ->assertSee('name="annual_turnover_range"', false)
            ->assertDontSee('data-partner-marketplaces multiple', false)
            ->assertDontSee('data-endpoint="/api/distributor-applications"', false);
    }

    public function test_single_country_application_rejects_multiple_target_marketplaces(): void
    {
        [$nepal, $india] = $this->countries();
        $marketplaces = DB::table('marketplaces')->whereIn('country_id', [$nepal, $india])->pluck('id')->all();

        $this->withHeader('CF-IPCountry', 'NP')->postJson('/partner-applications/seller', [
            'business_name' => 'Invalid Targets', 'contact_person' => 'Seller Owner',
            'email' => 'neogiga.invalid.targets@gmail.com', 'phone' => '+9779800000002',
            'country_id' => $nepal, 'operating_scope' => 'country',
            'target_marketplace_ids' => $marketplaces,
            'annual_turnover_range' => 'under_25000',
            'business_type' => 'Company', 'seller_type' => 'manufacturer',
        ])->assertUnprocessable()->assertJsonValidationErrors('target_marketplace_ids');
    }

    private function countries(): array
    {
        $now = now();
        $currency = DB::table('currencies')->insertGetId([
            'name' => 'Test Currency', 'code' => 'TST', 'symbol' => 'T', 'is_active' => true,
            'is_default' => true, 'exchange_rate' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $nepal = DB::table('countries')->insertGetId([
            'name' => 'Nepal', 'iso_code_2' => 'NP', 'iso_code_3' => 'NPL', 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $india = DB::table('countries')->insertGetId([
            'name' => 'India', 'iso_code_2' => 'IN', 'iso_code_3' => 'IND', 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $usa = DB::table('countries')->insertGetId([
            'name' => 'United States', 'iso_code_2' => 'US', 'iso_code_3' => 'USA', 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach ([[$nepal, 'nepal'], [$india, 'india']] as [$country, $code]) {
            DB::table('marketplaces')->insert([
                'name' => ucfirst($code), 'code' => $code, 'country_id' => $country, 'currency_id' => $currency,
                'timezone' => 'UTC', 'locale' => 'en', 'is_active' => true, 'is_default' => $code === 'nepal',
                'allow_vendor_registration' => true, 'require_vendor_approval' => true, 'tax_rate' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        return [$nepal, $india, $usa];
    }
}
