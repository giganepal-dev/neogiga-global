# NeoGiga Regional Platform and Catalog Release Audit

Date: 2026-07-14 (Asia/Kathmandu)
Scope: global Laravel platform, configured regional editions, branded Nepal/India sites, admin operations, regional SEO, and the ElecForest draft-catalog release.
Change policy: upgrade-only. No existing product, category, route, module, migration, media file, order, customer, email configuration, or legacy website was deleted or replaced.

## Executive result

- The global site and eight active regional hosts already run the same Laravel platform and product master.
- Regional marketplace context and SEO are now rendered separately by domain: title, description, canonical, robots, hreflang, currency context and structured data are edition-aware.
- The shared NeoGiga frontend now uses live categories, nested-taxonomy links, products, exact product-linked images, brands and functional workflows instead of a hard-coded India preview.
- The existing admin panel now exposes linked KPI/API/email-provider status and functional destinations for the remaining static catalog, SEO, media, access-control, inventory and POS controls.
- A dry-run-first governed release processed the 3,177 ElecForest drafts. One exact template/sentinel row (`NG-EF-`) remains quarantined; the other 3,176 passed the source-price, schema, image-file, provenance, existing-price and existing-stock gates and were released under the exact approved plan hash.
- The release records imported USD price as cost, calculates an exact 5% USD sale price at four-decimal precision, and assigns 10,000 units per eligible product: 8,000 Shenzhen, 667 Kathmandu, 667 New Delhi and 666 Dubai.
- Supplier image checksum and image signatures are verified before activation. This establishes file integrity, not license ownership. Original rights facts and open legal/catalog review tasks are retained and disclosed.
- Branded `giganepal.com` and `neogiga.in` are still independent WordPress systems. They were not switched because a blind cutover would lose URLs, data and/or mail. Their migrations require the gated sequence in this report.

## Current regional estate

Production PostgreSQL contains 26 marketplace records. Eight are active on the shared Laravel host:

| Edition | Current Laravel host | Application | SEO state |
|---|---|---|---|
| Global | `https://neogiga.com/en` | shared Laravel | index |
| Nepal | `https://np.neogiga.com/en` | shared Laravel | index |
| India | `https://in.neogiga.com/en` | shared Laravel | index |
| Bangladesh | `https://bd.neogiga.com/en` | shared Laravel | noindex |
| Sri Lanka | `https://lk.neogiga.com/en` | shared Laravel | noindex |
| Pakistan | `https://pk.neogiga.com/en` | shared Laravel | noindex |
| Bhutan | `https://bt.neogiga.com/en` | shared Laravel | noindex |
| Australia | `https://au.neogiga.com/en` | shared Laravel | noindex |

The active hosts resolve to the same server, Apache application, release, database and APIs. Bangladesh, Sri Lanka, Pakistan, Bhutan and Australia remain intentionally non-indexable until their launch checklists are complete.

The 18 inactive marketplace records are hidden/noindex. `mv.neogiga.com`, `ae.neogiga.com`, `sa.neogiga.com` and `ca.neogiga.com` have public DNS pointing at production but do not have a complete verified TLS/vhost/activation state and must not be advertised as live editions.

Configuration/database drift found during the audit:

- Config-only marketplace prefixes: MM, SG, MY, TH, JP, KR, PL, MX and NG.
- Database-only marketplace records: LK, PK, BT, MV, OM, KW and NZ.
- Config uses UK while production uses GB.
- This drift was documented rather than silently rewriting marketplace records.

## Regional SEO verification

The shared renderer now keeps the following concerns separate per edition:

- canonical host and localized page path;
- regional title and meta description;
- configured `index,follow` or `noindex,nofollow` state;
- hreflang links only for eligible visible editions;
- marketplace WebSite and category ItemList structured data;
- current marketplace/country/currency context;
- product price currency from an explicit marketplace price row.

Important currency rule: the release creates a GLOBAL USD price only. No NPR/INR or other regional amount is fabricated without a valid FX/rate policy. A regional page may use the explicit GLOBAL USD fallback, and the UI labels it USD.

The domain resolver also supports `www` fallback to an existing apex-domain mapping. This prepares branded domains without changing DNS, primary-domain flags or canonical strategy.

