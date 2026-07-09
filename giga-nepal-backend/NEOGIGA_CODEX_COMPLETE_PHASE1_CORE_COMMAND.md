# NeoGiga Codex Complete Phase 1 Core Command

Goal: complete core marketplace foundation for launch readiness.

Evidence:
- Marketplace schema/routes exist.
- Live counts show `products:1`, `vendors:0`, `orders:0`, `payments:0`, `product_brands:0`.
- `ProductAdminController`, `VendorAdminController`, `MarketplaceAdminController` are stubs.

Tasks:
1. Complete product/brand/vendor admin APIs.
2. Add product approval workflow endpoints and admin UI actions.
3. Complete vendor approval dashboard and document review actions.
4. Harden cart/checkout server-side totals and stock validation.
5. Ensure pending payment/order placeholders are explicit and safe.
6. Add seed/import path for initial brands/products/vendors using source metadata.

Rules:
- Preserve existing IoT/geography/marketplace data.
- No destructive migrations.
- No frontend price trust.
- Update `CHANGELOG.md`.

Verification:
- Route cache passes.
- Product/vendor admin APIs require admin token/auth.
- Checkout creates pending order with server-calculated totals.
- Tests for product, vendor, cart, checkout.

Deliverable:
- `NEOGIGA_PHASE1_CORE_IMPLEMENTATION_REPORT.md`

