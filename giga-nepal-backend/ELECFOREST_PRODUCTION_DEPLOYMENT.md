# ElecForest Production Deployment

## Outcome

NeoGiga release `/home/neogiga/laravel/releases/20260714-103845-elecforest` was deployed to production on 2026-07-14. The existing application, database, design, routes and unrelated modules were preserved. Apache serves the immutable release path and `/home/neogiga/laravel/current` resolves to the same release.

All 3,177 sellable rows from the 3,178-line ElecForest source are linked to 3,177 hidden draft products. The remaining row is a collection page and was intentionally rejected. Strict publication published zero and blocked all 3,177 drafts pending identity, taxonomy and media-rights review.

## Recovery points

- Pre-deployment backup: `/home/neogiga/backups/elecforest-20260714_103618`
- Pre-deployment PostgreSQL dump SHA-256: `6e36910cf883c520edf894a23ddc6d98a688d35e3cbbb3de9060caee2938f2a7`
- Pre-deployment code archive SHA-256: `8a4c376cc2f7cc934e882ce5a5fdc0d394674847ca24481b90a97559f6163238`
- Pre-URL-widen PostgreSQL dump: `/home/neogiga/backups/elecforest-20260714_103618/pre-url-widen/neogiga-before-url-widen.dump`
- Pre-URL-widen dump SHA-256: `8fc09234a008f080221b1fdc01719209ff7b319ff04b9664b1419c245050d6ff`
- Both dumps are PostgreSQL custom format and passed `pg_restore --list` validation.

## Source and schema

- Source: `storage/app/imports/elecforest-products.jsonl`
- Source SHA-256: `14a04e1001d9d02e787150c33ff3c6970677ed0332b0448475fce6f44b26409c`
- Source audit: 3,178 valid rows, zero malformed rows and zero invalid UTF-8 rows.
- The additive ElecForest schema migration and the production compatibility URL-widen migration are applied.
- The compatibility migration changed only `products.source_url` and `products.source_page_url` from `varchar(255)` to `text`; rollback is deliberately non-destructive to prevent provenance truncation.
- Production runs Laravel 12.63.0 on PHP 8.4.23 with 909 registered routes and 191 applied migrations.

## Final catalog validation

- Total products: 73,058
- ElecForest source/product links: 3,177 / 3,177
- Hidden draft products: 3,177
- Public ElecForest products: 0
- Product SEO rows: 3,177
- Content versions: 3,177
- Isolated supplier offers: 3,177
- Warehouse, marketplace, vendor and country-price rows created: 0
- Publication gate: 0 published, 3,177 blocked

## Media validation

- Source image assets downloaded: 9,801 / 9,801
- Failed image assets: 0
- Unique inactive product-image rows after checksum deduplication: 9,777
- Active ElecForest images: 0
- Image rows with complete derivative metadata: 9,777 / 9,777
- Derivative metadata references: 63,054
- Stored checksum-addressed files: 72,181 files, 1,691,788,910 bytes
- All source media remains `pending_review` and is not public until rights approval.

## Operational verification

- Catalog import, media and derivative queues: 0 remaining jobs
- Laravel failed-job count: unchanged at the five pre-existing UUIDs; no ElecForest failed job was added
- Apache, the NeoGiga default queue service and PHP 8.4 FPM are active
- Persistent low-priority workers cover campaign preparation/marketing, transactional/webhooks and customer spreadsheet imports; production marketing and transactional sending remain disabled/test-gated
- Apache configuration passes `Syntax OK`
- `/health`, `/en`, `/en/products`, `/en/categories`, `/sitemap.xml`, admin login, logo and placeholder assets return successful responses
- `/admin/imports/elecforest` redirects unauthenticated requests to admin login on both main and admin hosts
- Public marketplace API returns 200 and the protected wallet API returns 401 without credentials
- Focused ElecForest verification passes 20 tests with 114 assertions; the pre-deployment full suite passed 169 tests with 11 intentional skips and 709 assertions

## Safety state

No legacy product, category, brand, manufacturer, customer, route or media record was deleted. No existing price or inventory was overwritten. Marketing and transactional email delivery remains governed by the disabled/test/approval gates configured in the release; the deployment sent no campaign or customer email.
