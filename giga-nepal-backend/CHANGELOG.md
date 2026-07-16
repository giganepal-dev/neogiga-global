# Changelog

## 2026-07-16 - JLCPCB Missing-Only Continuation

- Added a guarded JLCPCB ETL `--missing-only` mode for the existing canonical NeoGiga target. It scans an explicit keyset cursor range, reads existing source links in bounded batches, imports only absent source IDs, and preserves already linked products, prices, offers, specifications, media, SEO metadata, and review decisions.
- Extended durable ETL evidence with scanned-row and already-linked-row counts. The mode requires the existing explicit NeoGiga write authorization, source checksum/provenance, a keyset cursor, and bounded transactions.
- New JLCPCB rows retain the existing NeoGiga SEO metadata structure and stay hidden/noindex until the established qualified-publication and SEO-governance services approve them; no public SEO, sitemap, or frontend state is changed by the import itself.

## 2026-07-15 - Governed 100,000-Product JLCPCB Catalog Expansion

- Preserved the verified JLCPCB SQLite snapshot at `/home/neogiga/backups/jlcpcb-source-20260714_192650/jlcpcb-components.sqlite3` with SHA-256 `9334f49b7d730b7ed7e5beb3c0360fe89a3a158605af3c4512a10f850c23c986` and recorded source/license provenance without downloading a replacement dataset.
- Created and isolated-restore-verified the pre-operation database, storage, release and environment backup at `/home/neogiga/backups/pre-100k-catalog-20260714_193852`; all governed apply commands require its verified manifests and checksums.
- Extended the existing canonical ETL with stable keyset resume, maximum 1,000-source-row transactions, durable per-chunk checkpoints, exact source lineage, a separate canonical-alias table, immutable existing catalog values, explicit `--publish --pilot --yes` authorization, and fail-closed transform handling.
- Kept all new canonical products hidden and pending at import time, reused the existing NeoGiga-branded local placeholder for newly inserted products, and left existing product data, specifications, documents, SEO metadata, offers, media and UI/theme records unchanged.
- Added dry-run-first JLCPCB commerce enrichment that creates only missing GLOBAL/USD prices using the established source-cost-plus-5% formula and never overwrites an existing marketplace price.
- Added a non-destructive marketplace-price precision expansion to eight decimals for the one verified sub-`0.0001` USD source cost, retaining the same rounded source cost and exact 5% formula instead of rounding the product to zero or inventing a minimum price.
- Added a separate deterministic supplier-availability overlay for quote/RFQ use: requested 1,000-10,000-unit estimates are capped by observed supplier stock, allocate 80% to Shenzhen and rotate the remainder across configured regional warehouses, and cannot reserve, fulfill, or modify physical `inventory_stocks`.
- Made supplier-availability writer ownership explicitly opt-in: additive governance fields conservatively adopt only the complete audited signature of the prior JLC generator, leave every ambiguous or externally created row unclaimed, and permit enrichment updates only with the exact owner tag plus explicit unlocked/non-manual state; manual and locked rows remain unchanged.
- Added hidden JLCPCB image-candidate staging from retained `components.extra.images` metadata. Candidates are exact allowlisted HTTPS URLs, remain inactive and rights-pending, never overwrite an existing review, and are not downloaded, hotlinked or published.
- Added a bounded, dry-run-first qualified publication gate requiring complete identity/raw snapshot, minimum source quality, an exact non-rejected JLC offer, active GLOBAL/USD price, active local image, no rejection, and no other pending source; apply requires the exact plan hash, verified backup, and explicit `--yes`.
- Added publication audit metadata with `source_notes`, `confidence_level`, `last_updated` and the `Advisory only` disclaimer while leaving external datasheets and image candidates non-public.
- Reworded generated global/regional catalog SEO descriptions to describe technical data and RFQ-based, quote-only supplier availability estimates without asserting local stock, warehouse fulfilment, low MOQ, quality, or dispatch; regional canonicals, titles, robots policy, manual overrides and frontend design remain unchanged.
- Added `catalog:jlcpcb-govern-seo`, a deterministic dry-run-first plan that inserts only missing JLCPCB product SEO rows and transitions only untouched importer-owned brand/category SEO metadata (including linked ancestor categories) to dynamic regional governance; apply requires the exact plan hash, `--yes`, a checksum/restore-verified backup, and bounded transactions while preserving existing product SEO, manual/locked/explicit overrides, localized metadata and keywords.
- Extended manufacturer sitemaps to merge active dedicated identities with deduplicated virtual identities derived only from products that pass the shared publication gate, so approved JLCPCB manufacturer names are covered without exposing pending imports or replacing stored manufacturer records.

