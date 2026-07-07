<?php

namespace Database\Seeders;

use App\Models\Affiliate\CommissionRule;
use Illuminate\Database\Seeder;

/**
 * Default affiliate commission rule. Idempotent. Run explicitly:
 *   php artisan db:seed --class=Database\\Seeders\\AffiliateSeeder --force
 * Not wired into DatabaseSeeder to keep the default seed set unchanged.
 */
class AffiliateSeeder extends Seeder
{
    public function run(): void
    {
        CommissionRule::firstOrCreate(
            ['name' => 'Default global referral 5%'],
            [
                'scope' => 'global',
                'type' => 'percentage',
                'rate' => 5,
                'min_order_total' => null,
                'max_commission' => null,
                'priority' => 100,
                'is_active' => true,
            ],
        );
    }
}
