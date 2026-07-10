# GLOBAL_COMMERCE_IMPLEMENTATION_PLAN (2026-07-10)

Scope for this execution, per the prompt's own bounding: **complete the audit (done — see the 5
companion docs), implement Stage 1 fully, implement foundational database/services for Stage 2,
seed all 25 countries inactive/preview, stop before enabling redirects/checkout/taxes/payments.**

## Design principle
Reuse everything the audit found (`marketplaces`, `tax_rules`, `marketplace_product_prices`,
`payment_providers`, `RegionalCommerceService`, `GlobalMarketplaceContextService`). Do not create
parallel tables/services for concepts that already exist under a different name. Only add what's
genuinely missing.

## Stage 1 — marketplace model, routing, fallback, country selector

### 1a. Extend `marketplaces` (guarded additive migration, new columns only)
`url_prefix` (nullable, unique — 'in', 'np', … null for domain-only entries if ever needed),
`regional_brand_name`, `default_language`, `checkout_enabled` (bool, default false),
`launch_status` (string: preview|active|retired, default 'preview'), `global_fallback` (bool,
default false — true only for GLOBAL), `redirect_enabled` (bool, default false),
`local_seller_support` / `local_warehouse_support` / `local_payment_support` (bool, default false).
Rejected as separate tables (reuse instead): `marketplace_countries`/`marketplace_currencies` (FKs
already exist), `marketplace_languages`/`marketplace_locales` (covered by `locale` +
`supported_languages` json + new `default_language`), `marketplace_seo_settings`/
`marketplace_support_settings` (covered by the existing `settings` json column, documented
sub-key convention below).

### 1b. New tables (genuinely distinct, many-rows-per-marketplace concepts)
- `marketplace_feature_flags` (marketplace_id, flag_key, is_enabled, notes, timestamps)
- `marketplace_redirect_rules` (marketplace_id, from_pattern, to_pattern, redirect_type
  [temporary|permanent], is_active, timestamps) — **rows may exist but redirect execution stays
  disabled** (`marketplaces.redirect_enabled=false` everywhere) until explicitly reviewed and
  turned on.

### 1c. Context resolution engine
- `MarketplacePathResolver` — new service: given a request path, extracts the leading segment,
  matches against seeded `url_prefix` values, returns the Marketplace or null. Read-only, cached.
- `MarketplaceContextResolver` — new orchestrating service implementing the exact resolution order
  from the spec: (1) URL prefix → (2) cookie preference → (3) authenticated user preference →
  (4) domain rules (existing `MarketplaceResolverService::getByDomain()`) → (5) GeoIP (**stubbed,
  always returns null — no provider integrated this cycle, explicitly deferred**) → (6) global
  fallback (existing `GlobalMarketplaceContextService::fallbackMarketplace()`).
- `ResolveMarketplaceContext` middleware — runs on the `web` group, calls the resolver once per
  request, shares the resolved context to all views. **Additive only** — does not move, wrap, or
  duplicate any of the ~30 existing public routes.
- `MarketplaceUrlGenerator` — small helper building a prefixed URL for a given marketplace + path,
  used by the country selector.

### 1d. Country selector / landing
One new route: `GET /{prefix}` constrained to the 25 known codes (no collision — none of the
existing top-level route segments are 2-letter codes). Renders a marketplace landing card (name,
regional brand, currency, launch status). For `launch_status=preview` marketplaces this is a
**"Coming soon" page**, not a functional storefront — no product listing, no checkout, honest and
non-misleading. For NEPAL/INDIA (which already have live branded domains) it surfaces a link to
the existing domain rather than duplicating the storefront. **No auto-redirect** — GeoIP isn't
built, so nothing forces a visitor anywhere; the existing cookie-preference flow is untouched.
Full content-bearing routes under each prefix (`/in/products`, etc.) are explicitly **Stage 4**
work, deferred until real localized content exists (see COUNTRY_LOCALIZATION_GAP_REPORT.md).

### 1e. Seed data
25 marketplaces total: keep the 3 existing rows as-is (GLOBAL active/global_fallback=true,
NEPAL/INDIA active with their existing domains), add 22 new rows with `launch_status='preview'`,
`is_active=false`, `checkout_enabled=false`, `redirect_enabled=false`, and a `url_prefix` per the
prompt's own path table (in, np, bd, lk, pk, bt, mv, ae, sa, qa, om, kw, us, ca, uk, de, fr, it,
es, nl, au, nz, br, za, ke). Country/currency rows created or reused as needed — **no invented tax
rates, no payment credentials, no legal claims**, exactly per the prompt's instruction.

## Stage 2 — foundational pricing/tax schema only (no live wiring)
- `exchange_rates` — append-only history table (from_currency_code, to_currency_code, rate,
  source, fetched_at, is_active). No scheduler, no provider integration, no cron — schema only,
  ready for a future `ExchangeRateProviderInterface` implementation.
- `regional_price_history` — append-only audit trail for `marketplace_product_prices` changes.
- `price_calculation_logs` — append-only breakdown table (base_cost_usd, exchange_rate, duty,
  tax, freight, margin, final_price, currency_code, calculation_version) for a **future** pricing
  formula service. **No formula service is built this cycle** — the existing
  `RegionalCommerceService` (cart tax/shipping estimates) is left completely untouched.
- HS-codes/import-duty/freight-rate-card tables: **explicitly deferred to Stage 3**, per the
  prompt's own release order — not created this cycle.

## What is explicitly NOT done this cycle
GeoIP integration, live redirects, live tax rates for new countries, live payment gateway
credentials/routing per country, freight/carrier integration, localized page content, seller/
warehouse country-approval workflows, full prefixed content routing. All logged in
NEXT_GLOBAL_COMMERCE_BACKLOG.md.

## Validation plan
`migrate --pretend` then `--force` on `neogiga_test`, `route:list`, `php artisan test` (new
feature tests for: unsupported country → global path unaffected, `/{prefix}` resolves for all 25
codes, preview marketplaces render "coming soon" not a storefront, no route collisions, resolver
order unit tests). Deploy to prod deferred until the user confirms the "hold" scope is lifted for
this project (see GLOBAL_COMMERCE_AUDIT.md's note on the prod-read classifier block).
