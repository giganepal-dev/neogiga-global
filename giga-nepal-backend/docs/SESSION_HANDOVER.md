# NeoGiga Development Session Handover

**Date:** 2026-07-13

**Session implementation branch:** `pcb-usable-portal` at `c94b32a`

**Repository:** `giganepal-dev/neogiga-global`
**Production application:** `/home/neogiga/laravel/current`

## 1. Project purpose and architecture

NeoGiga is a multi-marketplace electronics and engineering commerce platform. It maintains one canonical global product catalog, identified by manufacturer plus normalized MPN, while marketplace-specific data such as price, inventory, visibility, currency, delivery, tax, and seller availability are stored as overlays.

Public storefronts use locale-prefixed paths such as `/en/...`; the platform also supports regional storefront routing and a separate PCB portal at `pcb.neogiga.com`. The Laravel application provides the storefront, admin workspace, catalog ingestion/review workflows, BOM import service, regional commerce services, and PCB workflows.

## 2. Repository and active branch

- Git root: `/Users/ashokdhamala/Downloads/neogiga-main 2`
- Laravel project: `/Users/ashokdhamala/Downloads/neogiga-main 2/giga-nepal-backend`
- Session branch: `pcb-usable-portal`
- Session branch head: `c94b32a Connect header to BOM uploader workflow`
- Remote branch: `origin/pcb-usable-portal`
- The shared working checkout is currently on `main` at `0909d04`; its untracked user files were intentionally not changed.

The session work has been pushed to `pcb-usable-portal`. It has **not** been merged into `main`; reconcile and merge through a reviewed pull request or an intentional branch integration before treating `main` as the source of truth.

## 3. Work completed in this session

- Connected the public `Upload BOM` header action to a real localized BOM uploader at `/en/bom-imports`.
- Reused the existing `BomImportService` for CSV/TSV/TXT uploads and pasted BOM content; no duplicate parser or matching logic was introduced.
- Added an authenticated BOM submission endpoint with throttling and a guest sign-in state.
- Refined the public header: BOM and PCB actions sit beside search, country storefront names were replaced by flag-oriented controls, and marketplace switching moved to the footer.
- Fixed public product-page contrast, media rendering, and narrow-screen layouts.
- Imported category-matched media from the user-provided local image library into production with source metadata and checksums.
- Added category image presentation and a guarded image-import command that only replaces NeoGiga placeholders.
- Validated production storefront, catalog, category, PCB, admin login, and BOM uploader routes.

Earlier commits on the same session branch also contain the PCB portal, regional-commerce boundaries and readiness checks, supplier ingestion/review workflows, catalog identity controls, delivery-zone work, catalog seeding safeguards, and admin product/user fixes.

## 4. Files created or modified

### Directly relevant to the latest session work

- `app/Http/Controllers/Web/BomImportPageController.php` (new)
- `resources/views/frontend/bom-imports/index.blade.php` (new)
- `tests/Feature/BomUploaderPageTest.php` (new)
- `routes/web.php`
- `routes/console.php`
- `app/Http/Controllers/Web/ProductPageController.php`
- `app/Services/Catalog/ProductAvailabilityService.php`
- `resources/views/frontend/layout.blade.php`
- `resources/views/frontend/products/show.blade.php`
- `resources/views/frontend/categories/index.blade.php`
- `resources/views/frontend/categories/show.blade.php`
- `resources/views/landing.blade.php`

### Broader branch work

The branch additionally changes PCB controllers, catalog ingestion services and admin workflows, regional-commerce services, product administration views, and their feature tests. Review `git log main..pcb-usable-portal` and each commit before a merge; this handover deliberately avoids duplicating source code or a long file dump.

## 5. Database migrations and schema changes

No new migration was added for the latest BOM page or category-media import. The media import writes to existing product/category image and metadata fields, preserving prior values unless the image was a NeoGiga placeholder.

Migrations already present on `pcb-usable-portal` relative to `main` include:

