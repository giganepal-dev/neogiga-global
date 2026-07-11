# Changelog

## 2026-07-10 - JLCPCB Canonical Import Safety Gate

- Added the JLCPCB/LCSC ETL as a source-controlled Laravel tool with tests, mappings, validation reports, and production run documentation.
- Added a safe PostgreSQL connection resolver that prefers `DATABASE_URL`, falls back to Laravel `.env` DB settings, rejects non-PostgreSQL production drivers, and redacts credentials.
- Added an explicit NeoGiga canonical adapter targeting existing brands, categories, products, specs, documents, and additive source-scoped offer/provenance tables.
- Added additive catalog provenance migration for sources, import batches, source product links, import errors, and distributor offers.
- Added CLI guardrails so NeoGiga writes require `--target neogiga --publish --pilot` and are capped at 1,000 rows for this execution.
- Added canonical schema, field map, write plan, blocker resolution, pilot, idempotency, rollback dry-run, and next-scale gate reports.
- Deployed the additive ETL/provenance changes to production and completed the controlled 1,000-row canonical pilot plus idempotency rerun; imported products remain `draft`/`hidden`/`pending_review`.
- Synced the Global Commerce pricing service/model/config layer that was present locally but missing from the live release, matching the already-applied pricing foundation tables.
- Added a local/live/GitHub audit and deploy report with done, pending, and next phase status.
- Added Phase 3A JLCPCB admin import review workflow: protected `/admin/imports/jlcpcb` queue, filters, source snapshot view, approve, approve-and-publish, reject, bulk approve, source/offer/document status updates, and audit logging.
- Added Phase 3B internal search/facet rebuild foundation for approved JLCPCB imports: additive index tables, queued rebuild job, rebuild service, admin queue action, and rebuild status table.
- Added Phase 3C approved-import search integration: public/API product search now consults approved `product_search_documents`, exposes package/quality/stock facets, and keeps pending JLCPCB imports hidden until reviewed.
- Added Phase 3D review-to-index workflow: JLCPCB single/bulk approval can queue a search/facet rebuild, and bulk selection now covers all non-final pending source statuses.
- Switched JLCPCB canonical product SKU generation from `JLCPCB-*` to NeoGiga `NG-*` and added a dry-run-first `jlcpcb:repair-skus` command for existing linked imports.
- Hardened public/API product visibility so approved-but-hidden imported products remain excluded from listing, detail, category, SEO landing, cart, and review submission flows.
- Added a guarded JLCPCB approved-import publication workflow and visibility-aware public facet counts; approved hidden imports can now be published explicitly from admin without automatic exposure.
- Added a guarded bulk-publish action for approved hidden JLCPCB imports so admins can deliberately promote reviewed rows in small batches and queue one search/facet rebuild.
- Added the JLCPCB sitemap/SEO publication gate: sitemap product URLs now use the public visibility service only, and import review/publish/reject actions clear the sitemap cache.
- Added a read-only JLCPCB taxonomy review gate in admin to surface imported brand/category counts and flag generic labels before larger-scale catalog imports.
- Added an explicit guarded JLCPCB `--scale-import` ETL flag for hidden/pending NeoGiga imports up to 20,000 rows, plus generated localized noindex SEO metadata for imported products, brands, and categories.
- Ran the guarded 20,000-row JLCPCB production import on live: 18,947 new products, 1,053 existing products refreshed, 19,947 source links, 238 brands, 166 categories, 18,947 offers, localized noindex SEO metadata, and 53 canonical duplicate source rows skipped without public exposure.
- Upgraded JLCPCB localized SEO templates to professional country-specific commercial titles/descriptions for Global, India, and Nepal, including category/product/brand tags and keywords such as local stock, fast dispatch, and RFQ sourcing while keeping imported records noindex until review.
- Added a dry-run-first product activation/image utility that can set draft products to active while preserving approval/visibility gates and add a NeoGiga placeholder image for products missing media.
- Changed catalog search rebuilds to index all imported JLCPCB rows, not only approved imports, while keeping SEO publication controlled by visibility/sitemap gates.
- Added a licensed production catalog import pipeline for official manufacturer/distributor feeds with source/license validation, duplicate gates, marketplace localization overlays, provenance requirements, and dry-run/apply reports.
- Added a configurable JLCPCB NeoGiga scale-import ceiling so controlled follow-up imports can exceed the default 20,000-row guard only when an explicit `--scale-import-max` is supplied.
- Ran the controlled 70,000-row JLCPCB production scale import on live: 49,933 new products, 20,067 existing products refreshed, 69,880 searchable JLCPCB documents, 489,160 facets, active placeholder images for all products, and no copied/hotlinked JLCPCB product images.
- Tightened sitemap generation to include only `visibility_status=public` products so marketplace-searchable imports do not become SEO-published accidentally.
- Added the global SEO/i18n architecture foundation: 28 locale-prefixed public routes, marketplace metadata config, hreflang/SEO service, country localization/payment/warehouse/pricing plans, redirect policy, schema guide, SEO template library, and validation backlog.
- Fixed category detail pages by always providing related LMS lesson data to the frontend category view, preventing `/categories/{slug}` 500 errors when no lessons are linked.
- Canonicalized the global storefront so `neogiga.com/`, catalog, product, and RFQ GET URLs redirect to `/en/...`, and updated landing canonical, hreflang, JSON-LD, and sitemap metadata to point at `/en`.
- Updated public navigation and landing links to target locale-prefixed URLs directly, and added `/en` routes for distributors and seller early access to reduce internal redirect hops.
- Enabled non-forced marketplace recommendations on the `/en` landing page using country/locale signals, with crawler exclusion and cookie-backed switch/stay preferences.

