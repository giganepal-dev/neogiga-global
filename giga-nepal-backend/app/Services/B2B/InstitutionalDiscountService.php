<?php

namespace App\Services\B2B;

use App\Models\B2B\B2BAccount;
use App\Models\Pricing\PricingRule;
use Illuminate\Support\Facades\Schema;

class InstitutionalDiscountService
{
    public function percentForAccount(B2BAccount $account): float
    {
        $type = strtolower((string) ($account->type ?? 'corporate'));

        if (Schema::hasTable('pricing_rules')) {
            $rule = PricingRule::query()
                ->where('active', true)
                ->where('approval_status', 'approved')
                ->where('scope_type', 'customer_segment')
                ->where('customer_segment', $type)
                ->when($account->marketplace_id, fn ($q) => $q->where(function ($query) use ($account) {
                    $query->whereNull('marketplace_id')
                        ->orWhere('marketplace_id', $account->marketplace_id);
                }))
                ->orderByDesc('priority')
                ->first();

            if ($rule && $rule->action_type === 'percentage_markup' && $rule->action_value < 0) {
                return abs((float) $rule->action_value);
            }

            if ($rule && in_array($rule->action_type, ['fixed_markup', 'percentage_markup'], true)) {
                $value = (float) $rule->action_value;
                if ($value < 0) {
                    return abs($value);
                }
            }
        }

        $marketplaceKey = $account->marketplace_id
            ? "marketplace_overrides.{$account->marketplace_id}.{$type}"
            : null;

        if ($marketplaceKey && config()->has("b2b_institutional.{$marketplaceKey}")) {
            return (float) config("b2b_institutional.{$marketplaceKey}");
        }

        return (float) config("b2b_institutional.discounts.{$type}", config('b2b_institutional.discounts.corporate', 0));
    }

    public function applyDiscount(float $unitPrice, B2BAccount $account): array
    {
        $percent = $this->percentForAccount($account);
        $discounted = round($unitPrice * (1 - ($percent / 100)), 4);

        return [
            'unit_price' => max(0, $discounted),
            'discount_percent' => $percent,
            'discount_amount' => round($unitPrice - $discounted, 4),
        ];
    }
}
