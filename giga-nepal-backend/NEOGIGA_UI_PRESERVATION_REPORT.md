# NeoGiga UI Preservation Report — JLCPCB Existing Data

Date: 2026-07-15 (Asia/Kathmandu)

## Result

The NeoGiga frontend and admin design were not changed. No new JLCPCB import was run, and this report-writing task changes documentation only. A separate additive working-branch change is centralizing imported-product publication eligibility in backend query logic; the current diff does not change route definitions, Blade templates, design assets, database records or stored media. That backend gate passed local PostgreSQL tests but must still be deployed and validated with live canaries before any production visibility result is claimed.

## Existing UI retained

- The shared NeoGiga frontend theme remains the established `frontend.layout` implementation.
- Global, Nepal and India editions retain their separate host-aware canonical/SEO behavior.
- Existing product, category, brand, manufacturer, RFQ, seller and search workflows remain in place.
- Existing `/admin/imports/jlcpcb` review UI and its permission-gated approve, reject, publish and search-rebuild actions remain authoritative.
- Imported JLCPCB records retain their existing visibility/review state; this documentation pass did not publish or hide anything.
- The NeoGiga placeholder remains the historical JLCPCB media strategy where licensed product media is unavailable.
- The working-branch `ProductPublicationGate` reuses the existing product/source approval fields for public query eligibility rather than creating new UI or catalog tables. Its local PostgreSQL tests passed; it is not deployed.
- A fresh read-only count and the successful isolated restore of `/home/neogiga/backups/jlcpcb-existing-data-20260714_181929` provide data-preservation evidence without changing UI state. The isolated temporary database was dropped afterward.

## Latest prior visual/live evidence

The 2026-07-14 independent production verification reported that Global, Nepal and India rendered the shared design with correct host-specific titles/canonicals, Bangladesh remained noindex, the selected product and same-origin real media returned 200, the admin login form worked with noindex, and branded WordPress apex systems were untouched.

Those canaries are the latest retained evidence. No fresh browser session was claimed for this 2026-07-15 document-only task.

## PR #15 rejection rationale

PR #15 (`origin/pr-15`, audited tip `a0d960e`) was rejected for this integration path because it is not a narrow existing-data integration:

- It adds a parallel `canonical_products` application model and related product UI/data structures rather than reusing the populated `products` catalog.
- It adds new brand controller/views/routes while the repository already has governed brand pages, creating route/design duplication risk.
- It introduces broad email, currency, notification and marketplace changes unrelated to preserving/importing JLCPCB data.
- It edits established migrations rather than adding isolated compatibility migrations.
- Its rollback names include existing product image/document/relationship tables, so a rollback could drop live tables that its guarded `up()` did not create.
- It contains no reconciled backfill/migration plan for the 69,880 current source-linked products.

Accepting that change set would risk split catalog ownership and unreviewed design/data behavior, contrary to the upgrade-only and no-data-loss rules.

## UI integrity gates for any future importer change

- Reuse the existing admin import-review route and component hierarchy.
- Keep all imports hidden/noindex until approved.
- Verify desktop/mobile product, catalog, brand, category and admin views.
- Preserve same-origin CSP-compatible media behavior.
- Confirm global/regional canonical, hreflang, robots and currency labeling.
- Do not claim local stock, price or delivery without verified price/inventory records.
- Compare screenshots and DOM behavior before and after deployment.

UI preservation status: **design/templates/routes/assets preserved**. Local PostgreSQL verification for the backend publication gate passed; deployment and fresh live UI/browser canaries remain pending.
