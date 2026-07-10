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

## Production deployment (2026-07-10, user-authorized: "go on automode, deploy")

Pre-deploy reconciliation: prod's autonomous build had decomposed marketplace context resolution
into `CountryResolver`/`DomainMarketplaceResolver`/`MarketplacePreferenceService`/
`MarketplaceContextResolver`. Stage 1 was re-layered onto that architecture (commit `e7caef2`),
re-validated locally (full suite: 132 assertions, 0 failures; plus a clean single-process tinker
check of prefix/crawler/edge-header interactions — an earlier discrepancy proved to be
cross-process cache staleness, not a code bug). Fresh drift-diff of every shared file immediately
before push showed only the intended local additions. Two brand-new prod-side files
(`RegionalCommercePolicyService`, `RegionalVisibilityService`) were pulled to local verbatim
(commit `31a732d`) — additive, no overlap.

Deployed: 4 migrations, 5 service files, `MarketplaceLandingController` + landing view,
`routes/web.php`, `Marketplace` model, `GlobalCommerceMarketplaceSeeder`. Migrations ran clean via
`migrate --path`; seeder ran once; caches rebuilt with `config:cache` last.

| Post-deploy check | Result |
|---|---|
| Prod marketplaces | 26 total, 25 with `url_prefix`, still only 3 active, 23 preview |
| `checkout_enabled` | 3 (the pre-existing active ones only) / `redirect_enabled` 0 everywhere |
| Wallet canary | 401 ✅ (note: `neogiga.com/api/*` now 302s to `backend.neogiga.com` at the Apache vhost level — canary must follow redirects) |
| `/`, `/products`, `/categories` regression | 200 ✅ |
| `/np` (active) | 200, "Live" badge ✅ |
| `/bd`, `/de` (preview) | 200, "Coming soon" + `noindex,follow` ✅ |
| `/xx` (unknown prefix) | 404 ✅ |
| Branded domains | `neogiga.in` and `giganepal.com` are **WordPress vhosts**, not the Laravel app — their `/products` 404s are pre-existing vhost facts, not regressions. The Laravel branded-domain resolution path is currently only exercised on `neogiga.com`/`backend.neogiga.com`. |

Per the execution instruction, redirection and checkout remain disabled on every new marketplace;
stopping here before enabling either.
