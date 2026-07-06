# NeoGiga Digikash Payment Adaptation Command

Implement a Digikash-style payment, wallet, and affiliate foundation in NeoGiga without copying Digikash code.

Rules:
- Do not use live payment credentials.
- Do not copy `.env`, API keys, private keys, webhook secrets, or commercial source files.
- Audit existing NeoGiga orders, users, vendors, payments, refunds, and POS tables first.
- Add only incremental migrations.
- Use append-only ledger patterns; never mutate historical transaction amounts.
- Update `CHANGELOG.md`.

Build:
- Payment provider abstraction: `PaymentProviderInterface`, provider registry, provider settings, webhook verifier.
- Gateway placeholders: eSewa, Khalti, Fonepay, Stripe, PayPal, bank transfer, cash on delivery.
- Wallet/store-credit accounts by owner type: customer, vendor, marketplace, affiliate.
- Transaction ledger with idempotency key, source, status, currency, amount_minor, fee_minor, net_minor, provider reference, metadata.
- Order payment status synchronization: pending, authorized, paid, partial, failed, refunded, partially_refunded, cancelled.
- Vendor payout tracking: payable balance, hold period, payout batch, payout status.
- Refund handling: refund request, approval, provider refund placeholder, wallet reversal.
- Affiliate/referral tracking: referral code, attribution, commission rule, earned commission, payout status.
- Multi-currency support with currency code, exchange rate snapshot, country, and precision.

Verification:
- Route cache passes.
- Payment APIs reject unauthenticated unsafe calls.
- Test provider can simulate success/failure/refund without external network credentials.
- Ledger balances reconcile from transaction rows.

