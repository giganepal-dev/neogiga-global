# 2 — Reference Feature Matrix (2026-07-12)

Classification key (mission taxonomy): **CODE** safe to reuse as source code · **PATTERN** reuse as
architecture pattern · **UI** reuse as UI inspiration · **APPROVED_DEPENDENCY** upstream package can
be installed after license/security review · **NG-BETTER** NeoGiga already stronger · **REFACTOR**
needs refactoring · **UNSAFE** · **OUTDATED** · **DUP** duplicate · **N/R** not relevant.

> Because the reference is a proprietary-lineage Laravel 8/MySQL/Bootstrap 4 product, almost nothing
> qualifies as CODE. The value is workflow/schema shape. NeoGiga column = verified state from the
> evidence audits (`NEOGIGA_BOM_PCB_RFQ_EVIDENCE_AUDIT.md`, gap docs #5–#9).

| Reference feature | Ref quality | NeoGiga today | Classification |
|---|---|---|---|
| Admin dashboard (business overview, order/earning stats) | Solid widgets, MySQL queries | Blade admin dashboard w/ real DB stats + system-health center | PATTERN (KPI set, filters) + NG-BETTER (health checks) |
| Custom roles (module checkboxes) | Flat, no scopes | `roles.permissions` JSON + `permission:` middleware + `admin.user` gate (branch) | NG-BETTER core; PATTERN for role-CRUD UI |
| Employee management | Profiles, assignment | Users+roles only; no departments/designations | PATTERN |
| Category (3 fixed levels) | Sub/SubSub controllers | `product_categories` with parent_id (unlimited depth) + 177 seeded | NG-BETTER schema; UI (tree drag-drop) |
| Brand management | CRUD + logo + featured | `product_brands` CRUD (admin) | DUP core; PATTERN (aliases, authorization status, microsite) |
| Attributes/variations | attribute + choice options | `product_variants` + attributes JSON; no typed attribute engine | PATTERN (typed attributes, variation generator) |
| Bulk import/export (Excel) | phpspreadsheet-based | JLCPCB import pipeline + BOM CSV parser; **no XLSX** (no lib) | PATTERN + APPROVED_DEPENDENCY (`phpoffice/phpspreadsheet`, license/security review before require) |
| Product reviews + moderation | Approve/reject, replies | Reviews w/ moderation queue LIVE (RfqSupportReviewsTest) | NG-BETTER / DUP; PATTERN for seller reply + helpful votes |
| Order lifecycle (14 statuses, seller split, delivery man) | Mature | Orders + status/tracking admin routes + checkout LIVE; no seller-split, no delivery-agent flow | PATTERN (status machine, splits, COD reconciliation) |
| Refund workflow (request→approve→gateway/wallet) | Mature | Refund fields on orders; no request workflow | PATTERN |
| Invoices (PDF: dompdf/mpdf) | Mature | Admin order invoice view exists; no PDF engine | PATTERN + APPROVED_DEPENDENCY (`barryvdh/laravel-dompdf`, license/security review before require) |
| Delivery man management (assign/cash-collect/withdraw) | Full app flow | Absent | PATTERN (later release) |
| Flash/Featured/Day deals | Mature promo engine | PricingRuleResolver + promo backlog (INERT, stronger model) | NG-BETTER engine; PATTERN for deal UX + countdown |
| Coupons | Full (limits, segments, stacking) | Coupon gap noted in audits (real gap) | PATTERN |
| Banner management | CRUD + placements | Absent (marketing module partial) | PATTERN + UI |
| Currency management | Manual rates | ExchangeRateService + provider interface + snapshots | NG-BETTER |
| Social login (Socialite) | Google/FB/Apple | Absent | PATTERN + APPROVED_DEPENDENCY (`laravel/socialite`, license/security review before require) |
| Chat (buyer↔seller, support) | Working, polling | Support tickets + messages LIVE; no buyer↔seller chat | PATTERN (conversation model) |
| Business settings / env settings UI | Broad | config-driven + some admin settings | PATTERN (settings registry UI) |
| Announcements/notifications | Basic | Marketing jobs + notifications partial | PATTERN |
| Seller dashboard/products/orders/shop | Full portal | Seller application API + vendor model; **no seller portal UI** | PATTERN (portal IA) |
| Seller POS + barcode (milon/barcode) | Full POS | Absent | PATTERN + DEPENDENCY_REVIEW_REQUIRED (`milon/barcode` is LGPLv3; do not mark as permissive without legal review) |
| Seller withdraw/settlement | Wallet-based | Wallet module exists (api/v1/wallet); no settlements | PATTERN |
| Customer auth (email/phone/OTP) | Full | API-token auth only; no customer web auth | PATTERN |
| Wishlist / compare / recently viewed | Standard | Absent (deliberately unrendered in header) | PATTERN |
| Reward points/loyalty | Full ledger | Absent (affiliate ledger exists as analogue) | PATTERN (reuse NeoGiga affiliate-ledger shape) |
| Multi-language | Language admin + files | Locale-prefix routing + GlobalSeoI18n; no translation admin | PATTERN |
| Payment gateways (10+ SDKs) | Module w/ many SDKs | Payments-abstraction gap (audit-confirmed) | PATTERN (adapter shape); SDK licenses/advisories checked individually; add only per-marketplace need |
| ShipRocket / shipping adapters | Working adapter | Freight/ETA engine + no live carrier creds | PATTERN |
| File manager | Basic | Media module partial | UI |
| Sitemap/SEO | spatie sitemap | Marketplace SEO renderer + sitemap.xml LIVE | NG-BETTER |
| Install/Update wizard, Addon system, SoftwareUpdate | CodeCanyon machinery | — | **UNSAFE + N/R** (license machinery; never port) |
| `test.php` routes, TestForDataInsert | Debug leftovers | — | UNSAFE / N/R |
| CKEditor 4.22 bundled | EOL editor | — | OUTDATED / UNSAFE |
| Bootstrap4/jQuery/Vue2 frontend | EOL | NeoGiga design system + icon components | OUTDATED; NG-BETTER |
| Passport+Sanctum mix | Inconsistent | Single custom token scheme + RBAC | NG-BETTER (consistency) |

**Net:** ~0 lines of reference PHP should be copied. Reusable only as **reviewed upstream
dependencies** where a real NeoGiga gap exists (for example phpspreadsheet, dompdf, socialite; and
`milon/barcode` only after LGPLv3/legal review). Reusable as **patterns**: the
seller portal IA, POS workflow, order-status machine, refund workflow, coupon/deal schemas, loyalty
ledger, chat conversations, settings registry. Everything already stronger in NeoGiga (pricing,
currency, SEO, RBAC core, category depth, reviews, i18n routing) must not be replaced.
