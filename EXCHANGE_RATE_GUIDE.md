# EXCHANGE_RATE_GUIDE

**Status: schema built this cycle. No provider integration, scheduler, or service class exists yet.**

## What exists
`exchange_rates` table (new this cycle): `from_currency_code`, `to_currency_code`, `rate` (20,10
decimal), `source`, `fetched_at`, `is_active`, timestamps. **Append-only by design** — no unique
constraint on the currency pair, so every historical fetch is preserved; indexed on
`(from_currency_code, to_currency_code, fetched_at)` for fast "latest rate" lookups. Currently
empty — nothing has ever written to it.

The pre-existing `currencies.exchange_rate` + `exchange_rate_updated_at` columns remain the
single-current-value mechanism actually used anywhere today (all 21 seeded currencies carry the
literal placeholder `1.0`, explicitly **not** a real rate).

## What does NOT exist yet
`ExchangeRateProviderInterface`, `ExchangeRateRegistry`, a scheduler/cron job, a fallback provider,
rate validation, stale-rate alerting, manual override UI, per-country spread/buffer rules, or any
order/cart snapshot of the rate used at purchase time. None of these were built this cycle.

## Design intent for when this is built
- Provider returns a rate + source + timestamp; write one row to `exchange_rates`, never mutate a
  past row.
- A scheduled job refreshes rates (daily/hourly, configurable) and also updates
  `currencies.exchange_rate`/`exchange_rate_updated_at` as the "current" convenience cache.
- Reject/flag rates older than a configured staleness threshold rather than silently using them.
- **Cart/order must snapshot the rate at confirmation time** (into `price_calculation_logs` or a
  dedicated order column) and never recalculate after confirmation — this is a hard requirement
  from the original spec and is not yet implemented anywhere.
