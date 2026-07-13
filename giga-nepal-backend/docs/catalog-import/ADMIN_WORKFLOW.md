# Admin Workflow

The protected API surface is under `/api/v1/admin/catalog-ingestion` and uses the existing fail-closed `admin.token` middleware.

The server-rendered admin console is at `/admin/catalog-ingestion` and uses the existing `admin.web` session guard. It provides supplier policy audit, documented policy decisions, a normalized quotation CSV staging form, import-run counters, quality-aware review tasks, and task resolution. It has no product publication, price, stock, or checkout action.

- `GET /sources`: supplier compliance configuration and sync state.
- `POST /sources/{supplier}/audit`: read robots and create/update a pending-manual-review policy record.
- `PATCH /sources/{supplier}`: update policy state. Import cannot be enabled unless status is `approved`.
- `GET /runs`: review counters and failures.
- `GET /review-tasks`: review missing MPNs, category mapping, duplicates, and source-product records.

## Supplier quotation CSV staging

`POST /admin/catalog-ingestion/stage-document` accepts a normalized CSV up to 50 MB from an authenticated administrator. The CSV must contain `supplier_sku`, `product_name`, `source_name`, and `source_file`; prices, category hints, source URLs, and labelled raw specifications are optional.

- Uploaded files are stored on the private `local` disk under `catalog/staging/uploads`.
- The source is recorded as `supplier_document` and remains `pending_manual_review`; supplier crawling and media downloads stay disabled.
- New canonical products are hidden and `pending_review`. A supplier quote price is saved only in `supplier_products.source_price` as source provenance.
- The workflow does not create marketplace prices, inventory rows, media assets, search documents, or published products.
- Unknown manufacturer/brand and MPN values are never guessed. They create review tasks instead of automatic canonical matches.
- The form offers a dry run, which validates the CSV and writes an operational report without database rows.

## Identity verification

For a review task with a staged product, the `Identity` drawer accepts a reviewer-verified manufacturer and MPN with an evidence note.

- It updates only the hidden staged product and supplier provenance record.
- A verified manufacturer becomes an inactive brand record only when it does not already exist.
- A matching manufacturer plus normalized MPN on another product creates a `possible_canonical_duplicate` task. It does not merge records or redirect URLs.
- Otherwise it creates a `supplier_product_review` task for the remaining editorial and approval decision.
- Neither path changes publication, search indexing, regional price overlays, inventory, media, checkout, or supplier crawling settings.

## Category mapping review

The Category Mapping Review panel lists pending source-category paths and lets an administrator approve a mapping to an existing active NeoGiga category or defer it.

- Approval records the selected canonical category on the source mapping record and assigns it only to supplier-linked products that currently have no category.
- Existing manual product category assignments are preserved and never overwritten by a source mapping.
- New supplier rows retain an approved source mapping for subsequent review; the importer does not reset it to pending.
- Mapping a category does not approve or publish products, rebuild search, assign media, change stock, or create marketplace prices.

Publication is intentionally outside this surface and remains governed by the existing product approval controls.