## 2026-07-15 - Existing JLCPCB Data Audit and Fail-Closed Publication Gate

- Audited PR #15 and left it unmerged because it introduces a parallel catalog, production-incompatible foreign-key changes, destructive rollback ownership, and no tested reconciliation path for the populated NeoGiga catalog.
- Audited the existing live JLCPCB/LCSC state instead of downloading or replaying the missing raw source: 69,880 unique source-linked products already exist with zero duplicate source identities or orphan links.
- Created and checksum-verified the task-specific PostgreSQL, storage, current-release and environment backup at `/home/neogiga/backups/jlcpcb-existing-data-20260714_181929`; restored the PostgreSQL dump into an isolated database, verified baseline counts, then dropped the temporary restore database.
- Independently hashed and restore-tested the supplied pre-first-import dump at `/home/neogiga/backups/jlcpcb-import-20260710-110520/neogiga_before_jlcpcb_import.dump`; confirmed it is a one-product pre-JLCPCB recovery point rather than raw supplier data, and dropped the isolated validation database without touching production.
- Preserved all catalog and commerce data. This change runs no import, migration, price rewrite, stock allocation, media replacement, review-state update, route removal, or frontend design/template change.
- Added one reusable `ProductPublicationGate`: legacy/manual catalog records retain their established status path, while every source-linked product now requires both `products.approval_status = approved` and all linked `catalog_product_sources.review_status = approved` before public use.
- Applied the gate to global/regional product pages, API product/search/facets, product and manufacturer sitemaps, category/brand/SEO landings, homepage counts/cards, RFQ, Commerce AI, related/compatible suggestions, product reviews, public inventory, POS search/sales, BOM/LMS components, and stale web/API carts without removing stored cart or catalog rows.
- Versioned sitemap and brand cache identities so previously cached pending-product URLs/counts cannot survive a future deployment of the gate.
- Added PostgreSQL regression coverage for pending and approved imports, independent product/source approval requirements, public detail/search/POS/inventory/review/sitemap isolation, and preservation of existing manual products without source links.
- Added the required existing-data inventory, data-quality, field-mapping, duplicate, checksum, baseline, restore, post-integrity, UI-preservation, and final import-result reports. They record that exact source-unit-price-plus-5% marketplace prices already exist, while cost-price provenance and honest 10,000-unit stock classification remain unresolved and therefore were not fabricated or overwritten.

## 2026-07-14 - Shared Regional Frontend and Operational Admin Upgrade

