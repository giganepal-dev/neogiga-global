# CENTRAL_PRICING_ENGINE_GUIDE

**Status (updated): v1 formula service now BUILT and committed locally (717c6e8,
d81fef0) — NOT yet deployed to prod. It is inert by default: nothing calls it
automatically, no scheduler, no real exchange rates, and import_duty_rules is
empty on prod so duty always resolves to 0. See "v1 service" below.**

## v1 service (built)
- `App\Services\Pricing\CentralPricingService` — `calculate(productId, marketplace)`
  computes `duty_usd = base_cost_usd * duty% ; landed = base_cost + duty_usd ;
  local_cost = landed * fresh_rate ; pre_tax = local_cost + margin ;
  final = pre_tax + tax`, appends one `price_calculation_logs` row, and returns
  null (logging nothing) if base cost or a fresh rate is missing. `apply(log)`
  creates a `marketplace_product_prices` row ONLY when none exists (never
  overwrites) and records `regional_price_history`.
- `App\Services\Pricing\ExchangeRateService` — append-only record + latest/fresh
  (48h staleness) lookup; base→X records also refresh `currencies.exchange_rate`.
- `App\Services\Pricing\DutyService` — `import_duty_rules` percentage lookup +
  best-effort HS code from `product_countries_of_origin`.
- `pricing:refresh-exchange-rates` artisan command (manual/config provider, NOT
  scheduled).
- Coverage: `tests/Feature/CentralPricingEngineTest.php` (14 tests / 42 assertions).
- Still missing (Stage 2/3 continuation): a live exchange-rate provider + schedule,
  freight/insurance/warehouse/payment-fee inputs (all 0 today), regional price
  approvals, and any automatic invocation. Deploy is pending explicit user review.

---

**Original Stage-2-foundations note (historical):**

## What exists today
- `marketplace_product_prices` (pre-existing) — the real, live per-marketplace price a customer
  sees: base_price, sale_price, cost_price, currency_code, is_tax_inclusive, tax_rate, date range.
- `price_calculation_logs` (new this cycle) — an empty, ready-to-use append-only table for storing
  a full breakdown (base_cost_usd, exchange_rate, duty_amount, tax_amount, freight_amount,
  margin_amount, final_price, calculation_version) once a formula service writes to it.
- `regional_price_history` (new this cycle) — an empty, ready-to-use audit trail for changes to
  `marketplace_product_prices`.

## What does NOT exist yet
`global_product_costs`, `supplier_costs`, `marketplace_price_rules`, `country_tax_rules` (distinct
from the existing `tax_rules`), `hs_code_tax_rules`, `freight_rate_cards`, `warehouse_cost_rules`,
`payment_fee_rules`, `marketplace_margin_rules`, `rounding_rules`, `regional_price_approvals` — none
of these tables or the formula service that would populate `price_calculation_logs` were built this
cycle. This is intentional: the execution scope for this cycle was "foundational database/services
for Stage 2," not a live formula (see GLOBAL_COMMERCE_IMPLEMENTATION_PLAN.md).

## When this is built (Stage 2 continuation / Stage 3)
The formula from the original spec:
```
landed_cost_usd = base_cost_usd + freight_usd + insurance_usd + customs_cost_usd + import_duty_usd
local_cost       = landed_cost_usd * exchange_rate
pre_tax_price    = local_cost + warehouse_cost + payment_fee + operating_cost + margin
final_price      = pre_tax_price + VAT/GST
```
Price priority to implement: (1) active approved regional override → (2) seller offer price →
(3) calculated regional price → (4) global RFQ-only fallback. Every calculation must write a
`price_calculation_logs` row and must never silently overwrite a manually-approved
`marketplace_product_prices` row. See REGIONAL_PRICING_AUDIT.md for the full gap list.