The upgrade layer also closes the regional prefix-alias crawl matrix. On a dedicated regional host, any routed marketplace prefix other than `/en` receives a permanent redirect to that marketplace's branded `/en` URL, with the remaining path and query string preserved. Global-host marketplace-prefix routes remain available, and generated canonical tags point directly to the normalized regional URL rather than a redirecting alias.

## Branded regional source systems

### Nepal: `giganepal.com`

The site is currently WordPress/WooCommerce on the accessible production server and was left unchanged.

- WordPress 6.9.1
- 12,414 published products
- 292 variations
- 312 public categories
- 11 brands
- 8,522 media records
- 40,602 upload files (approximately 1.2 GB)
- 25,676 users, including 25,668 customer-role users
- 21,378 WooCommerce customer lookup rows
- 936 order-stat rows / 929 legacy order posts
- 4,280 order-item rows
- approximately 3.9 GB across 180 database tables

Full MySQL and uploads backup is technically possible. The public web vhost is currently coupled to mail/webmail/admin aliases, so those services must be separated before the document root changes.

### India: `neogiga.in`

The site is currently WordPress/WooCommerce on a separate origin for which authenticated hosting/database access is not available.

Publicly recoverable inventory:

- 12,572 products
- 308 categories
- 27 brands
- 11,863 media records
- 20 pages
- 69 RankMath child sitemaps, including 63 product sitemaps

Public APIs do not expose customers, orders or private SEO metadata. A public crawl is insufficient for a no-data-loss cutover.

India mail is coupled to the current apex origin: its MX points at the apex and SPF includes the old origin IP. Changing the apex web A record before splitting mail would endanger inbound email. Hosting/DB or authenticated WooCommerce access plus a mail/DNS migration plan is mandatory.

## Legacy URL protection

WordPress and Laravel use different public paths:

```text
WordPress: /product/{slug}/
Laravel:   /en/products/{slug}

WordPress: /product-category/{category-path}/
Laravel:   /en/categories/{slug}
```

Sample legacy slugs do not currently exist in the Laravel catalog. Therefore DNS/vhost changes are not a migration. A permanent source-to-destination URL map and tested 301 redirects are required before either branded domain can be made canonical on Laravel.

## Implemented upgrade layer

### Shared frontend

- Uses the established `frontend.layout` theme and supplied official NeoGiga icon assets.
- Loads root categories and child counts from the existing taxonomy.
- Loads publicly available products, exact active product media and explicit marketplace/global prices.
- Uses the NeoGiga logo placeholder only when verified product media is absent.
- Links category, product, brand, manufacturer, RFQ, seller, AI, LMS and warehouse workflows to registered routes.
- Shows inactive prefix editions as “Coming soon” and preserves noindex behavior.
- Keeps Nepal/India canonical and hreflang origins on the currently live NP/IN Laravel hosts; environment-overridable branded-apex cutover values are changed only after the migration gates below pass.

### Admin panel

- Dashboard KPI cards link to their real modules.
- API readiness distinguishes `/api/admin/*` and `/api/v1/admin/*` from public endpoints and includes live platform health.
- Marketing and transactional provider summaries link to encrypted SMTP/API credential settings.
- Email compose/campaign and customer spreadsheet-import workflows remain permission-gated and directly accessible.
- Category tree expansion, import navigation, SEO destinations, media filters, product details, role permissions, inventory transfers/reservations/alerts and POS refund/payment/offline-sync controls use registered routes and handlers.
- Optional dashboard table checks run before queries, avoiding PostgreSQL transaction invalidation when an optional module is absent.
- Category-tree expansion is loaded from a same-origin external JavaScript asset, so it complies with the existing `script-src 'self'` policy without weakening CSP or changing the page design.
- Permission creation and role-permission changes require a super administrator. A toggle locks and synchronizes both the legacy `roles.permissions` JSON list and `role_permissions` pivot in one transaction; authorization reads active pivot grants alongside legacy grants, and wildcard roles cannot be changed through the matrix.
- Low-stock refresh processes all qualifying inventory rows in locked `chunkById` batches and resolves an alert only when an authoritative `NOT EXISTS` check confirms that its linked stock row is no longer low; the former first-500-row reconciliation risk is removed.
- POS refund recording locks the sale row before calculating the remaining balance, compares four-decimal fixed-point units instead of floats, and requires a per-form/request intent key. Reusing the same key and payload replays safely, a different rendered form permits a legitimate identical refund, and reused keys with changed payloads or amounts above the remaining balance are rejected.

