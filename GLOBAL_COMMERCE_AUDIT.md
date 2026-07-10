# GLOBAL_COMMERCE_AUDIT (2026-07-10)

Audit method: local git repo inspection (`~/Downloads/neogiga-main 2/giga-nepal-backend`, in sync
with prod as of the last file-parity check). **Live production database was NOT queried for this
audit** — a permission classifier flagged production reads as ambiguous with the user's "hold this
operation" instruction on the prior JLCPCB task, and rather than guess at scope I paused prod
access and audited from code/schema only. Row counts below marked "prod, last known" are from the
JLCPCB investigation earlier the same day and may have shifted since.

## Marketplaces
| Item | State | Evidence |
|---|---|---|
| `marketplaces` table | ✅ Complete | `database/migrations/marketplace/2026_07_06_014717_create_marketplaces_table.php`. Columns: id, name, code, description, country_id, currency_id, timezone, locale, is_active, is_default, allow_vendor_registration, require_vendor_approval, tax_rate, supported_languages, settings (json), timestamps |
| Marketplaces seeded | ⚠️ Partial | 3 of 25: GLOBAL, NEPAL, INDIA (prod, last known) |
| `marketplace_domains` | ✅ Complete | one row per marketplace: neogiga.com, giganepal.com, neogiga.in, all `is_primary` |
| **Routing model** | ⚠️ Architectural gap | **Current routing is domain-based only** (`MarketplaceResolverService::getByDomain()`). There is **no URL path-prefix routing** (`/in`, `/np`, etc.) anywhere in `routes/web.php`. The requested 25-country model needs path-prefix marketplaces layered on top of (not replacing) the existing 3 branded domains. |
| `countries` | ✅ Complete | iso_code_2/3, phone_code, capital, currency_code, region, subregion, translations (json). 10 rows seeded (prod, last known) — short of the 25 target |
| `currencies` | ✅ Complete | code, symbol, native_symbol, decimal_places, exchange_rate, exchange_rate_updated_at, translations. 10 rows (prod, last known). **`exchange_rate` lives directly on the currency row — no history/audit table.** |

## Locale routing / marketplace context
| Item | State | Evidence |
|---|---|---|
| `GlobalMarketplaceContextService` | ✅ Exists, partial | `app/Services/Marketplace/GlobalMarketplaceContextService.php` (162 lines). Builds `editions()` (public marketplace list), a cookie-based preference reader, a "seen" cookie, `recommendedMarketplace()`, and **hreflang link generation**. |
| GeoIP country detection | ⚠️ Partial — **correction** | No MaxMind/dedicated GeoIP service, but `recommendedMarketplace()` **does** read Cloudflare's `CF-IPCountry` edge header (plus an `Accept-Language` region fallback) to produce a non-forced *recommendation* — exactly the Phase 4 "recommend, don't redirect" pattern, just previously hardcoded to India/Nepal only. Generalized to all 25 marketplaces (data-driven, no hardcoded country list) as part of this cycle's Stage 1 work. |
| Marketplace preference persistence | ✅ Exists | `MarketplacePreferenceController::store()` + `GlobalMarketplaceContextService` cookie constants — sets/reads a preference cookie, computes a safe return path. |
| Domain resolver | ✅ Exists | `MarketplaceResolverService::getByDomain()`, cached 1h per domain. |
| Path-prefix resolver | ❌ Missing | No `LocaleResolver`/`MarketplaceUrlGenerator`/URL-prefix parsing exists. |
| Redirect rules | ⚠️ Partial | `marketplace_domains.redirect_rules` (json) column exists but is unused by any controller/middleware found. |
| Fallback marketplace | ✅ Exists | `GlobalMarketplaceContextService::fallbackMarketplace()` — falls back to the GLOBAL marketplace. |

## SEO / hreflang / sitemaps
| Item | State | Evidence |
|---|---|---|
| `SeoLandingController` | ✅ Exists | `manufacturer()`, `mpn()`, `technology()`, `application()`, `country()`, generic `keywordLanding()` — programmatic SEO landing pages, with a `schema()` method producing structured data. |
| hreflang generation | ✅ Exists (domain-scoped) | `GlobalMarketplaceContextService::hreflangLinks()` — but built around the 3 existing domains, not a 25-marketplace path model. |
| `seo_pages` table | ✅ Exists | 1 row (prod, last known) — essentially unused so far. |
| Sitemap | ✅ Exists (basic) | `SitemapController` at `/sitemap.xml`. No sharding, no country/marketplace segmentation, no image/video sitemap found. |
| `llms.txt` | ✅ Exists | present per earlier project history (Phase 0-R). |
| Structured data types | ⚠️ Partial | Product/BreadcrumbList JSON-LD confirmed on `/products/{slug}` (built this cycle); Organization/WebSite/FAQ from Phase 0-R. No CollectionPage/ItemList/Course/HowTo confirmed. |

## Search
| Item | State |
|---|---|
| Dedicated search index (OpenSearch/Meilisearch) | ❌ Missing — reads are direct Eloquent/DB queries with `ilike` filters (seen in `ProductPageController`, admin controllers). No index-based ranking, no marketplace-prioritized result ordering. |

## AI / LMS
| Item | State | Evidence |
|---|---|---|
| `commerce_ai_sessions` and related CommerceAI module | ✅ Exists | pulled into git 2026-07-08 (`App\Services\CommerceAi\*`, `App\Models\CommerceAi\*`) — session/recommendation/BOM services live. |
| `product_lms_links` | ✅ Exists | `database/migrations/marketplace/2026_07_06_023545_create_product_lms_links_table.php` — links products to LMS courses. |
| `lms_courses` | ✅ Exists | full LMS platform live (Phase 0-R + since). |

## Admin controls
| Item | State | Evidence |
|---|---|---|
| Marketplace-scoped RBAC | ✅ Exists, more built than expected | `database/migrations/admin_console/2026_07_10_033000_create_admin_access_control_tables.php` creates `permissions`, `role_permissions`, **`user_country_access`**, **`user_seller_access`**, `admin_invitations` — this already satisfies most of Phase 18's "marketplace-scoped RBAC / country-admin restrictions / seller isolation" requirement at the schema level. Wiring into route middleware not yet confirmed. |
| `roles` table | ✅ Exists | `database/migrations/2026_07_04_055126_create_roles_table.php`. |
| Admin marketplace page | ✅ Exists | `/admin/marketplaces` (`AdminDash::marketplaces`) — list/view only, no create/edit UI confirmed for new marketplaces. |

## Payments / Tax / Freight
See REGIONAL_PRICING_AUDIT.md for full detail. Headline: `payment_providers` (generic abstraction,
8 providers seeded disabled) and `tax_rules` (marketplace/country/region-scoped) both exist and are
**more complete than the prompt's "Missing" assumption** — but there is no `exchange_rates` history
table, no `hs_codes`/import-duty tables, no `carriers`/`freight_rate_cards`, no per-country payment
gateway adapters beyond the existing sandbox providers.

## Summary verdict
NeoGiga already has a working **3-marketplace, domain-routed** regional commerce foundation with
real tax rules, regional pricing rows, RBAC scoping tables, an AI layer, and an LMS layer. The
genuine gap for the 25-country vision is almost entirely in **routing (path-prefix), geo-detection,
country/currency seed breadth, and the pricing/tax audit trail (exchange-rate history, calculation
logs)** — not in rebuilding the commerce core from scratch. See GLOBAL_COMMERCE_IMPLEMENTATION_PLAN.md.