- Replaced the hard-coded regional landing preview with the existing shared NeoGiga frontend layout, live catalog/category/brand records, official logo assets, functional search/RFQ/seller links and marketplace-specific status without replacing the established theme or stored data.
- Corrected marketplace resolution so regional domains retain their edition on `/en`, global-host marketplace prefixes continue to work, and `www` branded aliases resolve through the existing apex-domain records.
- Added distinct regional title, description, canonical, robots, hreflang and structured data rendering; inactive preview editions remain `noindex` and active editions retain their configured SEO state.
- Prevented GLOBAL USD fallback prices from being mislabeled as NPR/INR on regional homepages by rendering only the selected marketplace price or its explicit GLOBAL price currency.
- Expanded the existing admin dashboard with linked catalog/commerce KPIs, truthful API route and health metrics, encrypted email-provider readiness, and direct links to SMTP/API credentials, campaign composition and customer spreadsheet imports.
- Converted remaining static admin controls for categories, SEO, media, products, access control, inventory and POS into registered routes/forms while retaining their existing authorization and audit workflows.
- Added schema preflight guards for optional dashboard tables so PostgreSQL requests and test transactions do not fail when an optional module has not been migrated.
- Audited the global and regional estate: eight active Laravel hosts share this platform; legacy Nepal/India WordPress apex cutovers remain intentionally blocked until full catalog/customer/order/media/SEO backups, URL maps and India mail/DNS access are available.
- Added permanent regional-host canonical normalization: non-`/en` marketplace-prefix aliases redirect to the edition's configured live `/en` URL while preserving deep paths and query strings, global-host prefix routes remain available, and canonical tags point directly at the normalized destination.
- Kept Nepal and India canonicals, hreflang links, prefix redirects and geo recommendations on the live `np.neogiga.com` / `in.neogiga.com` Laravel hosts through environment-overridable cutover gates, preventing canonicals from pointing at unmigrated branded WordPress `/en` URLs.
- Moved category-tree expansion into a same-origin external script so the control works under the existing `script-src 'self'` Content Security Policy without changing the admin design.
- Enforced super-admin-only permission management, synchronized legacy role JSON and active permission-pivot grants transactionally, made runtime authorization read both stores, and protected wildcard roles from matrix mutation.
- Reworked low-stock refresh to reconcile every qualifying stock row in locked chunks and resolve alerts only when an authoritative stock query confirms recovery; POS refunds now use a locked transaction, four-decimal fixed-point arithmetic and required per-form/request intent keys so retries are idempotent without suppressing legitimate identical refunds.

## 2026-07-14 - Governed Draft Catalog Release Foundation

- Added a dry-run-first catalog release configuration for ElecForest products with exact 5% USD pricing, the fixed 10,000-unit China/regional warehouse allocation, explicit template-SKU quarantine, bounded chunks, and private release reports.
- Added a non-destructive price-column expansion from `DECIMAL(12,2)` to `DECIMAL(15,4)` so cost and 5% sale prices retain exact precision; rollback refuses values that cannot be represented safely by the legacy schema.
- Added nullable source and pricing-rule provenance fields to marketplace price rows; existing prices remain unchanged and rollback intentionally retains audit data.
- Added a follow-up non-destructive compatibility migration that widens a pre-existing 40-character marketplace price review-status label to 80 characters after the first production apply correctly failed closed; rollback never narrows the provenance field.
- Added an idempotent, bounded and transactional `catalog:release-drafts` command that is read-only by default and requires exact count/hash, verified backup and explicit unverified-license publication-risk acknowledgement before any apply; original rights facts and open review tasks are retained.
- Added focused release tests for dry-run purity, exact 5% price precision, the 8,000/667/667/666 split, verification-warehouse exclusion, provenance, quarantine, rights-gated real media, checksum rejection and idempotent replay.
- Deployed commit `70d2127` as `/home/neogiga/laravel/releases/20260714-214657-catalog-release-width-fix` after immutable full, freeze and pre-width-fix PostgreSQL/storage backups with recorded SHA-256 checksums.
- Recorded the first production apply as a safe fail-closed event: the legacy 40-character `marketplace_product_prices.source_review_status` rejected the first sellable-product write, no sellable-product transaction committed, and only the planned hidden `NG-EF-` sentinel quarantine was retained. Incremental migration `2026_07_14_182000_widen_marketplace_price_review_status` widened that field to 80 characters without deleting or rewriting user data.
- Completed the governed retry against plan hash `483f0c2a1b3f292115ab7db7cd37773a7af3b852fb7f97f3897a511f75e12129`: 3,176 eligible products released, 3,176 exact cost-plus-5% price rows created, 12,704 warehouse stock rows and matching movements created, and 9,777 checksum/file-verified real images activated.
- Allocated exactly 10,000 units per released product: 8,000 Shenzhen, 667 Kathmandu, 667 New Delhi and 666 Dubai. The operator explicitly acknowledged publication risk; media licensing remains independently unverified and all existing media-rights, application, brand, manufacturer and taxonomy review work remains open.
- Kept Nepal and India canonical output on `np.neogiga.com` and `in.neogiga.com`; the independent `giganepal.com` and `neogiga.in` WordPress apex systems remain untouched pending their no-data-loss, URL, mail and DNS cutover gates.
- Stored and read back the completed private release report at `catalog-releases/20260714-161552-101019-483f0c2a1b3f2921-completed.json` with SHA-256 `3a969ebd6783c7113fc8b23a2d6351acacf94e0b64058dc9ebd36e3af0e51e9d`; final database, file, queue and browser reconciliation evidence is recorded in the production audit.
- Corrected post-release regional product SEO by classifying the existing `elecforest_deterministic_seo_generator` marker as generated metadata, so Nepal and India render their own product title and canonical while locked/manual admin overrides remain unchanged.
- Kept public-disk product media on the current regional request host so strict same-origin CSP continues to protect every edition without blocking the shared real product images; external source/CDN URLs and non-web serialization behavior remain unchanged.
- Added a dedicated long-running `database_imports` queue connection and routed catalog search rebuilds to `catalog-imports`, with aligned retry/worker timeouts and terminal failure bookkeeping so an interrupted rebuild cannot remain falsely marked as running; the worker continues to consume the existing customer `imports` queue from the same non-destructive jobs table.
- Deployed final commit `799866a` as `/home/neogiga/laravel/releases/20260714-223816-regional-media-origin`. Production reconciliation returned zero governed data anomalies, all 9,777 image files passed a second checksum/signature/dimension read, the ElecForest index rebuild completed, persistent public media remained 78,000 files, and live Global/Nepal/India/admin/health canaries passed. The complete suite passed 189 tests and 1,031 assertions with 11 intentional skips.