- `2026_07_10_120000_create_jlcpcb_catalog_provenance_tables.php`
- `2026_07_11_000001_create_pcb_projects_table.php`
- `2026_07_11_000002_create_pcb_files_table.php`
- `2026_07_11_000003_create_pcb_gerber_and_quotes_table.php`
- `2026_07_11_000004_create_pcb_bom_cpl_tables.php`
- `2026_07_11_052500_add_products_brand_normalized_mpn_index.php`
- `2026_07_12_000001_complete_pcb_quote_lifecycle.php`
- `2026_07_12_020000_add_source_provenance_to_marketplace_prices.php`
- `2026_07_12_030000_create_supplier_catalog_ingestion_tables.php`
- `2026_07_12_031000_add_supplier_product_quality_score.php`
- `2026_07_13_090000_extend_product_brands_for_storefront_menu.php`

These migrations add PCB lifecycle data, canonical catalog identity indexing, source/provenance fields, supplier catalog review/staging, quality scoring, marketplace-price provenance, and storefront brand configuration. They must be reviewed and applied incrementally with a production backup; do not use destructive migration commands.

## 6. APIs, routes, controllers, models, and services added

### BOM uploader

- `GET /bom-imports` redirects to `/en/bom-imports`.
- `GET /{locale}/bom-imports` renders the authenticated/guest BOM uploader.
- `POST /{locale}/bom-imports` requires authentication and is throttled; it delegates parsing, matching, and persistence to `App\\Services\\Bom\\BomImportService`.
- `App\\Http\\Controllers\\Web\\BomImportPageController` is the SSR adapter for the existing BOM service.

### Existing branch services and workflows extended

- `ProductAvailabilityService` resolves regional product availability without duplicating canonical products.
- Regional-commerce and checkout-readiness services enforce marketplace inventory/pricing boundaries.
- Supplier ingestion/review workflows stage imported supplier records and preserve source/quality data before publication.
- PCB project, file, Gerber, BOM/CPL, and quote lifecycle routes/services support the `pcb.neogiga.com` workspace.

Existing API BOM routes remain the integration path for programmatic clients. The new web route is intentionally a server-rendered frontend over the same established domain service.

## 7. Commands already executed

- Focused Laravel feature tests for BOM and global-commerce behavior.
- PHP syntax checks for the new controller.
- Blade view compilation and Laravel cache rebuilds.
- Production `php artisan neogiga:smoke` checks.
- Production route checks with HTTP requests for storefront, product, category, PCB, admin login, and BOM uploader paths.
- Production media import dry run and guarded live import from the copied local image library.
- Responsive browser checks at mobile (`390x844`) and desktop (`1280x720`) widths.
- Git push of the session branch to `git@github.com:giganepal-dev/neogiga-global.git`.

No credentials, database URLs, or other secrets are recorded in this document.

## 8. Tests passed and failed

Passed:

- `tests/Feature/BomUploaderPageTest.php` with global-commerce coverage: 22 tests, 85 assertions.
- Earlier focused regional-commerce, brand-administration, and global-commerce suites: 26 tests, 103 assertions.
- Later focused regional-commerce/global-commerce run: 23 tests, 93 assertions.
- PHP lint, Blade view cache compilation, production smoke checks, and the documented HTTP smoke requests passed.

Failed:

- No test failure was observed in this session.

Not run:

- The complete `php artisan test` suite was not run.
- A production migration run was not performed as part of the latest documentation/BOM deployment work.
- Tablet runtime visual validation was not completed; the browser viewport control did not accept the requested tablet dimensions.

## 9. Current bugs and unresolved issues

- `main` does not yet contain the pushed session branch; deployment/merge governance needs to be resolved.
- The BOM page is functional for authenticated users, but customer/B2B account onboarding and ownership should be confirmed. The public site does not yet provide a complete self-service B2B signup journey.
- 5,734 catalog images were updated; 64,147 active products still use NeoGiga placeholders. Imported local-media rights are marked as user-supplied and require a formal rights review before broad commercial reuse.
- The importer intentionally skips ambiguous category matches and does not overwrite curated assets. More image matching needs source-specific, reviewable mappings.
- Full automated accessibility, tablet, and checkout-path testing remain outstanding.

