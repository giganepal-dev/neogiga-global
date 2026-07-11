# MARKETPLACE_DOMAIN_SEO_AUDIT (2026-07-10)

Audit of the existing marketplace-region module before the domain/SEO configuration upgrade.
Ground rule honoured: **preserve neogiga.com / neogiga.in / giganepal.com and all 26 marketplace
records; additive only.**

## What already exists (reuse — do NOT rebuild)
| Concern | Existing asset |
|---|---|
| Marketplace records | 26 rows on prod (GLOBAL/NEPAL/INDIA active, 23 preview) — see [[neogiga-global-commerce-status]] |
| Marketplace columns | `code`, `country_id`, `currency_id`, `timezone`, `locale`, `is_active`, `is_default`, `url_prefix`, `regional_brand_name`, `default_language`, `launch_status`, `global_fallback`, `checkout_enabled`, `redirect_enabled`, `local_*_support`, `settings` (json), `tax_rate`, `supported_languages` |
| Domains | `marketplace_domains` (domain, is_primary, is_active, ssl_certificate_path, ssl_expires_at, redirect_rules) + `MarketplaceDomain` model |
| Host resolution | `DomainMarketplaceResolver` (exact host, cached) + `MarketplaceContextResolver` (prefix → domain → cookie → geo → fallback) + `CountryResolver` (CF-IPCountry, crawler detection) |
| URL generation | `MarketplaceUrlGenerator` (branded domain → /prefix → bare) |
| Path routing | `MarketplacePathResolver` + `/{prefix}` landing route (25 codes) |
| hreflang | `GlobalMarketplaceContextService::hreflangLinks()` + `x-default` |
| Redirect rules | `marketplace_redirect_rules` (inert, master `redirect_enabled` kill-switch off) |

## What is MISSING (this codex fills)
- **Domain generation columns**: `domain_mode`, `domain_prefix`, `generated_domain`,
  `canonical_domain`, `force_https`, `redirect_to_canonical`, `www_redirect_mode`,
  `domain_verified_at`, `ssl_status`, `is_domain_locked`, plus a denormalized `domain` on the row.
- **Full SEO block**: `seo_title/description/keywords/h1/canonical_url/robots/og_*/twitter_*/
  schema_json/header_scripts/footer_scripts`, `sitemap_enabled`, `hreflang_enabled`, `indexable`,
  `seo_is_auto_generated`, `seo_last_generated_at`, `seo_manual_override_fields`.
- **Status/content columns**: `is_visible`, `allow_customer_registration`, `maintenance_mode`,
  `maintenance_message`, `launch_at`, `disabled_at`, `disabled_reason`, `short_description`,
  `marketplace_description`, `homepage_heading/subheading`, `logo`, `favicon`, `banner_image`,
  `country_iso2/iso3`, `currency_code/symbol`, `created_by`, `updated_by`, `deleted_at`.
- **Domain-generation service** (ISO alpha-2 → `{iso2}.neogiga.com`, sanitize, preserve custom).
- **SEO auto-fill service** (country templates, preserve manual overrides, robots by status).
- **Audit log** (`marketplace_audit_logs`), and extra `marketplace_domains` columns (`domain_type`,
  `redirect_url`, `verified_at`, `ssl_status`).

## Reuse mapping (avoid duplicate columns)
- Codex `allow_checkout` → existing **`checkout_enabled`** (not duplicated).
- Codex marketplace status → existing **`launch_status`** (preview/active) + new `disabled_*`.
- Codex path mode `domain_prefix` ↔ existing **`url_prefix`** (kept in sync by the seeder).

## This execution's scope (bounded)
**BUILT (additive, tested, deploy pending review):** the audit + implementation report; the schema
(marketplaces columns, marketplace_domains extension, marketplace_audit_logs); `MarketplaceDomainService`
(generation + sanitization + custom preservation + duplicate/localhost/IP/wildcard rejection);
`MarketplaceSeoService` (country-template auto-fill, manual-override preservation, robots-by-status);
`MarketplaceAuditLog` model + helper; a domain/SEO backfill seeder (preserve custom, keep inactive,
ssl `pending`); and tests.

**DEFERRED (documented in MARKETPLACE_IMPLEMENTATION_REPORT.md, not built):** the tabbed admin UI,
the `/api/admin/marketplaces*` endpoints, frontend SEO rendering + per-marketplace sitemaps, the
host-spoofing allow-list middleware wiring, and permission enforcement. Each is substantial; the
schema + services are the foundation they sit on.

## Hard invariants (from the codex)
- Existing custom domains (neogiga.com/.in/giganepal.com) must never be auto-overwritten.
- A generated domain does NOT mean DNS is configured; `ssl_status` stays `pending` until real
  verification. Never mark a domain verified without real DNS/HTTP checks.
- Do not activate inactive marketplaces automatically.
- Reject localhost/IP/wildcard/malformed hostnames; reject duplicates.
