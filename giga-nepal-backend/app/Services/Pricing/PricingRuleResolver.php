<?php

namespace App\Services\Pricing;

use App\Models\Pricing\MarginFloorRule;
use App\Models\Pricing\PriceFloorRule;
use App\Models\Pricing\PriceRoundingRule;
use App\Models\Pricing\PricingRule;
use Illuminate\Support\Collection;

/**
 * Deterministic, data-driven pricing-rule resolver (see PRICING_RULE_PRECEDENCE.md).
 *
 * Only active + approved rules that are in their time window and whose scope
 * matches the context are considered. Rules are ordered most-specific →
 * least-specific, then priority desc, then version desc. One primary pricing
 * rule sets the base selling price from the cost basis; additional stackable
 * primaries and modifier rules refine it. Every rule — applied or skipped — is
 * recorded in a trace. Price-floor and margin-floor violations are reported as
 * `blocked` with a reason; the resolver never silently clamps below a floor.
 */
class PricingRuleResolver
{
    /** Higher = more specific = higher precedence. */
    private const SPECIFICITY = [
        'product' => 100, 'generic_product' => 95, 'quantity_tier' => 90,
        'seller' => 80, 'reseller' => 80, 'brand' => 70, 'manufacturer' => 70,
        'subcategory' => 65, 'category' => 60, 'warehouse' => 55,
        'postal_group' => 50, 'city' => 48, 'state' => 46, 'region' => 44,
        'country' => 40, 'customer_segment' => 35, 'b2b_account' => 35,
        'marketplace' => 20, 'global' => 0,
    ];

    private const PRIMARY = ['percentage_markup', 'fixed_markup', 'fixed_selling_price', 'margin_target'];

    /** Fixed order in which modifier rules are applied after the base price. */
    private const MODIFIER_ORDER = [
        'freight_markup' => 1, 'payment_fee_markup' => 2,
        'currency_adjustment' => 3, 'exchange_rate_buffer' => 4,
        'minimum_price' => 5, 'price_floor' => 5,
        'maximum_price' => 6, 'price_ceiling' => 6,
        'rounding' => 9,
    ];

    public function __construct(private readonly ExchangeRateService $rates)
    {
    }

    /**
     * @return Collection<int,PricingRule> ordered by precedence
     */
    public function applicableRules(PricingContext $ctx): Collection
    {
        $now = $ctx->time();

        return PricingRule::query()
            ->where('active', true)
            ->where('approval_status', 'approved')
            ->where(fn ($q) => $q->whereNull('marketplace_id')->orWhere('marketplace_id', $ctx->marketplace->id))
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->get()
            ->filter(fn (PricingRule $r) => $this->matches($r, $ctx))
            ->sort(function (PricingRule $a, PricingRule $b) {
                return [$this->specificity($b), $b->priority, $b->version, $b->id]
                    <=> [$this->specificity($a), $a->priority, $a->version, $a->id];
            })
            ->values();
    }

    /**
     * Full price resolution with trace. Returns an array (never writes).
     */
    public function price(PricingContext $ctx): array
    {
        $cost = $ctx->costBasisAmount;
        $rules = $this->applicableRules($ctx);

        $running = null;
        $primaryLocked = false; // a non-stackable primary has fixed the base
        $applied = [];
        $trace = [];
        $modifiers = [];

        foreach ($rules as $rule) {
            if (in_array($rule->action_type, self::PRIMARY, true)) {
                if ($running === null) {
                    $running = $this->applyPrimary($rule, $cost, $cost, $ctx, $trace, $applied);
                    if ($rule->stop_processing) {
                        $trace[] = $this->line($rule, true, 'applied; stop_processing halted evaluation', $running);
                        return $this->finalize($ctx, $cost, $running, $applied, $trace);
                    }
                    $primaryLocked = ! $rule->stackable;
                } elseif (! $primaryLocked && $rule->stackable) {
                    $running = $this->applyPrimary($rule, $cost, $running, $ctx, $trace, $applied);
                    if ($rule->stop_processing) {
                        return $this->finalize($ctx, $cost, $running, $applied, $trace);
                    }
                } else {
                    $trace[] = $this->line($rule, false, 'skipped: superseded by higher-precedence primary rule', $running);
                }
            } else {
                $modifiers[] = $rule;
            }
        }

        if ($running === null) {
            $running = $cost;
            $trace[] = ['rule_id' => null, 'code' => null, 'scope_type' => null, 'action_type' => 'none',
                'applied' => true, 'reason' => 'no primary pricing rule matched; using cost basis as price', 'running_price' => round($running, 4)];
        }

        usort($modifiers, fn ($a, $b) => (self::MODIFIER_ORDER[$a->action_type] ?? 99) <=> (self::MODIFIER_ORDER[$b->action_type] ?? 99));
        foreach ($modifiers as $rule) {
            $running = $this->applyModifier($rule, $running, $ctx, $trace, $applied);
        }

        return $this->finalize($ctx, $cost, $running, $applied, $trace);
    }