## 10. Remaining work, ordered by priority

1. **P0:** Review, merge, and deploy `pcb-usable-portal` through the normal release process; preserve backups and verify migrations first.
2. **P0:** Perform an authenticated production BOM upload with a non-sensitive sample and verify import ownership, quote handoff, and error reporting.
3. **P1:** Establish a licensed media-source policy, review imported local assets, and continue image coverage using deterministic mappings.
4. **P1:** Complete customer/B2B account onboarding, seller/RFQ handoff, and end-to-end checkout acceptance tests.
5. **P1:** Apply remaining branch migrations only after staging validation and a database backup.
6. **P2:** Run full regression, mobile/tablet/browser accessibility checks, performance checks, and structured-data validation.
7. **P2:** Continue admin usability work for catalog review, supplier ingestion, regional overlays, and operational dashboards.

## 11. Exact next development steps

1. Fetch the repository and compare `main` with `origin/pcb-usable-portal`.
2. Open a reviewable pull request or perform an explicitly approved merge; resolve conflicts without discarding `main` changes.
3. In staging, run `php artisan migrate --pretend`, focused feature tests, `php artisan route:list`, and the complete suite where feasible.
4. Back up the production database and release directory, then deploy the merged release and run migrations incrementally.
5. Run `php artisan optimize:clear`, cache/view/route rebuilds as the `neogiga` application user, then run `php artisan neogiga:smoke`.
6. Log in with a test B2B user, upload a small CSV BOM, and validate the resulting import, matching, and admin/RFQ workflow.
7. Complete a tablet visual sweep and an accessibility audit for header, product, category, BOM, and checkout paths.
8. Continue product media enrichment only with source and license records populated for every asset.

## 12. Important decisions and assumptions

- Canonical product identity is manufacturer plus normalized MPN; regional data is an overlay, not a duplicated product record.
- The SSR BOM page must reuse `BomImportService`; the page does not implement an alternate BOM parser.
- Public storefront location switching is in the footer. Header controls show the active context, flags/currency, and primary operational actions.
- Header `PCB Check` routes to the established PCB portal rather than a duplicate tool.
- Category-media import is guarded: only direct, deterministic category-folder matches and active NeoGiga placeholder images may be replaced.
- Imported image metadata includes source/provenance, checksum, timestamps, original file values, and a rights-review note. This is not equivalent to verified redistribution permission.
- AI output must continue to expose source notes, confidence, last-updated data, and an advisory-only disclaimer when that feature is extended.

## 13. Deployment and environment changes

The following production code releases and data operations were applied during this session:

- BOM uploader deployment backup: `/home/neogiga/backups/bom-uploader-20260713-123214`
- Header deployment backup: `/home/neogiga/backups/public-header-20260713-112004`
- Catalog-media deployment backup: `/home/neogiga/backups/catalog-media-20260713-113348`
- Product mobile-layout backup: `/home/neogiga/backups/product-mobile-layout-20260713-114201`
- Landing marketplace-footer backup: `/home/neogiga/backups/landing-marketplace-footer-20260713-114735`
- Catalog-media database backup: `/home/neogiga/backups/catalog-media-20260713-113348/catalog-tables.sql`
- User image library copied to `/home/neogiga/imports/catalog-image-library-20260713`.

The guarded production media import reviewed 69,881 products, planned 5,734 product replacements and 84 category images, then applied those matches. Production smoke requests returned HTTP 200 for the principal storefront, catalog, category, PCB, admin-login, and BOM-upload routes at the time of validation.

## 14. Scope note

This handover records decisions, evidence, risks, and next actions without embedding long logs, credentials, or duplicate code. Use Git history, deployment backups, Laravel logs, and the focused test files for detailed forensic review.