## 2026-07-14 - Catalog Media, Canonical Brands and Governed SEO

- Added permission-gated, product-scoped image upload, ordering, primary selection, replacement, metadata, safe deactivation and API operations while preserving existing rows/files and exposing active images only on the storefront.
- Added `/en/brands` and canonical `/en/brand/{slug}` pages, locale-preserving 301 legacy redirects, brand visibility/publication controls, cache invalidation and stable no-product/RFQ states.
- Added one marketplace-aware SEO template/governance layer for products, every category level, brands and manufacturers with generated/manual separation, locks, reasoned robots, append-only versions, admin history/rollback and resumable dry-run-first regeneration.
- Integrated the supplied NeoGiga logo into favicon/app/touch assets, header/footer marks, social media and compatible product/category placeholders without changing the existing page design or stored media paths.
- Deployed `/home/neogiga/laravel/releases/20260714-140500-catalog-seo-media` after validated backups. Production now has complete SEO rows for 73,058 products and 441 categories, 70,058 new history versions, and a zero-change final dry run; 3,179 product and 262 category manual/locked records were preserved.
- Preserved production counts for products, categories, brands, images, customers, orders, inventory and prices. Full verification passed: 172 tests, 771 assertions, 11 intentional skips, asset build/caches, live route/API canaries and desktop/mobile browser regression checks.

## 2026-07-14 - Production-Safe ElecForest Catalog Import

- Added a non-destructive production compatibility migration that widens only product provenance URL fields from 255-character strings to text so complete source URLs are retained without truncation.
- Added a checksum-safe media fast path that reuses the complete validated WebP/AVIF derivative set when every expected file already exists, avoiding unnecessary production image decoding and resampling.
- Added an additive, resumable ElecForest catalog integration with provenance, identity collision protection, category mapping, professional content normalization, specifications/applications, editable SEO, source-offer isolation, review gates and protected admin operations.
- Imported all 3,177 sellable products from the 3,178-line source as hidden drafts; the one collection-page row was correctly rejected, and a clean full rerun left all 3,177 products unchanged.
- Downloaded all 9,801 product-image candidates in production, stored 9,777 checksum-deduplicated inactive image rows with 63,054 derivative references, and finished with zero ElecForest queued/failed jobs.
- Preserved all existing regional prices and inventory: the import wrote zero marketplace, vendor, country-price or warehouse-stock rows and kept supplier observations separate.
- Added the required eight audit/implementation/results reports, incremental migration rollback verification, source/database checksums, and full provenance for imported and generated data.
- Verified 169 passing backend tests, 11 intentional skips and 709 assertions; migration status, database integrity, local catalog/admin routes, regional canonicals, cache compilation, Composer checks and the frontend build also pass.
- Deployed release `/home/neogiga/laravel/releases/20260714-103845-elecforest` after two validated custom-format PostgreSQL backups; production has 3,177 hidden drafts, strict publication remains at zero, and identity, taxonomy and media-rights reviews are still required.
- Added `ELECFOREST_PRODUCTION_DEPLOYMENT.md` with the live release, backup checksums, source checksum, database/media totals, route canaries, isolation results and safety state.

