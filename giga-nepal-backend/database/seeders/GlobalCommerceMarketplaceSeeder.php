<?php

namespace Database\Seeders;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use Illuminate\Database\Seeder;

/**
 * Global Commerce Stage 1: seeds the first 25 marketplace configurations.
 * INDIA and NEPAL already exist (active, own domains) — this seeder only adds
 * the new Stage-1 columns to those two rows and leaves everything else about
 * them untouched. The other 22 are created fresh as launch_status=preview,
 * is_active=false, checkout_enabled=false — informational only, no storefront.
 *
 * No tax rates, payment credentials, or legal/delivery claims are set.
 * currencies.exchange_rate is left at the schema default (1.0) — an
 * explicit "not yet configured" placeholder, not a real FX rate.
 *
 * Idempotent. Run explicitly:
 *   php artisan db:seed --class=Database\\Seeders\\GlobalCommerceMarketplaceSeeder --force
 */
class GlobalCommerceMarketplaceSeeder extends Seeder
{
    /**
     * [name, iso2, iso3, phone_code, capital, currency_code, region, subregion, timezone, url_prefix]
     */
    private const COUNTRIES = [
        ['India', 'IN', 'IND', '+91', 'New Delhi', 'INR', 'Asia', 'Southern Asia', 'Asia/Kolkata', 'in'],
        ['Nepal', 'NP', 'NPL', '+977', 'Kathmandu', 'NPR', 'Asia', 'Southern Asia', 'Asia/Kathmandu', 'np'],
        ['Bangladesh', 'BD', 'BGD', '+880', 'Dhaka', 'BDT', 'Asia', 'Southern Asia', 'Asia/Dhaka', 'bd'],
        ['Sri Lanka', 'LK', 'LKA', '+94', 'Colombo', 'LKR', 'Asia', 'Southern Asia', 'Asia/Colombo', 'lk'],
        ['Pakistan', 'PK', 'PAK', '+92', 'Islamabad', 'PKR', 'Asia', 'Southern Asia', 'Asia/Karachi', 'pk'],
        ['Bhutan', 'BT', 'BTN', '+975', 'Thimphu', 'BTN', 'Asia', 'Southern Asia', 'Asia/Thimphu', 'bt'],
        ['Maldives', 'MV', 'MDV', '+960', 'Male', 'MVR', 'Asia', 'Southern Asia', 'Indian/Maldives', 'mv'],
        ['United Arab Emirates', 'AE', 'ARE', '+971', 'Abu Dhabi', 'AED', 'Asia', 'Western Asia', 'Asia/Dubai', 'ae'],
        ['Saudi Arabia', 'SA', 'SAU', '+966', 'Riyadh', 'SAR', 'Asia', 'Western Asia', 'Asia/Riyadh', 'sa'],
        ['Qatar', 'QA', 'QAT', '+974', 'Doha', 'QAR', 'Asia', 'Western Asia', 'Asia/Qatar', 'qa'],
        ['Oman', 'OM', 'OMN', '+968', 'Muscat', 'OMR', 'Asia', 'Western Asia', 'Asia/Muscat', 'om'],
        ['Kuwait', 'KW', 'KWT', '+965', 'Kuwait City', 'KWD', 'Asia', 'Western Asia', 'Asia/Kuwait', 'kw'],
        ['United States', 'US', 'USA', '+1', 'Washington, D.C.', 'USD', 'Americas', 'Northern America', 'America/New_York', 'us'],
        ['Canada', 'CA', 'CAN', '+1', 'Ottawa', 'CAD', 'Americas', 'Northern America', 'America/Toronto', 'ca'],
        ['United Kingdom', 'GB', 'GBR', '+44', 'London', 'GBP', 'Europe', 'Northern Europe', 'Europe/London', 'uk'],
        ['Germany', 'DE', 'DEU', '+49', 'Berlin', 'EUR', 'Europe', 'Western Europe', 'Europe/Berlin', 'de'],
        ['France', 'FR', 'FRA', '+33', 'Paris', 'EUR', 'Europe', 'Western Europe', 'Europe/Paris', 'fr'],
        ['Italy', 'IT', 'ITA', '+39', 'Rome', 'EUR', 'Europe', 'Southern Europe', 'Europe/Rome', 'it'],
        ['Spain', 'ES', 'ESP', '+34', 'Madrid', 'EUR', 'Europe', 'Southern Europe', 'Europe/Madrid', 'es'],
        ['Netherlands', 'NL', 'NLD', '+31', 'Amsterdam', 'EUR', 'Europe', 'Western Europe', 'Europe/Amsterdam', 'nl'],
        ['Australia', 'AU', 'AUS', '+61', 'Canberra', 'AUD', 'Oceania', 'Australia and New Zealand', 'Australia/Sydney', 'au'],
        ['New Zealand', 'NZ', 'NZL', '+64', 'Wellington', 'NZD', 'Oceania', 'Australia and New Zealand', 'Pacific/Auckland', 'nz'],
        ['Brazil', 'BR', 'BRA', '+55', 'Brasilia', 'BRL', 'Americas', 'South America', 'America/Sao_Paulo', 'br'],
        ['South Africa', 'ZA', 'ZAF', '+27', 'Pretoria', 'ZAR', 'Africa', 'Southern Africa', 'Africa/Johannesburg', 'za'],
        ['Kenya', 'KE', 'KEN', '+254', 'Nairobi', 'KES', 'Africa', 'Eastern Africa', 'Africa/Nairobi', 'ke'],
    ];

