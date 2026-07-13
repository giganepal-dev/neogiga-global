# Changelog

## 2026-07-13

### Added

- Localized public BOM uploader at `/en/bom-imports`, backed by the existing BOM import service.
- Authenticated and throttled BOM submission flow for uploaded CSV/TSV/TXT files and pasted BOM content.
- Guarded `product-images:import-category-library` console workflow for deterministic catalog/category media matching.
- Feature coverage for the public BOM uploader.

### Changed

- Public header now exposes the established BOM uploader and PCB workflow beside search.
- Storefront context is represented with flags/currency in the header; marketplace switching is available in the footer.
- Product detail contrast, image fallback behavior, and mobile layout were improved.
- Category pages can render representative category images with provenance metadata.

### Production operations

- Deployed the BOM page, public-header improvements, responsive product fixes, landing/footer selector changes, and category-matched media import with release backups.
- Imported 5,734 deterministic product media matches and 84 category images from the user-provided local library. The imported media remains subject to rights review.

## 2026-07-10 to 2026-07-13 (session branch foundation)

- Added PCB portal and lifecycle workflows.
- Added regional-commerce safeguards, catalog identity/provenance controls, delivery-zone configuration, supplier catalog ingestion/review, and storefront-brand configuration.
- Added related migrations, focused feature tests, and operational admin workflows on `pcb-usable-portal`.