## 2026-07-09 - Complete System Audit

- Added a production system audit report covering health, migrations, route protection, schema coverage, module completion state, queue status, admin/public endpoint checks, and prioritized next steps.
- Identified the NeoGiga queue worker gap for `/home/neogiga/laravel/current`, 470 pending jobs, incomplete product extension shell tables, and missing `/admin/distributors` UI route.
- Synced 54 local NeoGiga planning, audit, adaptation-command, and reference-map documents to the live release without overwriting existing live files.
- No migrations, seeders, imports, data rewrites, or application behavior changes were performed.

## 2026-07-07 - Smartend-Style Admin Console Foundation

- Added additive admin console foundation tables for admin settings, managed media assets, SEO pages, and SEO redirects.
- Completed the existing `product_seo_meta` shell with safe optional SEO columns.
- Added protected admin console APIs for overview metrics, navigation, settings, media uploads, SEO metadata, redirects, permissions, and approval queues.
- Added admin Settings, Media, and SEO pages under `/admin/*` using the existing NeoGiga admin theme.
- Extended the admin sidebar with stable Smartend-inspired console groups without copying reference source code.

## 2026-07-06

- Added marketing admin audit logging with an incremental `marketing_admin_audit_logs` table, logger service, protected audit page, and action logging for marketing admin writes.
- Added validated protected admin marketing form actions for creating segments, newsletter/email/WhatsApp templates and campaigns, refreshing segments, and saving non-secret marketing/analytics settings.
- Added protected admin marketing UI foundation under `/admin/marketing` with CRM, newsletter, email campaign, automation, abandoned cart, WhatsApp, analytics, and settings views backed by the Phase 2 tables.
- Added Phase 2 marketing automation foundation: CRM/segments, newsletter, email automation, WhatsApp placeholder campaigns, OTP login, abandoned cart recovery schema, analytics, dashboard APIs, settings, queue jobs, scheduler hooks, seeders, and documentation without overwriting `.env` or deleting existing data.

