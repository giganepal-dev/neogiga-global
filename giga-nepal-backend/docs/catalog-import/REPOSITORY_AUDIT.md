# Repository Audit: Supplier Catalogue Ingestion

Date: 2026-07-12

## Existing Architecture Reused

- Laravel 11.31 on PHP 8.2; production uses PostgreSQL and the database queue driver.
- `products` is the canonical catalogue table. It already has NeoGiga SKU, MPN, brand, category, status, source metadata, marketplace visibility, and a normalized brand/MPN PostgreSQL index.
- `catalog_sources`, `catalog_import_batches`, `catalog_product_sources`, `catalog_import_errors`, and `catalog_distributor_offers` provide existing source provenance and import-batch conventions from the JLCPCB integration.
- `product_specs`, `product_specifications`, `product_images`, `product_datasheets`, `product_compatibility`, product brands/categories, marketplace prices, and inventory remain the authoritative product-extension modules.
- The public catalogue and sitemap are already guarded by product status and visibility. Pending imports cannot become public through this ingestion layer.
- Admin APIs use the existing fail-closed `admin.token` middleware. Existing product approval controls are retained.

## Adaptation Decisions

- Extend `catalog_sources` for compliance configuration instead of creating a competing supplier registry.
- Keep ERP procurement `suppliers` separate from public catalogue-source identity. Supplier catalogue records reference `catalog_sources` and can optionally reference an ERP supplier later.
- Add supplier-specific source listings, import runs/items/checkpoints, review tasks, normalized definitions, compatibility platforms, and asset provenance as additive tables.
- Use `products.status=pending`, `approval_status=pending_review` (when available), and `visibility_status=hidden` (when available) for every newly persisted supplier item.
- Never write supplier price or stock into NeoGiga marketplace price/inventory tables. Supplier observations remain in `supplier_products` only.
- Media downloads are disabled by default. When enabled, source URLs and rights are recorded; unlicensed media is never attached to public product images.

## Identified Gaps

- The legacy import-export API is intentionally placeholder-only and is not a safe supplier pipeline.
- Existing provenance has batch-level tracking but no resumable per-item checkpoint, supplier policy audit, normalized specification definition, or supplier-category mapping workflow.
- Existing product compatibility is product-to-product. Platform compatibility requires a separate normalized platform table.
- No approved redistribution permissions for Adafruit, Waveshare, or OKYSTAR are stored in this repository. Imports therefore remain disabled until an administrator records a compliant source policy.

## Risks And Controls

- Supplier terms and robots policies can change: each live discovery requires a fresh audit and stores the observed policy outcome.
- Product prose and media may be copyrighted: descriptions are not copied unless the configured source policy explicitly permits reuse; media is disabled by default.
- Similar product names are unsafe identities: matching is deterministic (GTIN, manufacturer+MPN, supplier source ID/SKU, canonical URL) and otherwise becomes a review task.
- Large sitemaps require bounded streaming and checkpoints: discovery/import limits, per-domain request limits, conditional fetch metadata, failure logging, and idempotency keys are mandatory.

## Implementation Plan

1. Add reversible ingestion schema and conservative configuration defaults.
2. Add shared audit, discovery, normalization, matching, persistence, reporting, and command services.
3. Register Adafruit, Waveshare, and OKYSTAR adapters with their domains but disabled policies.
4. Add admin-token-protected source/run/review APIs.
5. Cover normalization, compliance gating, dry-run, deterministic matching, idempotency, and reports with fixtures only.
6. Run local migrations/tests and supplier command dry-runs. Do not run a live catalogue import or deploy without explicit source rights and deployment authorization.
