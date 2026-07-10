# LOCAL_PAYMENT_ROUTING_GUIDE

**Status: not built this cycle. Provider abstraction is real and live; marketplace-scoped routing is not.**

## What exists (pre-existing, unchanged this cycle)
`payment_providers` table: code, name, is_enabled, is_live, supported_currencies (json), config
(json), sort_order. **8 providers seeded, all disabled**: `cod`, `bank_transfer`, `wallet`,
`esewa`, `khalti`, `fonepay`, `stripe`, `paypal` — this already covers the Nepal (eSewa/Khalti/
Fonepay) and global (Stripe/PayPal) gateway list the original spec asked for by name. Admin toggle
UI exists at `/admin/payments` (`AdminCommerce::toggleProvider`) — flips `is_enabled` only, never
touches `config`/`is_live` (a deliberate safety boundary from an earlier cycle).

`payment_transaction_events` (from the payments-abstraction module) covers transaction/webhook
audit under a different name than the spec's `payment_transactions`/`payment_webhook_logs`.

## What does NOT exist
`marketplace_payment_methods` (no join table restricting which providers show per marketplace —
today `supported_currencies` is the closest proxy, but it isn't marketplace-scoped), `payment_fees`,
`payment_reconciliations`, `payment_gateway_accounts`. No provider's `config` column is encrypted
at rest (admin update strips secret-looking keys before persisting, but that's write-time
filtering, not column encryption — flagged as a pre-existing security follow-up, not something
this cycle introduced or fixed).

## Why this is deferred
Stage 3 per the release order. **No live gateway credentials exist anywhere in this project** —
consistent with the standing "do not connect live gateways yet" rule this codebase has followed
since the payments-abstraction module was first built.

## Next step
Add `marketplace_payment_methods` (marketplace_id, payment_provider_id, is_enabled) once a specific
marketplace is ready to go live with a specific gateway — do not enable providers globally by
currency match alone, since that would violate the "never leak gateway settings between countries"
requirement.