- Installed Let's Encrypt SSL for `neogiga.com`, `www.neogiga.com`, `mail.neogiga.com`, and `admin.neogiga.com` on the existing Apache/Virtualmin vhost without changing site content or database data.
- Added and deployed a production Next.js 15 NeoGiga landing page on `neogiga.com` as an additive app release under `/home/neogiga/app`, preserving backups of the prior Virtualmin welcome root and Apache vhost.
- Deployed `/Users/ashokdhamala/Downloads/neogiga.zip` to `neogiga.com` as a Laravel release under `/home/neogiga/laravel/releases/20260706-140308`, switched Apache to the Laravel `public/` web root, preserved rollback backups, and added non-destructive sitemap/mobile overflow fixes without running database migrations or seeders.
- Redesigned the Laravel front page for `neogiga.com` as a professional marketplace/platform page with no direct API links, added `backend.neogiga.com` as the SSL-protected backend host, and routed public `/api` traffic to the backend subdomain.
- Created an isolated `neogiga_app` MySQL database during backend setup and patched migration compatibility issues for MySQL table ordering, foreign keys, and long composite index names; live audit confirmed the deployed Laravel runtime currently uses the existing PostgreSQL `neogiga` connection.
- Removed remaining front-page `/api` link targets found during live audit so public UI cards and seller copy no longer navigate users into API endpoints.

## 2026-06-26

- Added read-only public production audit reports for `giganepal.com`, including technical SEO, robots/sitemap behavior, query-string canonical risk, product data quality, image alt SEO, and Nepal/international SEO opportunity findings.
- Added raw public audit evidence under `giganepal-audit-data/` without modifying production WordPress code, database data, products, orders, customers, media, categories, or SEO settings.

## 2026-06-12

- Added `wp-content/mu-plugins/ecoholiday-conversion-upgrade/` as an additive EcoHolidayAsia conversion layer.
- Added responsive homepage, tour package, travel guide/blog package, booking/account, inquiry, and AI trip-help UI without deleting existing themes, routes, products, posts, plugins, or data.
- Added versioned database tables for inquiries, chat history, and source audit metadata with required source fields and pre-migration schema snapshots.
- Added admin audit page under Tools > EcoHolidayAsia Audit for inquiry/source review with raw source URLs kept out of public page overload.
## 2026-07-06 - Inventory Ledger and POS Foundation

- Completed existing POS shell tables with additive columns for terminals, sessions, sales, sale items, and local payments.
- Added inventory procurement support tables for suppliers, purchase orders, and purchase order items.
- Added inventory services for stock movements, reservations, purchase receiving, and warehouse transfers.
- Added POS service for opening/closing sessions, product search, sale creation, stock-out posting, sale lookup, and local payment recording.
- Replaced inventory reservation and POS 501 placeholders with validated API behavior; POS mutation routes now require API token auth.
- Added protected admin inventory APIs for overview, stocks, movements, low-stock rows, adjustments, transfers, and receiving.
- Added read-only admin Inventory and POS dashboard pages.

## 2026-07-06 - LMS Adaptation Foundation

- Completed the existing LMS shell tables with additive migrations for courses, categories, modules, lessons, projects, components, code samples, product links, enrollments, progress events, quizzes, assignments, and certificates.
- Added NeoGiga-native LMS services for course catalog, enrollment, progress tracking, and certificate issuance.
- Replaced LMS 501 public API placeholders with queryable course/project endpoints and learner enrollment/progress APIs.
- Added protected admin LMS API endpoints for overview, courses, projects, lessons, enrollments, and certificates.
- Added a read-only LMS admin dashboard page at `/admin/lms`.
- Added SEO-ready public LMS pages at `/learn` and `/learn/projects/{slug}`.
- Reference LMS code was used for architecture mapping only; no commercial source files, secrets, SQL dumps, or nulled code were copied.

## 2026-07-06 - WhatsApp Manual Export Queue

- Added safe-mode WhatsApp campaign queue execution with provider `manual_export`; no provider delivery is attempted.
- Added opt-in and suppression filtering for WhatsApp audiences, including segment and country targeting support.
- Added admin API `send-now` and export endpoints for queued WhatsApp recipients.
- Added admin web queue/test actions for WhatsApp campaigns with marketing audit log records.
- Updated `SendWhatsAppCampaignJob` to queue campaigns for manual export instead of placeholder logging.

## 2026-07-06 - Marketing Campaign Safe Queue Execution

- Added safe-mode email and newsletter campaign execution through provider `log` only; no real outbound email is sent.
- Added campaign audience building with opt-in, consent, unsubscribe, suppression, segment, and country filters.
- Added admin API `send-now` endpoints and web admin queue/test actions for email and newsletter campaigns.
- Updated campaign queue jobs to use the execution service instead of placeholder logging.
- Recorded campaign queue/test actions in the marketing admin audit log.
# 2026-07-08 - Distributor Territory Stock Summaries

