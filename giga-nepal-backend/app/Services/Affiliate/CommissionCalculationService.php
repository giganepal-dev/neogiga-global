<?php

namespace App\Services\Affiliate;

use App\Models\Affiliate\Affiliate;
use App\Models\Affiliate\CommissionRule;

/**
 * Server-side commission calculation. The caller passes trusted, server-known
 * values (order total from the orders table) — never client-supplied amounts.
 */
class CommissionCalculationService
{
    /**
     * Resolve the highest-priority live rule applicable to this affiliate/order.
     * Precedence: affiliate-scoped > marketplace > category/product > global,
     * then by `priority` asc, then newest.
     */
    public function resolveRule(Affiliate $affiliate, ?int $marketplaceId = null): ?CommissionRule
    {
        return CommissionRule::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(function ($q) use ($affiliate, $marketplaceId) {
                $q->where('scope', 'global')
                    ->orWhere(fn ($s) => $s->where('scope', 'affiliate')->where('scope_id', $affiliate->id));
                if ($marketplaceId) {
                    $q->orWhere(fn ($s) => $s->where('scope', 'marketplace')->where('scope_id', $marketplaceId));
                }
            })
            ->orderByRaw("CASE scope WHEN 'affiliate' THEN 0 WHEN 'marketplace' THEN 1 WHEN 'product' THEN 2 WHEN 'category' THEN 3 ELSE 4 END")
            ->orderBy('priority')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Compute the commission amount for a given (trusted) order total.
     * Returns 0 when below the rule minimum. Caps at max_commission.
     */
    public function calculate(CommissionRule $rule, float $orderTotal): float
    {
        if ($rule->min_order_total !== null && $orderTotal < (float) $rule->min_order_total) {
            return 0.0;
        }

        $amount = $rule->type === 'fixed'
            ? (float) $rule->rate
            : round($orderTotal * ((float) $rule->rate / 100), 2);

        if ($rule->max_commission !== null) {
            $amount = min($amount, (float) $rule->max_commission);
        }

        return max(0.0, round($amount, 2));
    }
}
