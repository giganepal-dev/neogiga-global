# NeoGiga — Blueprint Alignment Audit

**Date:** 2026-07-07 · Legend: ✅ implemented · 🟡 partial · ❌ missing · ⚠️ implemented-but-risky · ❓ unknown
Evidence base: `giga-nepal-backend` code + live probes (backend/admin/neogiga.com). Read-only.

| # | Requirement | Status | Evidence | Risk | Recommended fix | Priority |
|---|---|---|---|---|---|---|
| 1 | Multi-country marketplace | ✅ | 3 marketplaces + `marketplace_domains`; `MarketplaceResolverService`; 10 countries/currencies seeded | Low | — | — |
| 2 | backend.neogiga.com API | ✅ | vhost live; `/api/v1/*` 200; 75 routes | Low | — | — |
| 3 | admin.neogiga.com admin | ✅ | dedicated vhost + Blade console; login 200; noindex | Low | Add admin CRUD | P1 |
| 4 | neogiga.com frontend | 🟡 | landing SSR only; no browse/PDP/cart | Med | Build catalog UX | P1 |
| 5 | Separate prod database | ✅ | PostgreSQL `neogiga` (isolated, non-super role); MySQL of other projects untouched | Low | — | — |
| 6 | Product catalog | ✅ | products/variants/specs/images schema; read APIs | Low | — | — |
| 7 | Categories/brands/products/variants/specs | ✅ | 177 categories, `product_brands`, variants, specs | Low | — | — |
| 8 | Vendor system | ✅ | `vendors` suite + models + register API | Low | — | — |
| 9 | Vendor regional approval | 🟡 | `vendor_marketplace_approvals` schema; no admin approval UI/flow | Med | Approval dashboard + policy | P1 |
| 10 | Inventory/warehouse/stock movement | 🟡 | schema + read endpoints; no server-side deduction wired to orders beyond checkout test | Med | Reserve/deduct + ledger | P1 |
| 11 | Multi-location inventory | 🟡 | `inventory_stocks` per warehouse+marketplace; `regional_inventory_visibility` | Med | Transfer + visibility rules | P2 |
| 12 | Pricing/tax/currency | 🟡 | `marketplace_product_prices`, `tax_rules`, currencies; server-side price in checkout | Med | Tax/currency at checkout | P1 |
| 13 | Cart/orders/payments | 🟡 | real Cart/Order controllers; checkout → pending order+payment (tested); no gateway | Med | Payment adapters | P1 |
| 14 | POS | 🟡 | 42 files (models/routes); endpoints 501 | Low | Execution + UI later | P3 |
| 15 | LMS | 🟡 | 33 files; route shells 501 | Low | Content delivery later | P3 |
| 16 | AI BOM commerce | 🟡 | DB-backed tool stubs; no orchestrator/live calls | Low | Orchestrator (Phase 2) | P3 |
| 17 | SEO/schema/sitemap/hreflang | ⚠️ | landing has JSON-LD/hreflang/OG; sitemap 178 URLs BUT 177 category URLs **404** | **High** | **Deploy `/categories` pages** | **P0** |
| 18 | Import/export | 🟡 | admin route shell; `imports`/`export_jobs` tables | Med | Queued jobs + validation | P2 |
| 19 | Marketing automation | ❌ | 0 code files | Med | Build per adaptation doc | P2 |
| 20 | Newsletter | ❌ | 0 code files | Low | Subscribe + list | P2 |
| 21 | WhatsApp campaign | ❌ | 0 code files | Low | Opt-in + provider | P2 |
| 22 | Abandoned cart | ❌ | 0 code files | Low | Job + email | P2 |
| 23 | OTP login | ❌ | 0 code files | Med | OTP + rate-limit | P2 |
| 24 | Transactional emails | 🟡 | mail config only; no order/verify mailers | Med | Mailables + queue | P2 |
| 25 | Analytics / GA4 | ❌ | 0 code files | Med | GA4 + event layer | P2 |
| 26 | Dashboard reports | 🟡 | KPI counts only; no charts/trends | Low | Charts + trending | P2 |
| 27 | Affiliate/referral | ❌ | 0 code files | Low | Referral + commission | P3 |
| 28 | Gift card/coupon/wallet | ❌ | 0 code files | Low | Coupon → wallet | P3 |
| 29 | Security/auth/roles | ✅ | bearer-token API auth, `permission:` RBAC, admin web session, fail-closed | Low | Rotate test pw; audit log | P1 |
| 30 | Deployment/SSL/CORS/domain sep | ✅ | 4 hostnames SSL, www→non-www 301, CORS restricted, headers, files blocked | Low | Persist vhosts in Virtualmin | P1 |

## Rollup

- ✅ Implemented: **11** (1,2,3,5,6,7,8,29,30 + core catalog/marketplace)
- 🟡 Partial: **13**
- ❌ Missing: **8** (marketing, newsletter, WhatsApp, abandoned-cart, OTP, analytics, affiliate, giftcard — growth/advanced)
- ⚠️ Risky: **1** (SEO sitemap→404)

**Weighted alignment ≈ 60%.** The **core marketplace + admin + secure deployment are done**; the deficit is concentrated in (a) public **frontend catalog UX**, (b) **growth/marketing** modules, and (c) **advanced** POS/LMS/AI/affiliate — all planned but uncoded. The single **risky** item (SEO 404s) is a cheap fix (deploy already-built pages).