- Added protected distributor dashboard overview, territory stock, leads summary, and customer summary APIs.
- Added protected distributor territory products and territory vendors APIs.
- Added `DistributorTerritoryStockService` for assigned country/region/city stock filtering.
- Kept responses aggregate/read-only without warehouse contact details or seller financial data.
- No migration or `.env` changes required.

# 2026-07-08 - Admin Product Review and Generic Suggestions

- Implemented admin product listing, detail, pending review, approve, and reject APIs.
- Added admin generic product group list/create APIs.
- Added admin generic suggestion create/update/soft-delete APIs.
- Added product review, generic group, and generic suggestion validation requests.
- Added `ProductApprovalService` with server-side approval state updates and vendor audit logging.
- Preserved existing vendor admin product review routes and admin token protection.

# 2026-07-08 - Seller Product Detail Management APIs

- Added protected seller product detail APIs for documents/datasheets, variants, attributes, specs, and warranty.
- Added seller form requests for product document, variant, spec, attribute, and warranty writes.
- Added `SellerProductDetailService` for catalog-product resolution and vendor audit logging.
- Reused existing seller vendor ownership checks and seller product permission middleware.
- Prevented direct detail edits on approved vendor products.
- Did not add migrations, change `.env`, or alter existing IoT modules.

# 2026-07-08 - Seller and Distributor Auth Foundation

- Added non-versioned customer auth API aliases for `/api/auth/register`, `/api/auth/login`, `/api/auth/me`, and `/api/auth/logout`.
- Added seller registration, login, logout, and me APIs under `/api/seller/*` and `/api/v1/seller/*`.
- Added distributor registration, login, logout, and me APIs under `/api/distributor/*` and `/api/v1/distributor/*`.
- Added auth form requests, user/seller/distributor API resources, shared auth service, and seller/distributor registration services.
- Seller/distributor registrations create pending onboarding records only and do not auto-approve marketplace access.
- Preserved existing token auth, seller/distributor panel protection, admin token protection, and `.env`.

# 2026-07-08 - Multi-Vendor Product Stock Visibility Foundation

- Added pre-audit document for current multi-vendor, product, seller, distributor, and inventory status.
- Added guarded product/stock migration for missing product metadata, warranty, datasheet/manual/certificate, generic suggestion, marketplace visibility, and low-stock tables.
- Added public product extension APIs for attributes, specs, variants, datasheets, warranty, generic suggestions, compatibility, related/accessories, and stock summaries.
- Added product visibility, region stock, and generic suggestion services.
- Added multi-vendor/product/stock implementation, verification, seller, distributor, product, warranty, generic suggestion, and stock documentation.
- Preserved existing IoT, marketplace, vendor, distributor, seller, inventory, and auth modules.

# 2026-07-08 - Sell on NeoGiga and Commerce AI Public Foundation

- Added public Sell on NeoGiga, Seller Early Access, Distributor Network, and AI Commerce pages.
- Updated the homepage Sell on NeoGiga and AI Commerce positioning with early-access CTAs.
- Added additive seller/distributor application and commerce AI demo tables.
- Added validated public seller and distributor application APIs with pending-only submission behavior.
- Added protected admin application review, status update, conversion, and dashboard summary APIs.
- Added local-rule Commerce AI demo APIs for examples, session, message, and BOM generation.
- Added implementation, API, SEO, AI commerce, and verification documentation.
- Did not remove existing seller, vendor, distributor, inventory, product, IoT, LMS, or admin modules.

# 2026-07-08 - BOM Project Commerce Foundation

- Added additive BOM project-commerce migration for project categories, projects, project items, tools, LMS links, code samples, alternatives, price snapshots, build guides, user builds, and cart conversions.
- Added BOM project, category, and item models plus pricing, availability, custom-build, cart-conversion, alternatives, and LMS-link services.
- Added public BOM project read, item/availability, and server-side pricing APIs.
- Added authenticated BOM add-to-cart conversion, custom build, and user build APIs.
- Added protected admin BOM project and project-item management APIs.
- Added `NEOGIGA_BOM_FOUNDATION_REPORT.md` and `NEOGIGA_BOM_PROJECT_COMMERCE_GUIDE.md`.
- Did not run production migrations, seeders, or `.env` changes.

