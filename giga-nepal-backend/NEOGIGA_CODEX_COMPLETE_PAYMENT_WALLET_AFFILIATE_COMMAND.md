# NeoGiga Codex Complete Payment Wallet Affiliate Command

Goal: build safe payment/wallet/affiliate foundation.

Evidence:
- `payments` table exists.
- Affiliate tables/services/routes exist.
- No wallet/store-credit ledger verified.
- Payment providers/webhooks are not complete.

Tasks:
1. Add provider abstraction with test provider only.
2. Add wallet/store-credit accounts and append-only ledger.
3. Add payment transactions/events with idempotency.
4. Add webhook verifier interface and disabled placeholders for real providers.
5. Add vendor payout tracking.
6. Complete affiliate commission approval/reversal and fraud flags.

Rules:
- No live credentials.
- No automatic payouts.
- Amounts use minor units or fixed precision.
- All money mutations in DB transactions.

Verification:
- Test provider success/failure/refund tests.
- Ledger reconciliation test.
- Webhooks reject unsigned requests.

Deliverable:
- `NEOGIGA_PAYMENT_WALLET_AFFILIATE_IMPLEMENTATION_REPORT.md`

