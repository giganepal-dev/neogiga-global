# NeoGiga POS Adaptation Command

Implement NeoGiga POS phase using UltimatePOS and Smart POS SaaS as reference only.

Build:
- POS modal/screen for product search, barcode lookup, cart, discounts, taxes, customer, payment method, and receipt.
- Session/register flow: open, cash-in, cash-out, close, denomination count.
- Sale flow: draft cart, completed sale, payment, receipt, inventory deduction.
- Refund/return flow: refund record, payment reversal placeholder, stock return movement.
- Receipt template settings and printer profile.
- Offline-safe idempotency key for sale submission.

Constraints:
- Do not copy reference source code.
- Do not enable real payment provider credentials.
- Do not alter historical stock or payment records.
- Add incremental migrations only and update `CHANGELOG.md`.

Verification:
- POS product search works.
- Sale deducts stock exactly once.
- Duplicate idempotency key does not double-deduct.
- Refund restores stock according to approved return quantity.
- Session close totals reconcile payments and cash movement rows.

