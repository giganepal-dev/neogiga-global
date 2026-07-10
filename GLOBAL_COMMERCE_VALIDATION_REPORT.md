# GLOBAL_COMMERCE_VALIDATION_REPORT (2026-07-10)

## Scope validated
Stage 1 (marketplace model, path-prefix routing, context resolver, country selector, 25-country
seed) + Stage 2 foundations (exchange_rates, regional_price_history, price_calculation_logs
schema). All validation ran against the local `neogiga_test` PostgreSQL database.

## Results
| Check | Result |
|---|---|
| `php -l` on every new/edited PHP file | clean |
| `migrate --force` (5 new migrations) | **all DONE**, guarded, no errors |
| `migrate:fresh --force` (full replay from zero) | clean — confirms no ordering/dependency issues |
| `migrate --pretend` after full migration | empty output (nothing pending) — expected |
| Seeder first run | 26 marketplaces (3 existing + 23 new), 25 unique `url_prefix` values |
| Seeder re-run (idempotency) | **bug found and fixed**: India/Nepal lookup by `country_id` incorrectly matched GLOBAL in one test scenario, causing a `url_prefix` unique-constraint violation on re-run. Fixed to match by `code` instead (the one column guaranteed unique per marketplace). Verified clean on 3 consecutive runs after the fix. |
| `route:list` | 772 routes total; new `{prefix}` route (`marketplace.landing`) registers correctly, `whereIn`-constrained to the 25 codes |
| `php artisan test` (new file only) | **14/14 passed, 58 assertions, 0 failures** |
| `php artisan test` (full suite) | **0 failures, 132 assertions** across all Feature tests (74 pre-existing + 58 new) |

## New test coverage (`GlobalCommerceMarketplaceTest`, 14 tests)
Seeder correctness (25 unique prefixes) · idempotency · India/Nepal backfilled to active+checkout-on
· new countries seeded preview+checkout-off · all 25 `/{prefix}` routes return 200 · preview
marketplace shows "Coming soon" with no storefront markup · active marketplace does not show
"Coming soon" · unknown prefix returns 404 (not a redirect) · unsupported-country / no-prefix
requests leave `/` and `/categories` unaffected · no collision with `/products` or `/rfq` ·
`MarketplacePathResolver` only resolves ACTIVE marketplaces (preview never becomes the governing
context) · **resolution order**: URL prefix beats cookie preference · cookie preference works when
no prefix is present · `allEditions()` includes preview marketplaces for the selector.

## What was NOT deployed
**Nothing from this cycle has been pushed to production.** Reasons:
1. A permission classifier blocked a live production database read mid-session, interpreting the
   user's "hold this operation" (given for the unrelated JLCPCB import) as potentially covering
   this project too. Rather than guess, all further work this cycle was scoped to local-file audit
   + local `neogiga_test` implementation and validation only.
2. The explicit instruction for this execution was to "stop after validation and report before
   enabling production redirection or checkout" — satisfied by design, since nothing in this cycle
   enables redirection or checkout anywhere (`redirect_enabled` and `checkout_enabled` default
   `false` on every new row).

Deploying is a small, low-risk follow-up once confirmed safe: 5 additive/guarded migrations, 3 new
service classes, 1 new controller+view, 2 extended files (`Marketplace` model, `GlobalMarketplace
ContextService`), 1 route addition, and the idempotent seeder — the same union-merge + wallet-canary
deploy procedure used throughout this project's history applies unchanged.
