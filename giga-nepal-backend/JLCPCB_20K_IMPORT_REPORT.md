# JLCPCB 20K Import Report

Date: 2026-07-11

Environment: NeoGiga production, `/home/neogiga/laravel/current`

Source: `jlcpcb_parts_database`

## Preflight

- Production PostgreSQL connection verified.
- Queue health verified: 0 pending jobs before import.
- Disk space verified: about 42 GB free before import.
- Live ETL test suite passed: 42 tests.
- Dry-run passed for 20,000 rows:
  - Rows processed: 20,000
  - Rows skipped: 0
  - Unknown categories: 300
  - Records without datasheet: 440
  - Records without package: 0

## Backup

- Backup file: `/home/neogiga/backups/neogiga_pre_jlcpcb_20k_20260711T040105Z.dump`
- Format: PostgreSQL custom dump
- Size: 2.7 MB

## Import Command

```bash
tools/jlcpcb_etl/.venv/bin/python -m tools.jlcpcb_etl.cli \
  --target neogiga \
  --publish \
  --pilot \
  --scale-import \
  --limit 20000 \
  --batch-size 5000 \
  --laravel-base-path /home/neogiga/laravel/current \
  --log-level INFO
```

## Result

- Import batch: `e6dd7bf6-19ff-4101-99e6-f60b9e238575`
- Mode: `neogiga_scale_hidden_pending`
- Rows read: 20,000
- Products inserted: 18,947
- Products updated: 1,053
- Brands inserted: 238
- Categories inserted: 166
- Source links created: 18,947
- Source links updated: 1,000
- Offers created: 18,947
- Offers updated: 1,000
- Skipped rows: 53

The 53 skipped rows were canonical duplicate conflicts where multiple source part IDs resolved to an already-linked NeoGiga product for the same source. No duplicate product records were created for those rows.

## Post-Import Counts

- Total products: 19,948
- Total brands: 274
- Total categories: 428
- JLCPCB source links: 19,947
- JLCPCB public products: 24
- JLCPCB hidden products: 19,923

Review status:

- Approved: 36
- Pending review: 300
- Source imported pending approval: 19,611

Visibility status:

- Approved/public: 24
- Approved/hidden: 12
- Pending review/hidden: 300
- Source imported pending approval/hidden: 19,611

## Localization SEO

- Product localized SEO metadata: 19,946 imported products
- Product noindex SEO metadata: 19,946 imported products
- New localized brand SEO metadata: 238 brands
- New localized category SEO metadata: 166 categories

Localized metadata includes Global, India, and Nepal entries for SEO title, description, keywords, locale, country/currency context, and canonical path hints. Imported products remain `noindex,nofollow` while hidden or pending review.

## Public Safety

- Public product count stayed at 24 imported products.
- Hidden imported products stayed hidden from public listing/detail/sitemap surfaces.
- Sitemap still returns 25 public product URLs only.
- Health endpoint returned 200 after import.
- Product listing returned 200 after import.

## Known Issues

- The import process exited non-zero after writing because 53 adapter rows were skipped. Data was committed and batch status is completed.
- Skipped rows are logged in `catalog_import_errors` and `tools/jlcpcb_etl/output/canonical_adapter_report.json`.
- `product_specs` remained unchanged because this adapter writes available attributes into product `attributes` and search keywords; structured spec-table expansion is still a later phase.

## Rollback

Preferred rollback point:

```bash
pg_restore --clean --if-exists --no-owner --no-acl \
  --dbname "$DATABASE_URL" \
  /home/neogiga/backups/neogiga_pre_jlcpcb_20k_20260711T040105Z.dump
```

Source-scoped cleanup can also be planned with:

```bash
tools/jlcpcb_etl/.venv/bin/python -m tools.jlcpcb_etl.cli \
  --target neogiga \
  --rollback-batch e6dd7bf6-19ff-4101-99e6-f60b9e238575 \
  --laravel-base-path /home/neogiga/laravel/current
```

Do not execute rollback without a fresh backup and approval.
