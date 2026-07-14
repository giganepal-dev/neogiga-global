<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class CustomerPreferenceService
{
    public function update(int $customerId, array $preferences): void
    {
        DB::table('customer_preferences')->updateOrInsert(
            ['customer_profile_id' => $customerId],
            ['category_interests' => json_encode($preferences['category_interests'] ?? []), 'brand_interests' => json_encode($preferences['brand_interests'] ?? []), 'channels' => json_encode($preferences['channels'] ?? []), 'newsletter_categories' => json_encode($preferences['newsletter_categories'] ?? []), 'analytics_opt_out' => (bool) ($preferences['analytics_opt_out'] ?? false), 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