# 2026-07-08 - B2B Commerce Foundation

- Added additive B2B foundation migration for accounts, users, price lists, RFQs, quotations, purchase orders, credit terms, approval workflow, and activity logs.
- Added B2B API controllers, services, validation requests, models, and protected route groups.
- Added admin B2B account approval, RFQ, quotation, purchase-order, and price-list endpoints.
- Added B2B buyer role permissions to `RoleSeeder`.
- Added `NEOGIGA_B2B_FOUNDATION_REPORT.md` and `NEOGIGA_B2B_COMMERCE_API.md`.
- Did not run production migrations, seeders, or `.env` changes.

# 2026-07-08 - Distributor Foundation

- Added additive distributor foundation migration for distributor accounts, profiles, territories, staff, hierarchy/downlines, leads, customers, orders, commissions, payouts, and activity logs.
- Added distributor API controllers, services, validation requests, models, and protected route groups.
- Added admin distributor approval, territory assignment, commission approval, and payout marking endpoints.
- Added distributor role permissions to `RoleSeeder`.
- Added `NEOGIGA_DISTRIBUTOR_FOUNDATION_REPORT.md` and `NEOGIGA_DISTRIBUTOR_PANEL_API.md`.
- Did not run production migrations, seeders, or `.env` changes.

# 2026-07-07 - Multi-Vendor Seller Phase B Foundation

- Added additive Phase B migration for vendor roles, permissions, branches, seller products, vendor orders, payouts, commissions, reviews, and support tickets.
- Added seller API controllers, services, policies, and form requests for dashboard, profile, marketplace approvals, products, inventory, orders, payouts, performance, and support tickets.
- Implemented admin vendor APIs for vendor approval, rejection, suspension, marketplace approvals, product review, and payout marking.
- Added seller role permissions in `RoleSeeder` idempotently.
- Added `NEOGIGA_MULTIVENDOR_SELLER_PHASE_B_REPORT.md` and `NEOGIGA_SELLER_PANEL_API.md`.
- Did not run production migrations or modify `.env`.

# 2026-07-07 - Multi-Vendor B2B BOM AI Pre-Audit

- Added `NEOGIGA_MULTIVENDOR_B2B_AI_PRE_AUDIT.md` before implementation, per advanced commerce foundation safety instructions.
- Audited existing vendor, marketplace, inventory, POS, LMS, ERP/RFQ, AI, marketing, affiliate, role/permission, route, admin, and frontend structures.
- Identified missing seller, distributor, B2B, BOM project-commerce, commerce AI, and visibility-rule layers with duplicate/conflict risks.

# 2026-07-07 - Admin UI Responsive Polish

- Replaced brittle admin inline grid definitions with shared responsive layout classes across dashboard, LMS, inventory, POS, settings, and marketing pages.
- Added shared admin form-control styling and applied it to email, newsletter, and WhatsApp campaign forms.
- Verified all admin pages render without server errors before this follow-up patch.

# 2026-07-07 - Admin UI Shell Fix

- Fixed the admin console shell so the sidebar remains constrained to a desktop rail and the main content area renders visibly.
- Added defensive responsive layout rules for admin cards, tables, topbar, and mobile navigation.

# 2026-07-07 - Admin Access Recovery

- Reset the existing `admin@neogiga.com` super admin password at owner request.
- Created private server-side rollback backups under `/home/neogiga/backups/` before changing the credential hash.

# 2026-07-07 - Critical Hardening Start

- Added a public-safe `/health` endpoint for app, database, cache, queue, and writable-storage checks.
- Added `php artisan neogiga:smoke` as a production-safe smoke test command because production does not include PHPUnit dev dependencies.
- Protected incomplete `/api/v1/ai/*` POST endpoints with the existing API token middleware.
- Added optional hashed admin token support via `ADMIN_API_TOKEN_HASH` while preserving current `ADMIN_API_TOKEN` behavior.
- Updated `.env.example` for `neogiga_prod` and documented a safe production DB cutover plan without modifying live `.env` or data.
