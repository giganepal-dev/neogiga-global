# Digikash Payment And Affiliate Reference

Source root: `/tmp/neogiga-reference-rescan/digikash-20/core-v2.0`

## Useful Files

Routes:
- `routes/admin.php`
- `routes/api.php`
- `routes/web.php`
- `routes/auth.php`

Core payment/wallet schema:
- `database/migrations/2024_11_12_040813_create_wallets_table.php`
- `database/migrations/2024_11_16_150322_create_transactions_table.php`
- `database/migrations/2024_08_11_083809_create_payment_gateways_table.php`
- `database/migrations/2024_08_11_090520_create_deposit_methods_table.php`
- `database/migrations/2026_05_13_040940_create_merchant_deposit_methods_table.php`
- `database/migrations/2026_05_13_041000_normalize_deposit_methods_schema.php`
- `database/migrations/2026_05_01_000000_add_stripe_payout_support.php`

Affiliate/agent/referral schema:
- `database/migrations/2025_01_07_150435_create_referrals_table.php`
- `database/migrations/2025_01_08_002400_create_rewards_table.php`
- `database/migrations/2026_05_01_100000_create_agents_table.php`
- `database/migrations/2026_05_10_164242_create_agent_commission_rules_table.php`
- `database/migrations/2026_05_10_172020_create_agent_commission_rule_assignments_table.php`
- `database/migrations/2026_05_10_164243_create_agent_operations_table.php`
- `database/migrations/2026_05_11_154051_create_agent_currencies_table.php`

Models/services/controllers:
- `app/Models/Wallet.php`
- `app/Models/Transaction.php`
- `app/Models/PaymentGateway.php`
- `app/Models/PaymentLink.php`
- `app/Models/Agent.php`
- `app/Services/WalletService.php`
- `app/Services/PaymentService.php`
- `app/Services/AgentService.php`
- `app/Services/AgentCommissionRuleService.php`
- `app/Services/TransactionNotifierService.php`
- `app/Http/Controllers/Frontend/DepositController.php`
- `app/Http/Controllers/Frontend/WithdrawController.php`
- `app/Http/Controllers/Frontend/MerchantPaymentReceiveController.php`
- `app/Http/Controllers/Frontend/ReferralController.php`
- `app/Http/Controllers/Webhook/BitnobWebhookController.php`

## What Can Be Adapted

- Wallet account per user/vendor/customer with currency-aware balances.
- Append-only transaction ledger with payment/deposit/withdraw/refund status.
- Payment gateway registry and deposit/withdraw method configuration.
- Merchant payment links and QR payment flows.
- Agent/referral commission rules and assignments.
- Notification after transaction status changes.

## What Must Be Rewritten

- Gateway clients, webhook verification, and payout code must be rewritten for NeoGiga.
- All secrets, `.env`, API keys, and demo credentials must be ignored.
- Monetary calculations need integer minor units or fixed decimal safeguards.
- Multi-country rules need explicit country, currency, tax, and compliance fields.

## Risks

- High commercial license risk; use as reference-only.
- Payment code is high-risk and must have idempotency, signature verification, immutable ledger entries, and audit logs.
- Payout/refund workflows must be manually reviewed before enabling real money.