## Governed draft-catalog release

Command:

```bash
php artisan catalog:release-drafts
```

Dry run is the default and performs no writes. Apply requires all of the following from the current dry run:

- `--apply`
- exact `--expected-count`
- exact SHA-256 `--expected-plan-hash`
- verified immutable `--backup-reference`
- explicit `--acknowledge-media-publication-risk`

The release refuses to overwrite any existing GLOBAL price, inventory stock or populated canonical price/stock. Each product is locked and committed in an independent bounded transaction, and publication is the final operation inside that transaction.

The first governed production apply failed closed before the first sellable product transaction because the existing `marketplace_product_prices.source_review_status` column was 40 characters. Only the already-planned hidden template quarantine committed. A new incremental migration safely widens that legacy label to 80 characters without changing existing values; the release requires a fresh plan/hash before retry.

### Price policy

- Source currency must be USD.
- Latest imported offer must equal the imported supplier price.
- Cost price = imported source price.
- Sale/base price = cost × 1.05.
- Product price columns are widened additively from `DECIMAL(12,2)` to `DECIMAL(15,4)`.
- Marketplace price rows already support four-decimal values.
- A GLOBAL USD marketplace row stores full source and normalization provenance.

### Inventory policy

Per eligible product:

| Warehouse | Units | Share |
|---|---:|---:|
| Shenzhen central (`NG-SHENZHEN-CN`) | 8,000 | 80% |
| Kathmandu (`NG-KATHMANDU-NP`) | 667 | 6.67% |
| New Delhi (`NG-NEWDELHI-IN`) | 667 | 6.67% |
| Dubai (`NG-DUBAI-AE`) | 666 | 6.66% |
| Total | 10,000 | 100% |

The verification-only warehouse is explicitly excluded. Stock and movement rows retain release hash, backup reference, source provenance and deterministic movement idempotency keys.

### Media policy

Every activated image must have:

- an exact supplier-product relationship;
- a matching 64-character SHA-256 checksum on the source asset and product image;
- an existing local file on the configured disk;
- a recomputed matching file checksum;
- a valid supported image signature/MIME type;
- matching stored byte count and dimensions;
- an HTTPS source URL and ElecForest source identity.

The command does not overwrite `supplier_product_assets.rights_status`, source license or copyright. It appends operator publication-risk evidence while leaving the media-rights review open.

### Provenance and advisory metadata

New price, inventory metadata, media metadata and audit reports retain:

- source name, URL, source file and source page URL;
- downloaded/imported timestamps and data year;
- license note and confidence level;
- original raw and normalized values;
- source notes, last updated timestamp and an “Advisory only” disclaimer.

## Verification completed before deployment

- Complete Laravel suite: 185 passed, 1,006 assertions, 11 intentional skips.
- Catalog release suite: 3 passed, 88 assertions.
- Regional/admin focused suites passed.
- Focused safety coverage verifies permission enforcement and synchronized grants, reconciliation beyond 500 low-stock rows, fixed-point/idempotent refund limits, regional prefix normalization and CSP-compatible category-tree wiring.
- Composer validation passed.
- Laravel configuration, route and Blade caches compiled.
- Vite production build passed.
- Pint checks for every release-added PHP file and `git diff --check` passed; unrelated repository-wide legacy formatting debt was not rewritten.

## Branded-domain cutover gates

No `giganepal.com` or `neogiga.in` DNS/vhost cutover should occur until all gates are satisfied:

1. Immutable full database, uploads, server-config and DNS backups with SHA-256 readback.
2. Full product/category/brand/manufacturer/media/customer/order import reconciliation.
3. Exact legacy URL and SEO metadata map with tested permanent redirects.
4. Duplicate identity rules for overlapping regional/global records.
5. India authenticated source access and complete mail separation (MX, mail host, SPF, DKIM, DMARC and certificate).
6. Nepal mail/webmail/admin alias separation from the WordPress web vhost.
7. Final canonical decision for branded apex versus NP/IN subdomain.
8. Staging canaries, delta import/freeze, rollback rehearsal and post-switch monitoring.