    private function applyPrimary(PricingRule $rule, float $cost, float $input, PricingContext $ctx, array &$trace, array &$applied): float
    {
        $value = (float) $rule->action_value;

        switch ($rule->action_type) {
            case 'percentage_markup':
                $result = $input * (1 + $value / 100);
                break;
            case 'margin_target':
                // price = cost / (1 - margin/100); margin must be < 100
                if ($value >= 100) {
                    $trace[] = $this->line($rule, false, 'skipped: margin_target >= 100% is impossible', $input);
                    return $input;
                }
                $result = $cost / (1 - $value / 100);
                break;
            case 'fixed_markup':
                $add = $this->toContextCurrency($value, $rule->action_currency, $ctx);
                if ($add === null) {
                    $trace[] = $this->line($rule, false, "skipped: fixed_markup in {$rule->action_currency} has no exchange rate to {$ctx->currencyCode}", $input);
                    return $input;
                }
                $result = $input + $add;
                break;
            case 'fixed_selling_price':
                $fixed = $this->toContextCurrency($value, $rule->action_currency, $ctx);
                if ($fixed === null) {
                    $trace[] = $this->line($rule, false, "skipped: fixed_selling_price in {$rule->action_currency} has no exchange rate to {$ctx->currencyCode}", $input);
                    return $input;
                }
                $result = $fixed;
                break;
            default:
                return $input;
        }

        $applied[] = $rule->id;
        $trace[] = $this->line($rule, true, 'applied', $result);

        return $result;
    }

    private function applyModifier(PricingRule $rule, float $running, PricingContext $ctx, array &$trace, array &$applied): float
    {
        $value = (float) $rule->action_value;

        switch ($rule->action_type) {
            case 'minimum_price':
            case 'price_floor':
                $floor = $this->toContextCurrency($value, $rule->action_currency, $ctx);
                $result = $floor === null ? $running : max($running, $floor);
                break;
            case 'maximum_price':
            case 'price_ceiling':
                $ceil = $this->toContextCurrency($value, $rule->action_currency, $ctx);
                $result = $ceil === null ? $running : min($running, $ceil);
                break;
            case 'freight_markup':
            case 'payment_fee_markup':
                // fixed when a currency is given, else percent of running
                if ($rule->action_currency) {
                    $add = $this->toContextCurrency($value, $rule->action_currency, $ctx);
                    $result = $add === null ? $running : $running + $add;
                } else {
                    $result = $running + $running * $value / 100;
                }
                break;
            case 'currency_adjustment':
            case 'exchange_rate_buffer':
                $result = $running * (1 + $value / 100);
                break;
            case 'rounding':
                $result = $this->round($running, $ctx, $value);
                break;
            default:
                $trace[] = $this->line($rule, false, "skipped: unsupported modifier action_type {$rule->action_type}", $running);
                return $running;
        }

        if ($result !== $running || in_array($rule->action_type, ['freight_markup', 'payment_fee_markup', 'currency_adjustment', 'exchange_rate_buffer', 'rounding'], true)) {
            $applied[] = $rule->id;
            $trace[] = $this->line($rule, true, 'applied', $result);
        } else {
            $trace[] = $this->line($rule, false, 'no effect (clamp not binding)', $running);
        }

        return $result;
    }

    private function finalize(PricingContext $ctx, float $cost, float $running, array $applied, array $trace): array
    {
        $final = round($running, 4);
        $grossMargin = $final > 0 ? round(($final - $cost) / $final * 100, 4) : 0.0;

        $blockReasons = [];
        foreach ($this->floorRules($ctx) as $floor) {
            if ($floor->min_absolute_price !== null) {
                $min = $this->toContextCurrency((float) $floor->min_absolute_price, $floor->currency_code, $ctx);
                if ($min !== null && $final < $min) {
                    $blockReasons[] = "price {$final} below absolute floor {$min} {$ctx->currencyCode} (price_floor_rule #{$floor->id})";
                }
            }
        }
        foreach ($this->marginRules($ctx) as $mr) {
            if ($mr->min_gross_margin_percent !== null && $grossMargin < (float) $mr->min_gross_margin_percent) {
                $blockReasons[] = "gross margin {$grossMargin}% below floor {$mr->min_gross_margin_percent}% (margin_floor_rule #{$mr->id})";
            }
        }

        return [
            'cost_basis' => round($cost, 4),
            'currency' => $ctx->currencyCode,
            'final_price' => $final,
            'gross_margin_percent' => $grossMargin,
            'blocked' => $blockReasons !== [],
            'block_reasons' => $blockReasons,
            'applied_rule_ids' => array_values(array_unique($applied)),
            'trace' => $trace,
        ];
    }

