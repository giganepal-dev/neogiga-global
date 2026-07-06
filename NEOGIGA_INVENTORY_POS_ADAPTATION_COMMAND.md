# NeoGiga Inventory POS Adaptation Command

Implement the next inventory/POS adaptation phase using UltimatePOS and Smart POS SaaS as reference only.

Rules:
- Audit existing NeoGiga inventory, warehouse, vendor, marketplace, POS, product, and order tables first.
- Do not delete, truncate, rebuild, or overwrite existing data.
- Create backup before migrations.
- Add only incremental migrations.
- Update `CHANGELOG.md`.

Implement:
- Country, marketplace, region, city, warehouse, branch/store, and vendor inventory dimensions.
- Append-only stock movement ledger.
- POS stock deduction and reversal support.
- Stock transfer workflow: draft, shipped, received, cancelled.
- Reserved, damaged, incoming, available, and on-hand quantities.
- Low-stock alert API and admin UI.
- Barcode/QR lookup endpoint for POS.
- Supplier module and purchase-order receiving.
- Admin inventory dashboard and POS modal/screen integration.

Reference:
- UltimatePOS root: `/tmp/neogiga-reference-rescan/ultimatepos/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1`
- Smart POS root: `/tmp/neogiga-reference-rescan/smartpos-221/dist/pos-saas`

Verification:
- Route cache passes.
- Public read endpoints remain stable.
- Mutating inventory/POS endpoints require auth/admin token.
- Service-level tests prove stock adjustment, transfer, reservation, POS sale deduction, refund/reversal, and purchase receiving.

