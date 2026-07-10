# PANEL_BRANCH_REVIEW — `next-phase-panel-execution-d162b` (2026-07-10)

Full code review of the branch's delta vs current `main` (51 files, +9,147/-124). Most of the
original panel work already reached main via prod file-sync; what remains is **four distinct
feature clusters** with very different risk profiles. Per-cluster verdicts:

## 1. Sanctum layer — ✅ MERGEABLE (fixed since the last attempt)
`composer.json` now actually adds `laravel/sanctum ^4.0` (the earlier branch's fatal flaw),
`config/sanctum.php`, `personal_access_tokens` migration (in the inert `2025_01_00_000000/`
subdir — must be run via `--path`), and `User` gains the `HasApiTokens` trait **without removing
`api_token_hash`** (the earlier auth-breaking defect is gone — custom tokens keep working;
Sanctum becomes additive).
**Deploy notes:** requires `composer install` on prod (new vendor package) and the `--path`
migration; keep `config:cache` last. Recommend merging this cluster.

## 2. Inventory reservations — ✅ MERGEABLE (valuable; two small fixes first)
`cart_reservations` table + `InventoryReservationService` + `ReleaseExpiredReservationsCommand`
(scheduled in `routes/console.php`) + a clean 3-line checkout hook converting reservations on
order placement. This implements the long-standing backlog item "soft-reserve with TTL".
**Fix before merge:** (a) add the house-style `hasTable` guard to the migration (it's in the
auto-loaded `marketplace/` dir); (b) re-diff `CartController`/`OrderController` against prod at
merge time (both files are hot race targets).

## 3. Warehouse network — ⛔ DO NOT MERGE (incompatible parallel redesign)
The branch reinvents `warehouses` with a **UUID primary key** and denormalized `region/country/
city` strings. Prod's LIVE `warehouses` table (1 row, used by the inventory module) is bigint-PK
with `marketplace_id`/`vendor_id`/`country_id`/`region_id` FKs. The branch migrations are
**unguarded `Schema::create` calls dated 2024** — running `migrate` after merge would crash on
the existing table, and even guarded, its models/controllers expect columns that don't exist.
The shipment/network features (incl. the Dubai-warehouse seeder) are interesting but must be
**rebuilt as an extension of the live schema**, not merged. Cherry-pick nothing from this cluster.

## 4. Payment gateways (eSewa/Khalti/Stripe/COD + callback controller) — ⏸ HOLD (owner decision)
Credential handling is done right (config/services.php ← env keys `STRIPE_KEY/SECRET/
WEBHOOK_SECRET`, eSewa/Khalti equivalents; nothing hardcoded; HMAC verification present).
But: (a) the standing project constraint is **"do not connect live gateways yet"** — merging wires
a public `PaymentCallbackController`; (b) it builds a parallel `PaymentGatewayFactory` instead of
plugging into the existing `payment_providers` abstraction (`PaymentGateway` contract +
`PlaceholderGateway`); (c) no gateway credentials exist in prod `.env` anyway. Recommend: hold
until you decide to go live with payments, then adapt these gateway classes to implement the
existing `App\Services\Payments\Contracts\PaymentGateway` contract.

## Minor notes
- `.env.testing` is committed with a test-only `APP_KEY` — acceptable for a test env, but worth
  regenerating locally out of caution. No production secrets found anywhere in the branch.
- 8 docs (`NEOGIGA_PANEL_*`, `NEOGIGA_SANCTUM_*`, warehouse/payment docs) — merge freely.

## Recommended path
1. Merge clusters **1 + 2** (+docs) onto main with the two reservation fixes; deploy with
   `composer install`, `--path` migrations, caches config-last, wallet canary.
2. **Delete or clearly quarantine cluster 3** on the branch (or close the branch after
   cherry-picking 1+2) so the auto-PR process can't merge the schema collision into main.
3. Park cluster 4 until the payments go-live decision; then integrate via the existing contract.
