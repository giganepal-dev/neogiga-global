<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds default tax zones with verified official rates.
 * All rates require admin review before activation in production.
 * Source: official tax authority publications as of 2026.
 */
class RegionalTaxSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('tax_zones')) return;

        $zones = [
            // Nepal — VAT 13% (Fiscal Year 2025/26)
            ['country_code' => 'NP', 'name' => 'Nepal VAT', 'code' => 'NP-VAT', 'tax_rate' => 13.00, 'is_compound' => false, 'is_inclusive' => false, 'priority' => 10],
            // India — GST standard rate for electronics (18%)
            ['country_code' => 'IN', 'name' => 'India GST', 'code' => 'IN-GST', 'tax_rate' => 18.00, 'is_compound' => false, 'is_inclusive' => false, 'priority' => 10],
            // Pakistan — Sales Tax 18% (FY 2025/26)
            ['country_code' => 'PK', 'name' => 'Pakistan Sales Tax', 'code' => 'PK-ST', 'tax_rate' => 18.00, 'is_compound' => false, 'is_inclusive' => true, 'priority' => 10],
            // Bangladesh — VAT 15%
            ['country_code' => 'BD', 'name' => 'Bangladesh VAT', 'code' => 'BD-VAT', 'tax_rate' => 15.00, 'is_compound' => false, 'is_inclusive' => false, 'priority' => 10],
            // Sri Lanka — VAT 18% (effective 2024)
            ['country_code' => 'LK', 'name' => 'Sri Lanka VAT', 'code' => 'LK-VAT', 'tax_rate' => 18.00, 'is_compound' => false, 'is_inclusive' => false, 'priority' => 10],
            // Australia — GST 10%
            ['country_code' => 'AU', 'name' => 'Australia GST', 'code' => 'AU-GST', 'tax_rate' => 10.00, 'is_compound' => false, 'is_inclusive' => true, 'priority' => 10],
            // Bhutan — Sales Tax 7% (as of FY 2024/25)
            ['country_code' => 'BT', 'name' => 'Bhutan Sales Tax', 'code' => 'BT-ST', 'tax_rate' => 7.00, 'is_compound' => false, 'is_inclusive' => false, 'priority' => 10],
            // UAE — VAT 5%
            ['country_code' => 'AE', 'name' => 'UAE VAT', 'code' => 'AE-VAT', 'tax_rate' => 5.00, 'is_compound' => false, 'is_inclusive' => true, 'priority' => 10],
            // Saudi Arabia — VAT 15%
            ['country_code' => 'SA', 'name' => 'Saudi Arabia VAT', 'code' => 'SA-VAT', 'tax_rate' => 15.00, 'is_compound' => false, 'is_inclusive' => true, 'priority' => 10],
            // Malaysia — SST 10% (sales tax on electronics)
            ['country_code' => 'MY', 'name' => 'Malaysia SST', 'code' => 'MY-SST', 'tax_rate' => 10.00, 'is_compound' => false, 'is_inclusive' => false, 'priority' => 10],
            // Global — 0% default
            ['country_code' => 'XX', 'name' => 'Global (no tax)', 'code' => 'GLOBAL-ZERO', 'tax_rate' => 0.00, 'is_compound' => false, 'is_inclusive' => false, 'priority' => 1],
        ];

        foreach ($zones as $zone) {
            $country = DB::table('countries')->where('iso_code_2', $zone['country_code'])->first();
            $marketplace = DB::table('marketplaces')->where('country_iso2', $zone['country_code'])->first();

            $exists = DB::table('tax_zones')
                ->where('code', $zone['code'])
                ->exists();

            if (! $exists) {
                DB::table('tax_zones')->insert(array_merge($zone, [
                    'country_id' => $country?->id,
                    'marketplace_id' => $marketplace?->id,
                    'is_active' => false, // Requires admin approval before activation
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // Also seed source registry entries
        if (Schema::hasTable('tax_tariff_source_registry')) {
            $sources = [
                ['NP', 'Nepal Inland Revenue Department', 'official_tax_authority', 'ird.gov.np', 'manual_verified'],
                ['IN', 'CBIC GST Portal', 'official_tax_authority', 'cbic-gst.gov.in', 'manual_verified'],
                ['PK', 'FBR Pakistan', 'official_tax_authority', 'fbr.gov.pk', 'manual_verified'],
                ['BD', 'NBR Bangladesh', 'official_tax_authority', 'nbr.gov.bd', 'manual_verified'],
                ['LK', 'IRD Sri Lanka', 'official_tax_authority', 'ird.gov.lk', 'manual_verified'],
                ['AU', 'ATO Australia', 'official_tax_authority', 'ato.gov.au', 'manual_verified'],
                ['BT', 'DRC Bhutan', 'official_tax_authority', 'drc.gov.bt', 'manual_verified'],
            ];

            foreach ($sources as [$code, $name, $type, $domain, $authType]) {
                $exists = DB::table('tax_tariff_source_registry')
                    ->where('country_code', $code)
                    ->where('source_type', $type)
                    ->exists();

                if (! $exists) {
                    DB::table('tax_tariff_source_registry')->insert([
                        'country_code' => $code,
                        'source_name' => $name,
                        'source_type' => $type,
                        'official_domain' => $domain,
                        'authentication_type' => $authType,
                        'update_frequency' => 'manual',
                        'active' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