Until these are complete, the safe production state is the shared Laravel platform on the active NeoGiga hosts plus untouched legacy branded source systems retained for migration and rollback.

## Production result

The governed production apply completed. The exact deployment, backup and command results below are recorded from production; final independent database reconciliation and live browser evidence are deliberately marked pending rather than inferred from local tests.

### Deployment and immutable backups

- Live release: `/home/neogiga/laravel/releases/20260714-214657-catalog-release-width-fix`
- Git commit: `70d2127`
- Full immutable backup: `/home/neogiga/backups/regional-catalog-release-20260714-212602-retry1`
  - PostgreSQL custom-format dump SHA-256: `221be4c39dade47ec85e729467eef5c58c6771d0e66bee7b07d5206e6e72a009`
  - storage archive SHA-256: `6d919f1174c23a365164d3dc0367ed19b91a960c02e6157cccb37679a86842b4`
- Frozen pre-migration PostgreSQL backup SHA-256: `f46e3ccc176cc17acba1e97770e7c53ed2b971b28971c2f46bc9f53ff6c9e229`
- Pre-width-fix PostgreSQL backup SHA-256: `e32bd6f54aa1a27a377c8b6af7c8c0ca5f51d6813ab2e923b046e18301d78dab`

No existing product, price, inventory, movement, media, customer, order or legacy-site data was deleted. The exact template/sentinel product `NG-EF-` was hidden and quarantined as planned.

### Fail-closed first attempt and compatibility fix

The first production apply stopped safely before the first sellable-product transaction committed because the legacy `marketplace_product_prices.source_review_status` column was `VARCHAR(40)`, shorter than the governed review label. Only the already-planned `NG-EF-` sentinel quarantine committed. No sellable-product price, inventory, movement or media activation from that attempt committed.

Incremental migration `2026_07_14_182000_widen_marketplace_price_review_status` widened the field to 80 characters while preserving every existing value. Its rollback intentionally does not narrow the field. A fresh dry run reproduced the same eligible count and plan hash before the governed retry.

### Completed governed apply

- Plan SHA-256: `483f0c2a1b3f292115ab7db7cd37773a7af3b852fb7f97f3897a511f75e12129`
- Eligible and released products: 3,176
- Exact source-cost-plus-5% GLOBAL USD price rows created: 3,176
- Warehouse stock rows created: 12,704
- Matching idempotent inventory movements created: 12,704
- Checksum-, signature- and local-file-verified real images activated: 9,777
- Allocation per released product: 8,000 Shenzhen, 667 Kathmandu, 667 New Delhi and 666 Dubai, totaling 10,000 units
- Private completion report: `catalog-releases/20260714-161552-101019-483f0c2a1b3f2921-completed.json`

The apply used the required backup reference and exact count/hash gates. The operator explicitly acknowledged the media-publication risk. Image-file integrity was verified, but independent media licensing was not established or claimed. The original review work remains open:

| Review class | Open tasks |
|---|---:|
| Media rights | 3,176 |
| Missing applications | 3,064 |
| Brand | 3,176 |
| Manufacturer | 3,176 |
| Taxonomy | 223 |

### Regional SEO and legacy-site state

Nepal and India canonical, hreflang, prefix-redirect and geo-recommendation output remains on the live Laravel hosts `np.neogiga.com` and `in.neogiga.com`. Canonicals do not point at the unmigrated branded WordPress apex paths. The independent `giganepal.com` and `neogiga.in` WordPress systems and their stored data remain untouched; their cutovers are still blocked by the backup, import-reconciliation, legacy-URL, mail and DNS gates above.

### Evidence still pending

The following evidence must be added after independent post-apply checks; this document does not claim those checks have completed yet:

- exact final database reconciliation totals and per-warehouse sums;
- confirmation that the post-apply dry run has zero remaining eligible products and only the quarantined sentinel draft;
- completion-report checksum/readback and final failed-job baseline comparison;
- live global, Nepal and India product-page canaries, real-image response checks, canonical/robots checks and browser screenshots;
- final public-media file-count comparison and admin/health route canaries.
