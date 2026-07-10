# NEXT_PRICING_PHASE_BACKLOG (2026-07-10)

Continues from PRICING_RULE_ENGINE_AUDIT.md. Ordered by dependency.

## Built this cycle (local, tested, NOT deployed)
- Migration `2026_07_10_140000_create_pricing_rule_engine_tables.php` (11 tables).
- Models `app/Models/Pricing/*` (PricingRule, PricingRuleSet, PricingRuleCondition, PriceFloorRule,
  MarginFloorRule, PriceRoundingRule).
- Services `app/Services/Pricing/{PricingContext,PricingRuleResolver,PriceSimulator}.php`.
- `tests/Feature/PricingRuleEngineTest.php` (17 tests). Deploy is pending explicit user review.

## Immediate
1. Deploy the pricing-rule engine to prod (migration + models + services) once reviewed — same
   rsync + `config:cache`-last + wallet-canary procedure. Low risk: inert, nothing calls it yet.
2. Cost-basis integration: teach `PricingRuleResolver` to pull the actual cost per `cost_basis`
   (supplier/landed/moving-average/FIFO/standard/global-USD/reseller/manual) instead of receiving a
   pre-landed number, wiring `CentralPricingService`'s landed-cost pipeline in.

## Promotion engine (largest deferred block — codex §7–§14)
3. `promotions` + all `promotion_*` tables.
4. `PromotionResolver` + `getActivePromotions()` / `validatePromotionEligibility()` / `applyPromotion()`
   / `calculateBestOffer()` implementing the §11 calculation order and §12 stacking/conflict logic.
5. Geo-targeted eligibility (`promotion_geographies`) using the confirmed shipping address, reusing
   `CountryResolver` (see GEO_TARGETED_PROMOTION_GUIDE.md).
6. Scheduling (`promotion_schedules`) + scheduled activation/expiry jobs + cache/search invalidation.
7. Budgets + usage limits (`promotion_budgets`, `promotion_usage_limits`, `promotion_redemptions`)
   with auto-stop on budget/redemption/stock/time/margin breach.

## Floor/margin completion
8. `loss_leader_approvals`, `discount_exception_requests`; net & contribution margin (needs freight/
   payment/operating cost inputs); `max_discount_percent`/`max_fixed_discount` enforcement.

## Surfaces (codex §16–§20)
9. Admin UI: `/admin/pricing*` + `/admin/promotions*`, pricing-rule wizard, promotion wizard,
   simulator page, approvals, history, exceptions, calendar, usage, budgets, geography.
10. Seller/reseller panel (cost-view gated by `pricing.cost.view`; edit only own `owner_id`).
11. Storefront promo display (regular/offer price, saving, countdown, eligibility, quantity tiers) —
    never show an unredeemable promo.
12. Cart/checkout revalidation (§20) + immutable order pricing snapshot (rule ids, promo ids,
    exchange rate, tax, calculation version) via `snapshotOrderPricing()`.

## Permissions + performance (codex §23, §25)
13. Add the `pricing.*` / `promotion.*` / `seller.pricing.*` permissions, scoped by marketplace/
    seller/warehouse.
14. Precomputed regional price read-models + active-promotion cache + rule compilation cache + bulk
    recalculation jobs + targeted invalidation. Live engine for cart/checkout/RFQ/simulation;
    cached output for listings/search/category pages.

## Housekeeping
15. GitHub push of local pricing commits still depends on the external sync (no push creds on this
    machine — standing item).