    /** code => [name, symbol, native_symbol, decimal_places] */
    private const CURRENCIES = [
        'INR' => ['Indian Rupee', '₹', '₹', 2],
        'NPR' => ['Nepalese Rupee', 'Rs', 'रू', 2],
        'BDT' => ['Bangladeshi Taka', '৳', '৳', 2],
        'LKR' => ['Sri Lankan Rupee', 'Rs', 'රු', 2],
        'PKR' => ['Pakistani Rupee', 'Rs', '₨', 2],
        'BTN' => ['Bhutanese Ngultrum', 'Nu.', 'Nu.', 2],
        'MVR' => ['Maldivian Rufiyaa', 'Rf', 'ރ', 2],
        'AED' => ['UAE Dirham', 'AED', 'د.إ', 2],
        'SAR' => ['Saudi Riyal', 'SR', 'ر.س', 2],
        'QAR' => ['Qatari Riyal', 'QR', 'ر.ق', 2],
        'OMR' => ['Omani Rial', 'RO', 'ر.ع.', 3],
        'KWD' => ['Kuwaiti Dinar', 'KD', 'د.ك', 3],
        'USD' => ['US Dollar', '$', '$', 2],
        'CAD' => ['Canadian Dollar', 'CA$', '$', 2],
        'GBP' => ['British Pound', '£', '£', 2],
        'EUR' => ['Euro', '€', '€', 2],
        'AUD' => ['Australian Dollar', 'A$', '$', 2],
        'NZD' => ['New Zealand Dollar', 'NZ$', '$', 2],
        'BRL' => ['Brazilian Real', 'R$', 'R$', 2],
        'ZAR' => ['South African Rand', 'R', 'R', 2],
        'KES' => ['Kenyan Shilling', 'KSh', 'Sh', 2],
    ];

    public function run(): void
    {
        $currencyIds = [];
        foreach (self::CURRENCIES as $code => [$name, $symbol, $nativeSymbol, $decimals]) {
            $currencyIds[$code] = Currency::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'symbol' => $symbol,
                    'native_symbol' => $nativeSymbol,
                    'decimal_places' => $decimals,
                    'is_active' => true,
                    // Placeholder — no real exchange rate has been configured yet.
                    'exchange_rate' => 1.0,
                    'exchange_rate_updated_at' => null,
                ]
            )->id;
        }

        foreach (self::COUNTRIES as [$name, $iso2, $iso3, $phone, $capital, $currencyCode, $region, $subregion, $timezone, $prefix]) {
            $country = Country::firstOrCreate(
                ['iso_code_2' => $iso2],
                [
                    'name' => $name,
                    'iso_code_3' => $iso3,
                    'phone_code' => $phone,
                    'capital' => $capital,
                    'currency_code' => $currencyCode,
                    'region' => $region,
                    'subregion' => $subregion,
                    'is_active' => true,
                ]
            );

            if (in_array($iso2, ['IN', 'NP'], true)) {
                // Already live with their own domain — only backfill the new
                // Stage-1 columns, never touch anything else about these rows.
                // Matched by `code` (not country_id): GLOBAL may share a
                // country_id with a real country in some environments, and
                // code is the one column guaranteed unique per marketplace.
                $existing = Marketplace::where('code', $iso2 === 'IN' ? 'INDIA' : 'NEPAL')->first();
                if ($existing) {
                    $existing->update([
                        'url_prefix' => $prefix,
                        'default_language' => 'en',
                        'launch_status' => 'active',
                        'global_fallback' => false,
                        'checkout_enabled' => (bool) $existing->is_active,
                        'local_seller_support' => true,
                        'local_warehouse_support' => true,
                        'local_payment_support' => true,
                    ]);
                }
                continue;
            }

            $marketplaceCode = strtoupper(str_replace([' ', '.'], '', $name));

            Marketplace::firstOrCreate(
                ['code' => $marketplaceCode],
                [
                    'name' => "NeoGiga {$name}",
                    'regional_brand_name' => "NeoGiga {$name}",
                    'description' => "Preview marketplace for {$name} — pricing, stock, sellers and payments not yet launched.",
                    'country_id' => $country->id,
                    'currency_id' => $currencyIds[$currencyCode],
                    'timezone' => $timezone,
                    'locale' => 'en',
                    'default_language' => 'en',
                    'is_active' => false,
                    'is_default' => false,
                    'allow_vendor_registration' => false,
                    'require_vendor_approval' => true,
                    'tax_rate' => 0.00,
                    'supported_languages' => ['en'],
                    'url_prefix' => $prefix,
                    'launch_status' => 'preview',
                    'global_fallback' => false,
                    'checkout_enabled' => false,
                    'redirect_enabled' => false,
                    'local_seller_support' => false,
                    'local_warehouse_support' => false,
                    'local_payment_support' => false,
                ]
            );
        }

        // GLOBAL marketplace: mark it as the fallback + fill its Stage-1 columns.
        Marketplace::where('code', 'GLOBAL')->update([
            'default_language' => 'en',
            'launch_status' => 'active',
            'global_fallback' => true,
            'checkout_enabled' => true,
            'local_seller_support' => true,
            'local_warehouse_support' => true,
            'local_payment_support' => true,
        ]);
    }
}
