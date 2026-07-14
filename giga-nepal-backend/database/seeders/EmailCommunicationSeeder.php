<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmailCommunicationSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('email_sender_profiles')) {
            return;
        }

        $regions = [
            'GLOBAL' => ['name' => 'NeoGiga Global', 'base_url' => 'https://neogiga.com', 'currency' => 'USD', 'marketing_domain' => 'news.neogiga.com', 'transactional_domain' => 'mail.neogiga.com'],
            'INDIA' => ['name' => 'NeoGiga India', 'base_url' => 'https://neogiga.in', 'currency' => 'INR', 'marketing_domain' => 'news.neogiga.in', 'transactional_domain' => 'mail.neogiga.in'],
            'NEPAL' => ['name' => 'Giga Nepal', 'base_url' => 'https://giganepal.com', 'currency' => 'NPR', 'marketing_domain' => 'news.giganepal.com', 'transactional_domain' => 'mail.giganepal.com'],
        ];
        foreach ($regions as $code => $region) {
            $marketplaceId = DB::table('marketplaces')->whereRaw('UPPER(code) = ?', [$code])->value('id');
            foreach (['marketing', 'transactional'] as $purpose) {
                $domain = $region[$purpose.'_domain'];
                $profileName = $region['name'].' '.ucfirst($purpose);
                if (! DB::table('email_sender_profiles')->where('name', $profileName)->exists()) {
                    DB::table('email_sender_profiles')->insert([
                        'marketplace_id' => $marketplaceId, 'name' => $profileName, 'purpose' => $purpose,
                        'from_name' => $region['name'], 'from_email' => ($purpose === 'marketing' ? 'news@' : 'orders@').$domain,
                        'reply_to' => 'support@'.parse_url($region['base_url'], PHP_URL_HOST), 'domain' => $domain,
                        'base_url' => $region['base_url'], 'default_currency' => $region['currency'], 'default_language' => 'en',
                        'is_verified' => false, 'is_enabled' => false,
                        'branding' => json_encode(['brand_name' => $region['name'], 'base_url' => $region['base_url']]),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                if (Schema::hasTable('email_domains') && ! DB::table('email_domains')->where('domain', $domain)->exists()) {
                    DB::table('email_domains')->insert([
                        'marketplace_id' => $marketplaceId, 'domain' => $domain, 'purpose' => $purpose,
                        'return_path_domain' => 'return.'.$domain, 'bounce_domain' => 'bounce.'.$domain,
                        'spf_status' => 'unknown', 'dkim_status' => 'unknown', 'dmarc_status' => 'unknown',
                        'provider_verification_status' => 'unknown', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }

        foreach (['Product news', 'Technical newsletters', 'Promotions', 'Regional offers', 'Brand updates', 'Manufacturer updates', 'Events and webinars'] as $category) {
            if (! DB::table('newsletter_categories')->where('name', $category)->exists()) {
                DB::table('newsletter_categories')->insert(['name' => $category, 'slug' => (string) str($category)->slug(), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        $globalId = DB::table('marketplaces')->whereRaw('UPPER(code) = ?', ['GLOBAL'])->value('id');
        foreach (['Support' => 'support', 'Orders' => 'orders', 'Billing' => 'billing', 'RFQ' => 'rfq', 'Seller communication' => 'seller'] as $label => $purpose) {
            $name = 'NeoGiga Global '.$label;
            if (! DB::table('email_sender_profiles')->where('name', $name)->exists()) {
                DB::table('email_sender_profiles')->insert([
                    'marketplace_id' => $globalId, 'name' => $name, 'purpose' => $purpose,
                    'from_name' => 'NeoGiga '.$label, 'from_email' => str_replace(' ', '-', $purpose).'@mail.neogiga.com',
                    'reply_to' => 'support@neogiga.com', 'domain' => 'mail.neogiga.com', 'base_url' => 'https://neogiga.com',
                    'default_currency' => 'USD', 'default_language' => 'en', 'is_verified' => false, 'is_enabled' => false,
                    'branding' => json_encode(['brand_name' => 'NeoGiga Global', 'base_url' => 'https://neogiga.com']),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }
}
