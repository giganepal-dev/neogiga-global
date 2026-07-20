<?php

namespace Database\Seeders;

use App\Models\Pricing\PricingRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InstitutionalDiscountRuleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('pricing_rules')) {
            return;
        }

        foreach (config('b2b_institutional.discounts', []) as $segment => $percent) {
            if ($percent <= 0) {
                continue;
            }

            $code = 'institutional-discount-'.Str::slug($segment, '-');

            PricingRule::updateOrCreate(
                ['code' => $code],
                [
                    'name' => ucfirst($segment).' institutional discount',
                    'scope_type' => 'customer_segment',
                    'customer_segment' => $segment,
                    'action_type' => 'percentage_markup',
                    'action_value' => -abs((float) $percent),
                    'cost_basis' => 'landed_unit',
                    'priority' => 100,
                    'active' => true,
                    'approval_status' => 'approved',
                    'stackable' => false,
                    'stop_processing' => false,
                    'version' => 1,
                    'reason' => 'Default institutional buyer discount for '.$segment,
                ]
            );
        }
    }
}
