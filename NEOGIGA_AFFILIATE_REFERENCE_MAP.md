# NeoGiga Affiliate Reference Map

Best affiliate source: Digikash.
Secondary source: Salesy SaaS CRM.

## Digikash Files

Root: `/tmp/neogiga-reference-rescan/digikash-20/core-v2.0`

Schema:
- `database/migrations/2025_01_07_150435_create_referrals_table.php`
- `database/migrations/2025_01_08_002400_create_rewards_table.php`
- `database/migrations/2026_05_01_100000_create_agents_table.php`
- `database/migrations/2026_05_10_164242_create_agent_commission_rules_table.php`
- `database/migrations/2026_05_10_172020_create_agent_commission_rule_assignments_table.php`
- `database/migrations/2026_05_10_164243_create_agent_operations_table.php`
- `database/migrations/2026_05_11_154051_create_agent_currencies_table.php`

Code:
- `app/Models/Agent.php`
- `app/Services/AgentService.php`
- `app/Services/AgentCommissionRuleService.php`
- `app/Http/Controllers/Frontend/ReferralController.php`

## Salesy Files

Root: `/tmp/neogiga-reference-rescan/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file`

- `database/migrations/2025_06_20_044143_create_referral_settings_table.php`
- `database/migrations/2025_06_20_044158_create_referrals_table.php`
- `database/migrations/2025_06_20_044206_create_payout_requests_table.php`
- `app/Models/Referral.php`
- `app/Models/ReferralSetting.php`
- `app/Models/PayoutRequest.php`
- `app/Http/Controllers/ReferralController.php`

## NeoGiga Plan

- Track referral attribution by customer/vendor/affiliate with first-touch and last-touch metadata.
- Commission rules by country, marketplace, product category, vendor, and campaign.
- Commission ledger with pending, approved, rejected, payable, paid, reversed states.
- Payout requests and payout batches.
- Fraud controls: self-referral block, same payment instrument checks, duplicate device/IP signals, manual review thresholds.

