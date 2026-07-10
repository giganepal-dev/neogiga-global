# DISCOUNT_AND_PROMOTION_ENGINE_GUIDE (2026-07-10)

**Status: DESIGN ONLY — the promotion engine is NOT built this cycle.** The pricing-rule engine
(markup/margin/floor/precedence/simulator) is built and tested; the discount/promotion layer that
sits on top of it is deferred so it is not half-implemented. This guide is the spec to build against.

## What exists to build on
- `coupons`, `coupon_redemptions`, `gift_cards`, `gift_card_transactions` (+ models).
- `bulk_price_tiers` (quantity-tier substrate).
- `PricingRuleResolver` (produces the base price + trace a promotion discounts from).
- `MarketplaceContextResolver` / `CountryResolver` (geo + crawler substrate for targeting).

## What must be built (deferred)
Tables: `promotions`, `promotion_rules`, `promotion_conditions`, `promotion_actions`,
`promotion_scopes`, `promotion_schedules`, `promotion_marketplaces`, `promotion_geographies`,
`promotion_products/categories/brands/manufacturers/sellers`, `promotion_customer_segments`,
`promotion_quantity_tiers`, `promotion_usage_limits`, `promotion_redemptions`, `promotion_budgets`,
`promotion_approvals`, `promotion_audit_logs`.

Discount types: percentage, fixed amount, fixed promo price, BOGO, quantity, bundle, category, brand,
seller, shipping, free-shipping, first-order, B2B, coupon, automatic, flash-sale, clearance.

## Discount calculation order (to implement exactly — codex §11)
1. approved cost basis → 2. base regional selling price (this engine) → 3. approved fixed-price
override → 4. automatic product/category promo → 5. quantity-tier → 6. bundle → 7. customer/B2B →
8. coupon (per stacking rules) → 9. shipping promo → 10. tax → 11. rounding → 12. validate floor +
margin → 13. immutable cart/order snapshot.

## Stacking (codex §12)
stackable / non-stackable / exclusive / priority / best-price-wins / first-match-wins / max-total-
discount / max-stacked-count / coupon+brand+seller exclusions. On conflict: eligibility → exclusivity
→ priority → best-price → max discount → floor/margin. Always record why an offer was NOT applied
(the pricing engine's trace pattern extends here).

## Hard rules
- Never display or apply a promotion the visitor cannot redeem in their selected marketplace.
- Never push final price below the configured floor without an authorized loss-leader approval.
- AI must obtain every commercial figure from services, never invent discounts/prices/dates.
