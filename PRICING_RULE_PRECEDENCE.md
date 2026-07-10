# PRICING_RULE_PRECEDENCE (2026-07-10)

How `App\Services\Pricing\PricingRuleResolver` decides which rules apply and in what order. This
is BUILT and tested (`tests/Feature/PricingRuleEngineTest.php`).

## Eligibility (a rule is even considered only if all hold)
- `active = true` AND `approval_status = 'approved'` (draft/pending/rejected/suspended never price).
- `marketplace_id` is null (applies everywhere) OR equals the context marketplace.
- `now` is within `[starts_at, ends_at]` (nulls = open-ended).
- Scope matches the context (see specificity ladder), AND any quantity window
  (`min_quantity`/`max_quantity`) and `customer_segment` gate is satisfied.

## Specificity ladder (most specific wins)
```
product 100 · generic_product 95 · quantity_tier 90 · seller/reseller 80 · brand/manufacturer 70 ·
subcategory 65 · category 60 · warehouse 55 · postal_group 50 · city 48 · state 46 · region 44 ·
country 40 · customer_segment/b2b_account 35 · marketplace 20 · global 0
```
Ordering key per rule: `[specificity, priority, version, id]`, all descending. So within the same
scope, higher `priority` wins; ties break to the most recent `version`, then newest `id`.

## Primary vs modifier
- **Primary** (`percentage_markup`, `fixed_markup`, `fixed_selling_price`, `margin_target`) sets the
  base selling price from the cost basis. The single highest-precedence primary applies. If it is
  `stackable`, subsequent stackable primaries compound onto the running price; a non-stackable
  primary locks the base and lower ones are traced as **skipped: superseded**. `stop_processing`
  halts immediately.
- **Modifier** (`minimum_price`/`price_floor`, `maximum_price`/`price_ceiling`, `freight_markup`,
  `payment_fee_markup`, `currency_adjustment`, `exchange_rate_buffer`, `rounding`) applies after, in
  a fixed order: markups → clamps → rounding.

## Effective precedence (matches the codex's 1→10 intent)
1. Approved product fixed-price override (`product` + `fixed_selling_price`).
2. Product + seller + marketplace rule.
3. Product + marketplace rule.
4. Seller + category + marketplace rule.
5. Brand/manufacturer + marketplace rule.
6. Category/subcategory + marketplace rule.
7. Seller/reseller default.
8. Country/marketplace default.
9. Global default.
10. No primary rule ⇒ cost basis is used as the price and the trace says so (RFQ-only fallback is a
    downstream decision, not the resolver's job).

## Rule trace
`price()` returns `trace[]` — every eligible rule with `applied` (bool), `reason`, and the
`running_price` after it. Skips carry a reason (superseded / clamp-not-binding / no-FX-rate /
impossible-margin). This is the "full rule trace showing which rules were applied or skipped" the
codex requires.

## Calculation methods (markup ≠ margin)
- `percentage_markup`: `price = basis × (1 + value/100)`  (cost 1.00, 25% ⇒ 1.25)
- `fixed_markup`: `price = basis + value` (currency-converted; skipped if no FX rate)
- `fixed_selling_price`: `price = value` (override)
- `margin_target`: `price = cost / (1 − value/100)`  (cost 1.00, 30% ⇒ 1.4286, **not** 1.30)

Fixed monetary amounts in a foreign currency are converted via `ExchangeRateService` using a fresh
rate; if none exists the rule is **skipped and traced**, never applied with a guessed rate.
