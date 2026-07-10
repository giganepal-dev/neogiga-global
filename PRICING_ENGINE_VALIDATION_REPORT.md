# PRICING_ENGINE_VALIDATION_REPORT (2026-07-10)

## Scope validated
The configurable pricing-rule engine (schema + `PricingRuleResolver` + `PriceSimulator` + floor/
margin protection). Promotion engine, admin UI, storefront, and cart/checkout revalidation are
**not** part of this cycle (deferred — see NEXT_PRICING_PHASE_BACKLOG.md). Validated on the local
`neogiga_test` PostgreSQL database. **Nothing deployed to production; no live price changed.**

## Results
| Check | Result |
|---|---|
| `php -l` on every new PHP file (migration, 6 models, 3 services, test) | clean |
| `migrate --path` (pricing_rule_engine_tables) | DONE, guarded, no errors |
| New test file `PricingRuleEngineTest` | **17 passed, 25 assertions, 0 failures** |
| Full suite | **exit 0, 199 assertions, 0 failures** (no regressions) |

## Test coverage (`PricingRuleEngineTest`, 17 tests)
percentage_markup · fixed_markup · fixed_selling_price override · **margin_target ≠ markup**
(30% margin ⇒ 1.4286, not 1.30) · product-scope beats category beats global (with skip trace) ·
priority tie-break within a scope · stackable primaries compound · stop_processing halts ·
minimum_price modifier raises floor · price_floor_rule blocks below absolute minimum ·
margin_floor_rule blocks thin margin · foreign-currency fixed amount converted via FX ·
unconvertible fixed amount skipped (never guessed) · marketplace isolation · quantity-tier gating ·
draft/unapproved rules ignored · simulator never writes.

## What is enforced (engine invariants)
- Only `active` + `approved` + in-window + scope-matching rules price anything.
- Deterministic precedence: specificity → priority → version → id.
- Markup vs margin kept mathematically distinct.
- Fixed monetary rules converted through a fresh FX rate or skipped — never applied unconverted.
- Below-floor price / below-margin price ⇒ `blocked` with reason; the resolver never silently
  clamps or ships a sub-floor price.
- Simulation is read-only (row counts asserted unchanged).

## Production enablement steps (when approved)
1. Review + deploy the migration (`2026_07_10_140000_create_pricing_rule_engine_tables.php`) and the
   `app/Models/Pricing/*`, `app/Services/Pricing/{PricingContext,PricingRuleResolver,PriceSimulator}.php`
   files via the standard rsync + `config:cache`-last procedure; wallet-canary check.
2. Author pricing rules through the (deferred) admin UI or a seeder, in `draft`, then `approve`.
3. Simulate with `PriceSimulator` before activating.
4. Wire the resolver into `CentralPricingService`/storefront only after the promotion engine, cart
   revalidation, and performance cache exist (deferred) — until then the engine is inert and
   nothing calls it in a customer path.
