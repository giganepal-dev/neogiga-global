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

        $response = $this->withHeader('CF-IPCountry', 'NP')->getJson('/api/partner-country-options');

        $response->assertOk()
            ->assertJsonPath('data.detected_country_id', $nepal)
            ->assertJsonPath('data.country_locked', true)
            ->assertJsonCount(2, 'data.countries')
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

        $this->withHeader('CF-IPCountry', 'NP')->postJson('/api/seller-applications', [
            'business_name' => 'Public Seller', 'contact_person' => 'Seller Owner',
            'email' => 'neogiga.public.seller@gmail.com', 'phone' => '+9779800000001',
            'country_id' => $india, 'operating_scope' => 'global',
            'business_type' => 'Company', 'seller_type' => 'manufacturer',
        ])->assertCreated();
        $this->assertDatabaseHas('seller_applications', [
            'email' => 'neogiga.public.seller@gmail.com', 'country_id' => $nepal, 'operating_scope' => 'global',
        ]);

        $this->withHeader('CF-IPCountry', 'US')->postJson('/api/distributor-applications', [
            'business_name' => 'Public Distributor', 'contact_person' => 'Distributor Owner',
            'email' => 'neogiga.public.distributor@gmail.com', 'phone' => '+919800000001',
            'country_id' => $india, 'operating_scope' => 'country',
            'distributor_type' => 'regional_distributor',
        ])->assertCreated();
        $this->assertDatabaseHas('distributor_applications', [
            'email' => 'neogiga.public.distributor@gmail.com', 'country_id' => $india, 'operating_scope' => 'country',
        ]);
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