## 2026-07-14 - Admin-Configurable Email Providers and Campaign Operations

- Added persistent production workers for campaign preparation/marketing, transactional/webhook delivery, and customer spreadsheet imports; workers retain the existing disabled/test/approval sending gates and run at explicit low priorities.
- Added a pre-migration schema-capability guard so production can boot and run the additive provider migration when an older `email_provider_configs` table exists without the new channel/security columns.
- Added encrypted SMTP and generic provider-API configuration for independent marketing and transactional channels through the existing admin panel, with secret-preserving updates, explicit clearing, test actions, test recipients, sender selection, and rate/daily limits.
- Added runtime provider loading for HTTP requests and queue jobs, a campaign-capable SMTP adapter, and safe environment fallback until an admin configuration is saved; secrets are never returned to the browser or audit log.
- Added audited sender-profile editing and retained verified/enabled sender, consent, suppression, approval, production authorization, and test-mode gates.
- Expanded the email page with HTML/plain-text composition, versioned content, template and customer-segment selection, scheduling, explicit live approval, prepare/run controls, status visibility, and a direct link to the mass customer spreadsheet importer.
- Reused the existing additive provider schema; no destructive migration or customer/catalog data rewrite is required.
- Verified the complete backend suite (149 passed, 11 intentional skips, 598 assertions), focused communication/import/account/checkout suite (26 passed, 175 assertions), all 900 routes, cache compilation, Composer audit, reference-workbook dry-run, and Vite production build.

## 2026-07-13 - Customer Import and Communication Governance

- Added reversible normalized customer identity, spreadsheet import, source provenance, consent/preferences, suppression, audience snapshot, sender/domain, provider event, and communication-log schema extensions without replacing existing customer or marketing records.
- Added XLSX/XLS/CSV/ODS preview/import APIs, admin workflow, resumable import queue, and `neogiga:import-customers` dry-run/import CLI with configurable mappings, country normalization, deduplication, and no inferred marketing consent.
- Added customer/country/segment CRM views and permission-gated formula-safe exports within the existing admin theme.
- Separated marketing and transactional provider configuration and queues; added regional sender gates, immutable campaign/newsletter templates, approvals, test-recipient allowlists, frozen consent-aware audience snapshots, schedule preparation, rate/daily limits, resumable batches, and operational controls.
- Added confirmation-based opaque unsubscribe/preferences routes, central eligibility/suppression services, signed idempotent webhooks with encrypted raw payloads, bounce/complaint processing, and first-party campaign/country analytics.
- Wired account verification/welcome/password changes, seller/distributor approval, checkout/order, and OTP communication through the transactional queue and retained marketing delivery on independent lower-priority queues.
- Seeded disabled/unverified Global, India, Nepal, Support, Orders, Billing, RFQ, and Seller Communication sender profiles plus marketing/transactional domain verification records.
- Upgraded Laravel 11.54 to 12.63 after a checksum-verified backup, resolving three framework advisories. Composer now reports no known advisories.
- Verified the complete backend suite (147 passed, 11 intentional skips, 572 assertions), focused communication suite (24 passed, 149 assertions), reference-workbook dry-run, all 896 routes, scheduler/cache compilation, and Vite build. No real email was sent and production delivery stays fail-closed.

