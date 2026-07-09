# NeoGiga Codex Complete Inventory POS Command

Goal: complete inventory/POS safety and cashier-readiness.

Evidence:
- Inventory/POS services and tables exist.
- POS sale and payment work.
- POS refund returns 501.
- Barcode/QR lookup and shift close workflows are incomplete.

Tasks:
1. Add idempotency checks to stock and POS mutations.
2. Implement POS refund with stock reversal and payment status update.
3. Implement cash movement and shift closing service.
4. Add barcode/QR product lookup endpoint.
5. Add stock transfer status lifecycle.
6. Add tests for race/idempotency/refund/transfer.

Rules:
- Stock ledger append-only.
- No direct stock mutation outside services.
- No destructive data changes.

Verification:
- Duplicate sale key does not double-deduct.
- Refund restores stock only once.
- Low-stock API works.
- Route cache and tests pass.

Deliverable:
- `NEOGIGA_INVENTORY_POS_IMPLEMENTATION_REPORT.md`

