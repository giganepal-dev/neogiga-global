<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;

/**
 * Seed initial countries, currencies, and languages.
 */
class CountryDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Currencies first
        $this->seedCurrencies();

        // Seed Languages
        $this->seedLanguages();

        // Seed Countries
        $this->seedCountries();
    }

    /**
     * Seed major world currencies.
     */
    protected function seedCurrencies(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => true],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'symbol_position' => 'after', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'symbol_position' => 'before', 'decimal_places' => 0, 'is_default' => false],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF', 'symbol_position' => 'space_before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => '₩', 'symbol_position' => 'before', 'decimal_places' => 0, 'is_default' => false],
            ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'symbol_position' => 'before', 'decimal_places' => 0, 'is_default' => false],
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'VND', 'name' => 'Vietnamese Dong', 'symbol' => '₫', 'symbol_position' => 'after', 'decimal_places' => 0, 'is_default' => false],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => '﷼', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'QAR', 'name' => 'Qatari Riyal', 'symbol' => '﷼', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'NPR', 'name' => 'Nepalese Rupee', 'symbol' => '₨', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'LKR', 'name' => 'Sri Lankan Rupee', 'symbol' => '₨', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R', 'symbol_position' => 'before', 'decimal_places' => 2, 'is_default' => false],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }

    /**
     * Seed major languages.
     */
    protected function seedLanguages(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr'],
            ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'direction' => 'ltr'],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr'],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'direction' => 'ltr'],
            ['code' => 'it', 'name' => 'Italian', 'native_name' => 'Italiano', 'direction' => 'ltr'],
            ['code' => 'nl', 'name' => 'Dutch', 'native_name' => 'Nederlands', 'direction' => 'ltr'],
            ['code' => 'ja', 'name' => 'Japanese', 'native_name' => '日本語', 'direction' => 'ltr'],
            ['code' => 'ko', 'name' => 'Korean', 'native_name' => '한국어', 'direction' => 'ltr'],
            ['code' => 'zh', 'name' => 'Chinese', 'native_name' => '中文', 'direction' => 'ltr'],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl'],
            ['code' => 'hi', 'name' => 'Hindi', 'native_name' => 'हिन्दी', 'direction' => 'ltr'],
            ['code' => 'pt', 'name' => 'Portuguese', 'native_name' => 'Português', 'direction' => 'ltr'],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }

    /**
     * Seed target countries with full details.
     */
    protected function seedCountries(): void
    {
        $countries = [
            // Asia-Pacific
            [
                'name' => 'Nepal', 'iso_code_2' => 'NP', 'iso_code_3' => 'NPL', 'numeric_code' => '524',
                'phone_code' => '+977', 'capital' => 'Kathmandu', 'currency_code' => 'NPR', 'currency_symbol' => '₨',
                'tld' => '.np', 'region' => 'Asia', 'subregion' => 'Southern Asia',
                'default_vat_rate' => 13.00, 'default_import_duty_rate' => 10.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'India', 'iso_code_2' => 'IN', 'iso_code_3' => 'IND', 'numeric_code' => '356',
                'phone_code' => '+91', 'capital' => 'New Delhi', 'currency_code' => 'INR', 'currency_symbol' => '₹',
                'tld' => '.in', 'region' => 'Asia', 'subregion' => 'Southern Asia',
                'default_vat_rate' => 18.00, 'default_import_duty_rate' => 10.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Bangladesh', 'iso_code_2' => 'BD', 'iso_code_3' => 'BGD', 'numeric_code' => '050',
                'phone_code' => '+880', 'capital' => 'Dhaka', 'currency_code' => 'BDT', 'currency_symbol' => '৳',
                'tld' => '.bd', 'region' => 'Asia', 'subregion' => 'Southern Asia',
                'default_vat_rate' => 15.00, 'default_import_duty_rate' => 12.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Sri Lanka', 'iso_code_2' => 'LK', 'iso_code_3' => 'LKA', 'numeric_code' => '144',
                'phone_code' => '+94', 'capital' => 'Colombo', 'currency_code' => 'LKR', 'currency_symbol' => '₨',
                'tld' => '.lk', 'region' => 'Asia', 'subregion' => 'Southern Asia',
                'default_vat_rate' => 15.00, 'default_import_duty_rate' => 10.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Singapore', 'iso_code_2' => 'SG', 'iso_code_3' => 'SGP', 'numeric_code' => '702',
                'phone_code' => '+65', 'capital' => 'Singapore', 'currency_code' => 'SGD', 'currency_symbol' => 'S$',
                'tld' => '.sg', 'region' => 'Asia', 'subregion' => 'South-Eastern Asia',
                'default_vat_rate' => 8.00, 'default_import_duty_rate' => 7.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Malaysia', 'iso_code_2' => 'MY', 'iso_code_3' => 'MYS', 'numeric_code' => '458',
                'phone_code' => '+60', 'capital' => 'Kuala Lumpur', 'currency_code' => 'MYR', 'currency_symbol' => 'RM',
                'tld' => '.my', 'region' => 'Asia', 'subregion' => 'South-Eastern Asia',
                'default_vat_rate' => 10.00, 'default_import_duty_rate' => 8.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Thailand', 'iso_code_2' => 'TH', 'iso_code_3' => 'THA', 'numeric_code' => '764',
                'phone_code' => '+66', 'capital' => 'Bangkok', 'currency_code' => 'THB', 'currency_symbol' => '฿',
                'tld' => '.th', 'region' => 'Asia', 'subregion' => 'South-Eastern Asia',
                'default_vat_rate' => 7.00, 'default_import_duty_rate' => 10.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Indonesia', 'iso_code_2' => 'ID', 'iso_code_3' => 'IDN', 'numeric_code' => '360',
                'phone_code' => '+62', 'capital' => 'Jakarta', 'currency_code' => 'IDR', 'currency_symbol' => 'Rp',
                'tld' => '.id', 'region' => 'Asia', 'subregion' => 'South-Eastern Asia',
                'default_vat_rate' => 11.00, 'default_import_duty_rate' => 10.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Japan', 'iso_code_2' => 'JP', 'iso_code_3' => 'JPN', 'numeric_code' => '392',
                'phone_code' => '+81', 'capital' => 'Tokyo', 'currency_code' => 'JPY', 'currency_symbol' => '¥',
                'tld' => '.jp', 'region' => 'Asia', 'subregion' => 'Eastern Asia',
                'default_vat_rate' => 10.00, 'default_import_duty_rate' => 5.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'South Korea', 'iso_code_2' => 'KR', 'iso_code_3' => 'KOR', 'numeric_code' => '410',
                'phone_code' => '+82', 'capital' => 'Seoul', 'currency_code' => 'KRW', 'currency_symbol' => '₩',
                'tld' => '.kr', 'region' => 'Asia', 'subregion' => 'Eastern Asia',
                'default_vat_rate' => 10.00, 'default_import_duty_rate' => 8.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'China', 'iso_code_2' => 'CN', 'iso_code_3' => 'CHN', 'numeric_code' => '156',
                'phone_code' => '+86', 'capital' => 'Beijing', 'currency_code' => 'CNY', 'currency_symbol' => '¥',
                'tld' => '.cn', 'region' => 'Asia', 'subregion' => 'Eastern Asia',
                'default_vat_rate' => 13.00, 'default_import_duty_rate' => 9.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            // Middle East
            [
                'name' => 'United Arab Emirates', 'iso_code_2' => 'AE', 'iso_code_3' => 'ARE', 'numeric_code' => '784',
                'phone_code' => '+971', 'capital' => 'Abu Dhabi', 'currency_code' => 'AED', 'currency_symbol' => 'د.إ',
                'tld' => '.ae', 'region' => 'Asia', 'subregion' => 'Western Asia',
                'default_vat_rate' => 5.00, 'default_import_duty_rate' => 5.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Saudi Arabia', 'iso_code_2' => 'SA', 'iso_code_3' => 'SAU', 'numeric_code' => '682',
                'phone_code' => '+966', 'capital' => 'Riyadh', 'currency_code' => 'SAR', 'currency_symbol' => '﷼',
                'tld' => '.sa', 'region' => 'Asia', 'subregion' => 'Western Asia',
                'default_vat_rate' => 15.00, 'default_import_duty_rate' => 5.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Qatar', 'iso_code_2' => 'QA', 'iso_code_3' => 'QAT', 'numeric_code' => '634',
                'phone_code' => '+974', 'capital' => 'Doha', 'currency_code' => 'QAR', 'currency_symbol' => '﷼',
                'tld' => '.qa', 'region' => 'Asia', 'subregion' => 'Western Asia',
                'default_vat_rate' => 0.00, 'default_import_duty_rate' => 5.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            // Europe
            [
                'name' => 'Germany', 'iso_code_2' => 'DE', 'iso_code_3' => 'DEU', 'numeric_code' => '276',
                'phone_code' => '+49', 'capital' => 'Berlin', 'currency_code' => 'EUR', 'currency_symbol' => '€',
                'tld' => '.de', 'region' => 'Europe', 'subregion' => 'Western Europe', 'is_eu' => true,
                'default_vat_rate' => 19.00, 'default_import_duty_rate' => 4.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'France', 'iso_code_2' => 'FR', 'iso_code_3' => 'FRA', 'numeric_code' => '250',
                'phone_code' => '+33', 'capital' => 'Paris', 'currency_code' => 'EUR', 'currency_symbol' => '€',
                'tld' => '.fr', 'region' => 'Europe', 'subregion' => 'Western Europe', 'is_eu' => true,
                'default_vat_rate' => 20.00, 'default_import_duty_rate' => 4.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'United Kingdom', 'iso_code_2' => 'GB', 'iso_code_3' => 'GBR', 'numeric_code' => '826',
                'phone_code' => '+44', 'capital' => 'London', 'currency_code' => 'GBP', 'currency_symbol' => '£',
                'tld' => '.uk', 'region' => 'Europe', 'subregion' => 'Northern Europe', 'is_eu' => false,
                'default_vat_rate' => 20.00, 'default_import_duty_rate' => 4.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Netherlands', 'iso_code_2' => 'NL', 'iso_code_3' => 'NLD', 'numeric_code' => '528',
                'phone_code' => '+31', 'capital' => 'Amsterdam', 'currency_code' => 'EUR', 'currency_symbol' => '€',
                'tld' => '.nl', 'region' => 'Europe', 'subregion' => 'Western Europe', 'is_eu' => true,
                'default_vat_rate' => 21.00, 'default_import_duty_rate' => 4.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Italy', 'iso_code_2' => 'IT', 'iso_code_3' => 'ITA', 'numeric_code' => '380',
                'phone_code' => '+39', 'capital' => 'Rome', 'currency_code' => 'EUR', 'currency_symbol' => '€',
                'tld' => '.it', 'region' => 'Europe', 'subregion' => 'Southern Europe', 'is_eu' => true,
                'default_vat_rate' => 22.00, 'default_import_duty_rate' => 4.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Spain', 'iso_code_2' => 'ES', 'iso_code_3' => 'ESP', 'numeric_code' => '724',
                'phone_code' => '+34', 'capital' => 'Madrid', 'currency_code' => 'EUR', 'currency_symbol' => '€',
                'tld' => '.es', 'region' => 'Europe', 'subregion' => 'Southern Europe', 'is_eu' => true,
                'default_vat_rate' => 21.00, 'default_import_duty_rate' => 4.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            // Americas
            [
                'name' => 'United States', 'iso_code_2' => 'US', 'iso_code_3' => 'USA', 'numeric_code' => '840',
                'phone_code' => '+1', 'capital' => 'Washington D.C.', 'currency_code' => 'USD', 'currency_symbol' => '$',
                'tld' => '.us', 'region' => 'Americas', 'subregion' => 'Northern America',
                'default_vat_rate' => 0.00, 'default_import_duty_rate' => 3.50,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'Canada', 'iso_code_2' => 'CA', 'iso_code_3' => 'CAN', 'numeric_code' => '124',
                'phone_code' => '+1', 'capital' => 'Ottawa', 'currency_code' => 'CAD', 'currency_symbol' => 'C$',
                'tld' => '.ca', 'region' => 'Americas', 'subregion' => 'Northern America',
                'default_vat_rate' => 5.00, 'default_import_duty_rate' => 3.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            // Oceania
            [
                'name' => 'Australia', 'iso_code_2' => 'AU', 'iso_code_3' => 'AUS', 'numeric_code' => '036',
                'phone_code' => '+61', 'capital' => 'Canberra', 'currency_code' => 'AUD', 'currency_symbol' => 'A$',
                'tld' => '.au', 'region' => 'Oceania', 'subregion' => 'Australia and New Zealand',
                'default_vat_rate' => 10.00, 'default_import_duty_rate' => 5.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            [
                'name' => 'New Zealand', 'iso_code_2' => 'NZ', 'iso_code_3' => 'NZL', 'numeric_code' => '554',
                'phone_code' => '+64', 'capital' => 'Wellington', 'currency_code' => 'NZD', 'currency_symbol' => 'NZ$',
                'tld' => '.nz', 'region' => 'Oceania', 'subregion' => 'Australia and New Zealand',
                'default_vat_rate' => 15.00, 'default_import_duty_rate' => 5.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
            // Africa
            [
                'name' => 'South Africa', 'iso_code_2' => 'ZA', 'iso_code_3' => 'ZAF', 'numeric_code' => '710',
                'phone_code' => '+27', 'capital' => 'Pretoria', 'currency_code' => 'ZAR', 'currency_symbol' => 'R',
                'tld' => '.za', 'region' => 'Africa', 'subregion' => 'Southern Africa',
                'default_vat_rate' => 15.00, 'default_import_duty_rate' => 8.00,
                'allows_marketplace' => true, 'allows_b2b' => true, 'allows_b2c' => true,
            ],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['iso_code_2' => $country['iso_code_2']],
                $country
            );
        }

        // Link currencies to countries
        $this->linkCurrenciesToCountries();
    }

    /**
     * Link currencies to their respective countries.
     */
    protected function linkCurrenciesToCountries(): void
    {
        $countryCurrencyMap = [
            'NP' => 'NPR',
            'IN' => 'INR',
            'BD' => 'BDT',
            'LK' => 'LKR',
            'SG' => 'SGD',
            'MY' => 'MYR',
            'TH' => 'THB',
            'ID' => 'IDR',
            'JP' => 'JPY',
            'KR' => 'KRW',
            'CN' => 'CNY',
            'AE' => 'AED',
            'SA' => 'SAR',
            'QA' => 'QAR',
            'DE' => 'EUR',
            'FR' => 'EUR',
            'GB' => 'GBP',
            'NL' => 'EUR',
            'IT' => 'EUR',
            'ES' => 'EUR',
            'US' => 'USD',
            'CA' => 'CAD',
            'AU' => 'AUD',
            'NZ' => 'NZD',
            'ZA' => 'ZAR',
        ];

        foreach ($countryCurrencyMap as $countryCode => $currencyCode) {
            $country = Country::where('iso_code_2', $countryCode)->first();
            $currency = Currency::where('code', $currencyCode)->first();

            if ($country && $currency) {
                $country->currencies()->syncWithoutDetaching([
                    $currency->id => ['is_primary' => true]
                ]);
            }
        }

        // Add USD as secondary currency for international trade
        $usd = Currency::where('code', 'USD')->first();
        if ($usd) {
            Country::whereIn('iso_code_2', ['SG', 'AE', 'SA', 'QA', 'MY', 'TH'])
                ->each(function ($country) use ($usd) {
                    $country->currencies()->syncWithoutDetaching([
                        $usd->id => ['is_primary' => false]
                    ]);
                });
        }
    }
}
