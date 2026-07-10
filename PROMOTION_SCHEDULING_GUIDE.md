# PROMOTION_SCHEDULING_GUIDE (2026-07-10)

**Status: partially enabled at the engine layer; full promotion scheduling DEFERRED.**

## Already true (pricing-rule engine)
`pricing_rules` carry `starts_at`, `ends_at` (stored UTC) and `timezone`. The resolver only applies a
rule when `now ∈ [starts_at, ends_at]`, and `PriceSimulator` accepts an `at` time so a future-dated
rule can be previewed before it activates. So scheduled *pricing rules* work today.

## To build for promotions (codex §9)
- Per-offer start/end date-time, marketplace timezone, recurring schedule, weekday selection, hourly
  flash-sale window, early-access period, countdown display, grace period, scheduled activation,
  automatic expiry — via `promotion_schedules`.
- **Store internally in UTC; render and evaluate in the marketplace timezone.**
- Expired offers must not appear in: product pages, search, cart, checkout, schema markup, email
  campaigns, or AI recommendations. This requires the scheduled-expiry job + cache invalidation in
  the performance layer (codex §25) — deferred.

## Scheduled jobs to add (deferred)
`scheduled activation`, `scheduled expiration`, and cache/search-index invalidation on both. The
pricing side already has `pricing:refresh-exchange-rates` as the pattern for a scheduled command;
promotion activation/expiry commands follow the same shape.