## 2026-07-13 - NeoGiga Logo, Placeholder and Complete Catalog Sitemap Upgrade

- Added versioned NeoGiga favicon, app-icon, Apple touch icon, catalog-placeholder, and 1200×630 social-image assets derived from the supplied official logo.
- Replaced inline legacy favicons and catalog placeholder artwork without changing page layout, spacing, typography, or the existing theme.
- Preserved the legacy placeholder URL as a compatibility wrapper so existing product image records render the new branded asset without database updates.
- Expanded sharded sitemap coverage to every active brand and manufacturer, including a data-safe fallback built from existing product manufacturer names when optional manufacturer tables are absent.
- Confirmed that root, subcategory, and child-category records are all included in the category sitemap; added regression coverage for nested taxonomy URLs.
- Aligned brand and manufacturer CollectionPage structured-data URLs with the exact current localized URL.
- Kept hreflang rendering backward-compatible with marketplace edition payloads already present in the production cache during rolling deployment.
- Deployment is code/assets only: no migrations, seeders, imports, or catalog-row writes.
- Extended the NeoGiga image fallback to every category hub/detail template, covering all categories without stored media across every localized storefront without changing category records.
- Added Category ItemList structured data for the main taxonomy hub and subcategory collections.
- Verified exact live coverage of all 69,875 public product URLs and all 439 active category URLs across the complete sharded sitemap index.

## 2026-07-13 - Technical SEO Canonical and Sitemap Upgrade

- Fixed marketplace SEO rendering so canonical and `og:url` values retain the current localized page path while using the configured canonical marketplace host.
- Updated marketplace URL generation and hreflang output to prefer canonical domains and omit hidden, non-indexable, or hreflang-disabled editions.
- Added safe catalog URL policy: filtered/search pages emit `noindex,follow`; clean pagination remains indexable and uses page-specific canonicals.
- Added default social images and protected cart, checkout, password reset, login, registration, and account pages from indexing.
- Upgraded Product JSON-LD with canonical localized URLs, images, manufacturer/brand URLs, conditional aggregate ratings, and conditional offers; removed null rating values.
- Added ItemList and localized BreadcrumbList JSON-LD to product listings and category pages without changing visual design.
- Replaced the 5,000-product sitemap cap with a sitemap index and 10,000-URL shards for static pages, categories, and every publicly visible product.
- Sitemap cache keys now include catalog counts and latest update timestamps, catalog hubs are included, and unknown/fake `lastmod` values are omitted.
- Corrected the production robots sitemap URL from `neogiga.in` to `neogiga.com` and excluded private workflow URLs from crawling.
- Added focused SEO regression tests and stored pre-change backups in the repository-level `.codex-backups/2026-07-13-seo-technical-upgrade/` directory.

## 2026-07-13 - Catalog Identity: Brand and Manufacturer Foundation

- Added backend-only catalog SEO/content generation for products, brands, categories, manufacturers, and sellers via `neogiga:generate-seo-content`, with dry-run default, scoped type options, country/tag templates, and force/apply controls.
- Product SEO generation now produces titles in the requested `{Brand} {Product_Name} {MPN} on NeoGiga {Country} - {Website Tag}` format, meta descriptions, short descriptions, descriptions, and derived product specs from existing catalog facts only.
- Product detail metadata now reads generated product SEO rows/fields when present without changing the visual page design.
- Added additive manufacturer identity tables, manufacturer aliases, and nullable product/brand manufacturer links without changing existing product rows.
- Added source lineage and identifier columns for products where missing, including normalized MPN, GTIN, HS code, ECCN, lifecycle, source URL/name, confidence, and verification date fields.
- Added `Manufacturer` and `ManufacturerAlias` models plus product/brand manufacturer relationships.
- Added canonical public `/manufacturer/{slug}` and `/brand/{slug}` pages with legacy plural redirects.
- Updated product detail pages to link brand, manufacturer, and MPN identities and include manufacturer organization data in Product JSON-LD.
- Added the read-only `neogiga:audit-catalog` Artisan command to report catalog identity, brand, manufacturer, product identifier, and inventory coverage.

