# 7 — Customer Feature Gap Report (2026-07-12)

NeoGiga today: anonymous public storefront (locale-prefixed), session cart + checkout LIVE, product
reviews LIVE, support tickets LIVE, RFQ/BOM/PCB intake LIVE (this program), API-token customer auth
(API only), password-reset web routes present. **No customer web login/registration**, which is the
root gap gating most of this report.

| Capability | State | Notes / recommendation |
|---|---|---|
| Email/phone registration + OTP + verification | MISSING (web) / PARTIAL (infra) | `otp` rate limiter already defined in AppServiceProvider; users table + password reset exist. Build session auth (login/register/verify) — P0 of Release 3 |
| Password recovery | EXISTING | `/forgot-password` + named `password.reset` routes |
| 2FA optional | MISSING | TOTP secret column + challenge (post-auth) |
| Social login | MISSING | Socialite adapters, show only configured providers (mission rule; header already follows it) |
| Session management / account deletion / consent logs | MISSING | standard tables; deletion = soft-anonymize workflow |
| Product browsing / search / filters | EXISTING | storefront + catalog search live; typed-attribute filters follow R1 attribute engine |
| Compare / wishlist / recently viewed / save-for-later | MISSING | session+user hybrid tables; header icons already reserved |
| Cart / checkout | EXISTING | session cart, server-side pricing, thank-you page, tests |
| Add to BOM / RFQ | EXISTING | `/bom`, `/rfq`, `/pcb/quote` intake + authed BOM imports API (add-to-cart from BOM on branch) |
| Order tracking | PARTIAL | tracking fields + admin update exist; customer-facing order page needs auth (R3) |
| Reviews / ratings | EXISTING (with moderation + verified flow) |
| Product Q&A | MISSING | mirror review moderation pipeline |
| Seller chat / AI assistance | PARTIAL | support tickets + AI commerce pages exist; buyer↔seller conversations in chat workstream (R3) |
| Reorder / invoice download | MISSING (needs customer auth + PDF engine) |
| Return/refund request | MISSING (customer side of R1 refund workflow) |
| Address book / default address / delivery instructions | MISSING | `customer_addresses` table; checkout currently inline-address |
| Marketplace-valid payment methods only | PARTIAL | marketplace context exists. Checkout must validate the submitted payment method server-side against the marketplace allowlist and reject unsupported/tampered methods even if the UI hides them. Add tests for disabled gateways, wrong-marketplace methods, and client payload tampering when gateways land |
| Reward points / loyalty | MISSING | ledger design per mission (earn/multipliers/expiry/redemption/reversal); reuse affiliate CommissionLedgerEntry shape; B2B exclusion via b2b_account check |
| Localized storefront / multi-language | PARTIAL | locale-prefix routing + hreflang live; content translation tables missing (R4) |

**Sequence (Release 3):** customer session auth (+OTP/verification) → account area (orders,
addresses, invoices) → wishlist/compare → return-request UI → loyalty ledger → chat → Q&A.