    /** Convert a monetary amount from $currency into the context currency; null if no rate. */
    private function toContextCurrency(float $amount, ?string $currency, PricingContext $ctx): ?float
    {
        $currency = strtoupper((string) ($currency ?: $ctx->currencyCode));
        $target = strtoupper($ctx->currencyCode);

        if ($currency === $target) {
            return $amount;
        }

        $base = $this->rates->baseCurrency();
        if ($currency === $base) {
            $rate = $this->rates->freshRate($base, $target);
            return $rate ? $amount * (float) $rate->rate : null;
        }
        if ($target === $base) {
            $rate = $this->rates->freshRate($base, $currency);
            return $rate ? $amount / (float) $rate->rate : null;
        }
        // cross rate via base
        $r1 = $this->rates->freshRate($base, $currency);
        $r2 = $this->rates->freshRate($base, $target);
        return ($r1 && $r2) ? $amount / (float) $r1->rate * (float) $r2->rate : null;
    }

    private function round(float $amount, PricingContext $ctx, float $inlineIncrement): float
    {
        $rule = PriceRoundingRule::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('marketplace_id')->orWhere('marketplace_id', $ctx->marketplace->id))
            ->where(fn ($q) => $q->whereNull('currency_code')->orWhere('currency_code', $ctx->currencyCode))
            ->orderByRaw('marketplace_id is null')
            ->first();

        $increment = $inlineIncrement > 0 ? $inlineIncrement : ($rule ? (float) $rule->increment : 0.01);
        $strategy = $rule->strategy ?? 'nearest';
        if ($increment <= 0) {
            $increment = 0.01;
        }

        $units = $amount / $increment;
        $rounded = match ($strategy) {
            'up' => ceil($units) * $increment,
            'down' => floor($units) * $increment,
            'charm' => (floor($amount) + ($rule && $rule->charm_ending !== null ? (float) $rule->charm_ending : 0.99)),
            default => round($units) * $increment,
        };

        return round($rounded, 4);
    }

    /** @return Collection<int,PriceFloorRule> */
    private function floorRules(PricingContext $ctx): Collection
    {
        return PriceFloorRule::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('marketplace_id')->orWhere('marketplace_id', $ctx->marketplace->id))
            ->get();
    }

    /** @return Collection<int,MarginFloorRule> */
    private function marginRules(PricingContext $ctx): Collection
    {
        return MarginFloorRule::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('marketplace_id')->orWhere('marketplace_id', $ctx->marketplace->id))
            ->get();
    }

    private function specificity(PricingRule $rule): int
    {
        return self::SPECIFICITY[$rule->scope_type] ?? 0;
    }

    /** Scope + gating-condition match for a rule against the context. */
    private function matches(PricingRule $rule, PricingContext $ctx): bool
    {
        // Quantity window (applies whenever set, any scope)
        if ($rule->min_quantity !== null && $ctx->quantity < $rule->min_quantity) {
            return false;
        }
        if ($rule->max_quantity !== null && $ctx->quantity > $rule->max_quantity) {
            return false;
        }
        // Customer segment gate
        if ($rule->customer_segment !== null && $rule->customer_segment !== '' && $rule->customer_segment !== $ctx->customerSegment) {
            return false;
        }

        return match ($rule->scope_type) {
            'global' => true,
            'marketplace' => $rule->marketplace_id === null || $rule->marketplace_id === $ctx->marketplace->id,
            'product', 'generic_product' => (int) $rule->scope_product_id === $ctx->productId,
            'category', 'subcategory' => $ctx->categoryId !== null && (int) $rule->scope_category_id === $ctx->categoryId,
            'brand' => $ctx->brandId !== null && (int) $rule->scope_brand_id === $ctx->brandId,
            'manufacturer' => $ctx->manufacturerId !== null && (int) $rule->scope_manufacturer_id === $ctx->manufacturerId,
            'seller', 'reseller' => $ctx->sellerId !== null && (int) $rule->scope_seller_id === $ctx->sellerId,
            'warehouse' => $ctx->warehouseId !== null && (int) $rule->scope_warehouse_id === $ctx->warehouseId,
            'country' => $ctx->countryId !== null && (int) $rule->scope_country_id === $ctx->countryId,
            'customer_segment', 'b2b_account' => $rule->customer_segment !== null && $rule->customer_segment === $ctx->customerSegment,
            'quantity_tier' => true, // already gated by min/max above
            // region/state/city/postal handled only when the context carries them; default false to avoid over-broad matches
            default => false,
        };
    }

    private function line(PricingRule $rule, bool $applied, string $reason, float $running): array
    {
        return [
            'rule_id' => $rule->id,
            'code' => $rule->code,
            'scope_type' => $rule->scope_type,
            'action_type' => $rule->action_type,
            'action_value' => $rule->action_value,
            'action_currency' => $rule->action_currency,
            'applied' => $applied,
            'reason' => $reason,
            'running_price' => round($running, 4),
        ];
    }
}
