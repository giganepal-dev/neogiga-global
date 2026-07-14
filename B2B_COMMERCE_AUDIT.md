# B2B Commerce Audit

Generated: 2026-07-13

## Current State

- RFQ intake, RFQ items, BOM import, BOM-to-RFQ flow, seller offers, regional pricing, cart, checkout, payment allowlist, and reviews exist.
- Product detail pages lead with RFQ, AI engineer CTA, seller offers, stock, and documents.
- Admin token permission middleware now protects settings writes.

## Gaps

- Account type onboarding for buyer, engineer, company, seller, distributor, school, and repair center needs unified UX.
- Quantity price tiers and MOQ presentation need stronger integration.
- BOM/RFQ workflows should consume normalized manufacturer and MPN identity.

## Next Fixes

1. Use `manufacturer_id` and normalized MPN in BOM matching.
2. Add quantity tier display to product detail.
3. Add account-type selection and commerce permissions audit.
