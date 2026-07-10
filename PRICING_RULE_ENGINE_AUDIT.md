# PRICING_RULE_ENGINE_AUDIT (2026-07-10)

Audit for the configurable multi-country Pricing / Margin / Discount / Promotion Rule Engine
codex. Ground rule honoured: **preserve all existing work, extend rather than duplicate.**

## What already exists (reuse — do NOT rebuild)

| Concern | Existing asset | Notes |
|---|---|---|
| Per-marketplace live price | `marketplace_product_prices` (base/sale/cost, currency, tax fields, date range) | the row the customer sees; the engine writes here only via approved apply, never blind |
| Central calc + logging | `App\Services\Pricing\CentralPricingService` | v1 formula (duty→landed USD→×rate→+margin→+tax); `calculate()` logs, `apply()` never overwrites an existing row |
| Calc audit trail | `price_calculation_logs`, `regional_price_history` | append-only; every calc/apply recorded |
| FX | `exchange_rates` (append-only) + `ExchangeRateService` (fresh/staleness) + `ManualExchangeRateProvider` + `currencies.exchange_rate` cache | base→X rates; snapshot-ready |
| Tax | `tax_rules` (marketplace/country/region, effective-dated, %/fixed, compound/inclusive) | consumed by CentralPricingService + RegionalCommerceService |
| Import duty | `import_duty_rules` (country/marketplace/hs_code, %/fixed, effective-dated) + `DutyService` | wired into the formula; empty on prod → inert |
| Quantity tiers | `bulk_price_tiers` (product/variant/marketplace, min/max qty, price, tier_type) | B2B quantity-tier substrate |
| Coupons / gift cards | `coupons`, `coupon_redemptions`, `gift_cards`, `gift_card_transactions` + models | coupon substrate for the discount engine |
| Seller offers / cost | `vendor_product_prices`, `catalog_distributor_offers`, commission rule tables | seller/reseller price ownership substrate |
| Shipping fees | `shipping_fee_rules` | freight-markup substrate |
| Seller visibility scoping | `RegionalVisibilityService` + `RegionalCommercePolicyService` | already scopes prices/offers/stock per marketplace |
| Marketplace context | `MarketplaceContextResolver` + `CountryResolver` (GeoIP CF-IPCountry, crawler detection) | geo/preview substrate for promo targeting |

## What is MISSING (the actual gap this codex fills)

- **No general pricing-rule engine.** There is no `pricing_rules` / `pricing_rule_*` family. Today
  pricing is a single hardcoded formula in `CentralPricingService` plus per-row prices. There is no
  data-driven, versioned, scope-precedence rule system (percentage/fixed/margin-target/floor/ceiling,
  owner + scope + priority + stacking + approval).
- **No `promotions` table or promotion engine.** Only `coupons`/`gift_cards` exist. There is no
  `promotions` / `promotion_*` family, no automatic/BOGO/bundle/flash-sale/geo-targeted promotions,
  no promotion scheduling, budget, usage-limit, or redemption engine beyond coupons.
- **No price simulator, rule trace, price-floor/margin-floor rule tables, loss-leader approvals, or
  price-exception workflow.**
- **No admin pricing/promotion UI, wizards, or storefront promo display / cart revalidation.**

## This execution's scope (bounded, mirrors the Global Commerce codex approach)

**BUILT this cycle (inert, tested, not deployed until approved):**
1. This audit + `PRICING_RULE_PRECEDENCE.md` + `PRICE_FLOOR_AND_MARGIN_POLICY.md` +
   `PRICE_SIMULATOR_GUIDE.md` (real), plus honest scope/plan guides for the deferred pieces.
2. Core pricing-rule schema (`pricing_rule_sets`, `pricing_rules`, `pricing_rule_conditions`,
   `pricing_rule_actions`, `pricing_rule_scopes`, `pricing_rule_versions`, `pricing_rule_approvals`,
   `price_rounding_rules`, `price_floor_rules`, `margin_floor_rules`) — additive, `hasTable`-guarded,
   data-driven, marketplace-scoped, versioned, auditable.
3. `PricingRuleResolver` — deterministic scope precedence (most-specific → least), the four core
   calculation methods (percentage_markup, fixed_markup, fixed_selling_price, margin_target),
   stackable/stop_processing handling, a full rule trace, and price-floor + margin-floor enforcement.
4. `PriceSimulator` — full dry-run breakdown that never writes.
5. Feature tests for every calc method, precedence, stop_processing, floor block, rounding,
   simulator-no-write, and marketplace isolation.

**DEFERRED to `NEXT_PRICING_PHASE_BACKLOG.md` (documented, not built this cycle):** the full promotion
engine (`promotions`/`promotion_*`), geo-targeted/scheduled/budgeted promotions, admin UI + pricing/
promotion wizards, storefront promo display, cart/checkout revalidation, permission wiring, and the
performance/caching read-model layer. These are each substantial and must not be half-built.

## Invariants enforced (from the codex)

- Data-driven only — **no pricing logic in controllers/views.**
- Markup% ≠ margin%: `margin_target ⇒ price = cost / (1 − margin/100)`, distinct from
  `percentage_markup ⇒ price = cost × (1 + markup/100)`.
- Default cost basis = **landed unit cost**, never raw purchase price when freight/duty exist unless
  the rule explicitly selects another basis.
- Fixed monetary rules **must** record currency; snapshot source amount + currency + rate + converted
  amount + timestamp.
- A discount/rule may **never** push final price below the configured floor without an authorized,
  time-limited, audited loss-leader exception.
- Nothing is activated live; no real product price changes until simulation + approval + migration
  validation + regression tests pass.