## 2026-07-13 - Gap Feature Fill: Checkout Payment Allowlist

- Added interim `admin.permission` middleware for token-gated admin API routes and required
  `settings.manage` for admin console settings writes, with deny-by-default behavior when
  `ADMIN_API_TOKEN_PERMISSIONS` is not configured.
- Added `PaymentMethodPolicyService` so checkout payment methods are validated server-side from the
  `payment_providers` registry when configured, with legacy fallback only while the registry is empty.
- Wired the policy into API checkout and public web checkout; tampered/disabled methods now fail
  validation instead of relying on static controller `in:` rules.
- Updated the checkout page to render only enabled methods for the cart currency and show a safe
  unavailable state when no method is enabled.
- Added feature coverage for disabled/tampered payment methods and kept existing checkout/promo
  flows using explicitly enabled test providers.
- Fixed the existing PCB API route middleware typo that prevented Artisan from booting.
- Reconciled the supplier importer base contract with existing array-based supplier importers so
  Artisan can resolve importer commands without PHP signature fatals.
- Fixed PCB migration foreign-key type mismatches against existing bigint-backed `users`,
  `products`, and `warehouses` tables while keeping PCB-owned IDs as UUIDs.
- Fixed the CPL validation error relation to match the bigint-backed `pcb_cpl_lines.id` column.
- Converted the duplicate supplier import migration into an idempotent extension of the existing
  ERP suppliers table, preserving existing supplier records and adding only missing importer columns.
- Updated the default feature smoke test to assert the existing `/` to `/en` canonical redirect
  instead of expecting a direct 200 response.
- Updated the region stock visibility smoke test to assert the same canonical locale redirect.

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
- Added feature-flagged forced marketplace recommendation redirects for normal GET/HEAD traffic, preserving crawler, admin/API, health, sitemap, and explicit user-preference bypasses; dedicated regional domains land at their storefront homepages until catalog paths are available there.
- Hardened the shared public frontend layout with the dark NeoGiga marketplace design tokens while keeping fonts self-contained for the existing CSP.
- Replaced scheduled marketing placeholder jobs with first-party safe-mode implementations for trending products/categories, top searches, abandoned cart capture/reminders, segment refresh, regional sales reports, and transactional email queue processing.
- Added a Composer post-autoload patch and app database config fallback for Laravel 11's deprecated `PDO::MYSQL_ATTR_SSL_CA` default so PHP 8.5 local/CI runs stay clean without a major framework upgrade.
- Added licensed product image source metadata columns plus dry-run-first `product-images:audit` and `product-images:import-licensed-manifest` commands, so placeholder images can be replaced only from local files with explicit redistribution permission.
- Added a hidden `product_image_candidates` review queue and `product-images:discover-candidates` command that can collect public source-page image URL candidates by product/MPN without downloading or publishing unapproved media.
- Tightened public image candidate discovery with a configurable confidence floor so generic source-page images are skipped instead of filling the review queue.
- Added product image candidate listing/export and review-status commands so operations can prepare licensed image manifests and approve/reject candidates without changing public product images.
- Added the BOM procurement import API: authenticated users can upload/paste BOM CSVs, auto-match lines by normalized MPN, manually review ambiguous lines, and convert the full BOM into an RFQ.
- Connected BOM imports to the Admin Console with `/admin/bom-imports`, sidebar navigation, KPIs, customer/RFQ links, and line-level match visibility.

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

# 2026-07-11 - Admin System Health Control Center

- Added an authenticated `/admin/system-health` page for database, cache, Redis, storage, queue, API, search, catalog media, and import health.
- Added the System Health navigation item under the admin Overview section so the control-center change is visible from the sidebar.
- Added `FINAL_COMPLETION_BASELINE.md`, `FINAL_COMPLETION_BACKUP_REPORT.md`, and `FINAL_COMPLETION_ROLLBACK_PLAN.md` based on the latest audit documents.
- Preserved existing admin routes, catalog data, import workflows, and media records; no migration or destructive data change was introduced.

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
