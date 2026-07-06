<?php

namespace Database\Seeders\MarketplaceSeeders;

use Illuminate\Database\Seeder;
use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceDomain;
use App\Models\Marketplace\MarketplaceSetting;
use App\Models\Marketplace\Region;
use App\Models\Marketplace\City;

class MarketplaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Countries
        $countries = [
            ['iso_code_2' => 'US', 'iso_code_3' => 'USA', 'name' => 'United States', 'phone_code' => '+1', 'capital' => 'Washington D.C.', 'region' => 'Americas', 'subregion' => 'Northern America', 'currency_code' => 'USD', 'translations' => json_encode(['en' => 'United States']), 'is_active' => true, 'is_eu' => false],
            ['iso_code_2' => 'NP', 'iso_code_3' => 'NPL', 'name' => 'Nepal', 'phone_code' => '+977', 'capital' => 'Kathmandu', 'region' => 'Asia', 'subregion' => 'Southern Asia', 'currency_code' => 'NPR', 'translations' => json_encode(['en' => 'Nepal']), 'is_active' => true, 'is_eu' => false],
            ['iso_code_2' => 'IN', 'iso_code_3' => 'IND', 'name' => 'India', 'phone_code' => '+91', 'capital' => 'New Delhi', 'region' => 'Asia', 'subregion' => 'Southern Asia', 'currency_code' => 'INR', 'translations' => json_encode(['en' => 'India']), 'is_active' => true, 'is_eu' => false],
            ['iso_code_2' => 'GB', 'iso_code_3' => 'GBR', 'name' => 'United Kingdom', 'phone_code' => '+44', 'capital' => 'London', 'region' => 'Europe', 'subregion' => 'Northern Europe', 'currency_code' => 'GBP', 'translations' => json_encode(['en' => 'United Kingdom']), 'is_active' => true, 'is_eu' => false],
            ['iso_code_2' => 'DE', 'iso_code_3' => 'DEU', 'name' => 'Germany', 'phone_code' => '+49', 'capital' => 'Berlin', 'region' => 'Europe', 'subregion' => 'Western Europe', 'currency_code' => 'EUR', 'translations' => json_encode(['en' => 'Germany']), 'is_active' => true, 'is_eu' => true],
            ['iso_code_2' => 'CN', 'iso_code_3' => 'CHN', 'name' => 'China', 'phone_code' => '+86', 'capital' => 'Beijing', 'region' => 'Asia', 'subregion' => 'Eastern Asia', 'currency_code' => 'CNY', 'translations' => json_encode(['en' => 'China']), 'is_active' => true, 'is_eu' => false],
            ['iso_code_2' => 'JP', 'iso_code_3' => 'JPN', 'name' => 'Japan', 'phone_code' => '+81', 'capital' => 'Tokyo', 'region' => 'Asia', 'subregion' => 'Eastern Asia', 'currency_code' => 'JPY', 'translations' => json_encode(['en' => 'Japan']), 'is_active' => true, 'is_eu' => false],
            ['iso_code_2' => 'SG', 'iso_code_3' => 'SGP', 'name' => 'Singapore', 'phone_code' => '+65', 'capital' => 'Singapore', 'region' => 'Asia', 'subregion' => 'South-Eastern Asia', 'currency_code' => 'SGD', 'translations' => json_encode(['en' => 'Singapore']), 'is_active' => true, 'is_eu' => false],
            ['iso_code_2' => 'AE', 'iso_code_3' => 'ARE', 'name' => 'United Arab Emirates', 'phone_code' => '+971', 'capital' => 'Abu Dhabi', 'region' => 'Asia', 'subregion' => 'Western Asia', 'currency_code' => 'AED', 'translations' => json_encode(['en' => 'United Arab Emirates']), 'is_active' => true, 'is_eu' => false],
            ['iso_code_2' => 'AU', 'iso_code_3' => 'AUS', 'name' => 'Australia', 'phone_code' => '+61', 'capital' => 'Canberra', 'region' => 'Oceania', 'subregion' => 'Australia and New Zealand', 'currency_code' => 'AUD', 'translations' => json_encode(['en' => 'Australia']), 'is_active' => true, 'is_eu' => false],
        ];

        foreach ($countries as $countryData) {
            Country::firstOrCreate(['iso_code_2' => $countryData['iso_code_2']], $countryData);
        }

        // Seed Currencies
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
            ['code' => 'NPR', 'name' => 'Nepalese Rupee', 'symbol' => '₨', 'decimal_places' => 2],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'decimal_places' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'decimal_places' => 2],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_places' => 0],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimal_places' => 2],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ', 'decimal_places' => 2],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'decimal_places' => 2],
        ];

        foreach ($currencies as $currencyData) {
            Currency::firstOrCreate(['code' => $currencyData['code']], $currencyData);
        }

        // Seed Marketplaces - using existing migration schema
        $usCountry = Country::where('iso_code_2', 'US')->first();
        $npCountry = Country::where('iso_code_2', 'NP')->first();
        $inCountry = Country::where('iso_code_2', 'IN')->first();
        
        $usd = Currency::where('code', 'USD')->first();
        $npr = Currency::where('code', 'NPR')->first();
        $inr = Currency::where('code', 'INR')->first();

        $globalMp = Marketplace::firstOrCreate(
            ['code' => 'GLOBAL'],
            [
                'name' => 'NeoGiga Global',
                'description' => 'Global master marketplace for electronics, robotics, IoT, and industrial components',
                'country_id' => $usCountry?->id,
                'currency_id' => $usd?->id,
                'timezone' => 'UTC',
                'locale' => 'en',
                'is_active' => true,
                'is_default' => true,
                'allow_vendor_registration' => true,
                'require_vendor_approval' => true,
                'tax_rate' => 0.00,
                'supported_languages' => json_encode(['en']),
                'settings' => json_encode([]),
            ]
        );

        $nepalMp = Marketplace::firstOrCreate(
            ['code' => 'NEPAL'],
            [
                'name' => 'GigaNepal',
                'description' => 'Nepal regional marketplace for electronics, robotics, and DIY components',
                'country_id' => $npCountry?->id,
                'currency_id' => $npr?->id,
                'timezone' => 'Asia/Kathmandu',
                'locale' => 'en',
                'is_active' => true,
                'is_default' => false,
                'allow_vendor_registration' => true,
                'require_vendor_approval' => true,
                'tax_rate' => 13.00,
                'supported_languages' => json_encode(['en', 'ne']),
                'settings' => json_encode([]),
            ]
        );

        $indiaMp = Marketplace::firstOrCreate(
            ['code' => 'INDIA'],
            [
                'name' => 'NeoGiga India',
                'description' => 'India regional marketplace for electronics, automation, and industrial components',
                'country_id' => $inCountry?->id,
                'currency_id' => $inr?->id,
                'timezone' => 'Asia/Kolkata',
                'locale' => 'en',
                'is_active' => true,
                'is_default' => false,
                'allow_vendor_registration' => true,
                'require_vendor_approval' => true,
                'tax_rate' => 18.00,
                'supported_languages' => json_encode(['en', 'hi']),
                'settings' => json_encode([]),
            ]
        );

        // Seed Marketplace Domains
        MarketplaceDomain::firstOrCreate(
            ['domain' => 'neogiga.com'],
            [
                'marketplace_id' => $globalMp->id,
                'is_primary' => true,
                'is_active' => true,
                'ssl_certificate_path' => null,
                'ssl_expires_at' => null,
                'redirect_rules' => json_encode([]),
            ]
        );

        MarketplaceDomain::firstOrCreate(
            ['domain' => 'giganepal.com'],
            [
                'marketplace_id' => $nepalMp->id,
                'is_primary' => true,
                'is_active' => true,
                'ssl_certificate_path' => null,
                'ssl_expires_at' => null,
                'redirect_rules' => json_encode([]),
            ]
        );

        MarketplaceDomain::firstOrCreate(
            ['domain' => 'neogiga.in'],
            [
                'marketplace_id' => $indiaMp->id,
                'is_primary' => true,
                'is_active' => true,
                'ssl_certificate_path' => null,
                'ssl_expires_at' => null,
                'redirect_rules' => json_encode([]),
            ]
        );

        // Seed Marketplace Settings
        $this->seedMarketplaceSettings($globalMp, 'USD');
        $this->seedMarketplaceSettings($nepalMp, 'NPR');
        $this->seedMarketplaceSettings($indiaMp, 'INR');

        // Seed Regions and Cities for Nepal
        $nepal = Country::where('iso_code_2', 'NP')->first();
        if ($nepal) {
            $regions = [
                ['name' => 'Province No. 1', 'code' => 'P1'],
                ['name' => 'Madhesh Province', 'code' => 'P2'],
                ['name' => 'Bagmati Province', 'code' => 'P3'],
                ['name' => 'Gandaki Province', 'code' => 'P4'],
                ['name' => 'Lumbini Province', 'code' => 'P5'],
                ['name' => 'Karnali Province', 'code' => 'P6'],
                ['name' => 'Sudurpashchim Province', 'code' => 'P7'],
            ];

            foreach ($regions as $regionData) {
                $region = Region::firstOrCreate(
                    ['country_id' => $nepal->id, 'code' => $regionData['code']],
                    ['name' => $regionData['name']]
                );

                // Add cities for Bagmati Province (Kathmandu valley)
                if ($regionData['code'] === 'P3') {
                    $cities = ['Kathmandu', 'Lalitpur', 'Bhaktapur', 'Kirtipur', 'Madhyapur Thimi'];
                    foreach ($cities as $cityName) {
                        City::firstOrCreate(
                            ['region_id' => $region->id, 'name' => $cityName],
                            ['country_id' => $nepal->id, 'latitude' => '27.7172', 'longitude' => '85.3240']
                        );
                    }
                }
            }
        }

        // Seed Regions and Cities for India
        $india = Country::where('iso_code_2', 'IN')->first();
        if ($india) {
            $regions = [
                ['name' => 'Delhi', 'code' => 'DL'],
                ['name' => 'Maharashtra', 'code' => 'MH'],
                ['name' => 'Karnataka', 'code' => 'KA'],
                ['name' => 'Tamil Nadu', 'code' => 'TN'],
                ['name' => 'Telangana', 'code' => 'TG'],
                ['name' => 'West Bengal', 'code' => 'WB'],
                ['name' => 'Gujarat', 'code' => 'GJ'],
            ];

            foreach ($regions as $regionData) {
                $region = Region::firstOrCreate(
                    ['country_id' => $india->id, 'code' => $regionData['code']],
                    ['name' => $regionData['name']]
                );

                // Add major cities
                $citiesMap = [
                    'DL' => ['New Delhi', 'Delhi'],
                    'MH' => ['Mumbai', 'Pune', 'Nagpur'],
                    'KA' => ['Bangalore', 'Mysore', 'Hubli'],
                    'TN' => ['Chennai', 'Coimbatore', 'Madurai'],
                    'TG' => ['Hyderabad', 'Warangal'],
                    'WB' => ['Kolkata', 'Howrah'],
                    'GJ' => ['Ahmedabad', 'Surat', 'Vadodara'],
                ];

                if (isset($citiesMap[$regionData['code']])) {
                    foreach ($citiesMap[$regionData['code']] as $cityName) {
                        City::firstOrCreate(
                            ['region_id' => $region->id, 'name' => $cityName],
                            ['country_id' => $india->id, 'latitude' => '20.5937', 'longitude' => '78.9629']
                        );
                    }
                }
            }
        }

        $this->command->info('Marketplace seeder completed successfully!');
    }

    private function seedMarketplaceSettings(Marketplace $marketplace, string $currencyCode): void
    {
        // marketplace_settings is a key/value store (key, value, type, group)
        // — the previous wide-row insert failed against the real schema (DB-04).
        $currency = Currency::where('code', $currencyCode)->first();

        $settings = [
            // group => [key => [value, type, is_public]]
            'commerce' => [
                'currency_id' => [$currency?->id, 'integer', true],
                'currency_code' => [$currencyCode, 'string', true],
                'tax_inclusive' => ['0', 'boolean', true],
                'enable_guest_checkout' => ['1', 'boolean', true],
                'minimum_order_amount' => ['0', 'decimal', true],
                'free_shipping_threshold' => ['5000', 'decimal', true],
            ],
            'units' => [
                'default_weight_unit' => ['kg', 'string', true],
                'default_dimension_unit' => ['cm', 'string', true],
            ],
            'locale' => [
                'date_format' => ['Y-m-d', 'string', true],
                'time_format' => ['H:i:s', 'string', true],
                'timezone' => [$marketplace->timezone, 'string', true],
                'language' => [$marketplace->locale, 'string', true],
            ],
            'seo' => [
                'seo_title' => [$marketplace->name . ' - Electronics & Robotics Marketplace', 'string', true],
                'seo_description' => [$marketplace->description, 'string', true],
                'seo_keywords' => ['electronics, robotics, IoT, Arduino, ESP32, sensors, batteries, solar, tools', 'string', true],
            ],
            'ops' => [
                'maintenance_mode' => ['0', 'boolean', false],
                'analytics_id' => [null, 'string', false],
            ],
        ];

        foreach ($settings as $group => $pairs) {
            foreach ($pairs as $key => [$value, $type, $isPublic]) {
                MarketplaceSetting::firstOrCreate(
                    ['marketplace_id' => $marketplace->id, 'key' => $key],
                    [
                        'value' => $value === null ? null : (string) $value,
                        'type' => $type,
                        'group' => $group,
                        'is_public' => $isPublic,
                    ],
                );
            }
        }
    }
}
