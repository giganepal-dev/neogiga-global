# NeoGiga TODO

## P0 - Release integrity

- [ ] Review and merge `pcb-usable-portal` into the intended release branch without overwriting current `main` work.
- [ ] Run staged migration previews and backups before applying branch migrations in production.
- [ ] Verify the deployed release SHA, route cache, config cache, view cache, queue health, and `neogiga:smoke` after merge.
- [ ] Perform an authenticated end-to-end BOM upload and confirm import ownership, matching, and RFQ/quote handoff.

## P1 - Catalog and commerce

- [ ] Establish a formal image license/provenance review for the 5,734 imported local-media matches.
- [ ] Increase product image coverage using deterministic source mappings; keep placeholders where a match is ambiguous.
- [ ] Complete B2B/customer onboarding and validate cart, checkout, tax, delivery-zone, seller, and regional-stock paths end to end.
- [ ] Review supplier ingestion quality thresholds, approval workflow, and canonical MPN conflict handling.
- [ ] Apply and verify the PCB, supplier-ingestion, catalog-provenance, and storefront-brand migrations on the final release branch.

## P2 - Quality and operations

- [ ] Run the complete Laravel test suite and add regression coverage for any uncovered header/footer, media-import, and BOM failure paths.
- [ ] Complete tablet, keyboard, screen-reader, accessibility, performance, SEO, sitemap, and structured-data audits.
- [ ] Confirm admin modules expose appropriate permissions, audit trails, destructive-action confirmations, and operational error states.
- [ ] Define retention and cleanup policy for uploaded BOMs, staged supplier imports, and copied image-library files.

## Done in this session

- [x] Route the public BOM button to the existing functional BOM upload workflow.
- [x] Move public marketplace switching to the footer and simplify header storefront labels.
- [x] Improve product responsive layout and product image rendering.
- [x] Import deterministic local-library product/category media with production backups and provenance data.
