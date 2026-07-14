# 9 — System Settings Gap Report (2026-07-12)

| Domain | State | Notes / recommendation |
|---|---|---|
| **Currency management** | EXISTING (strong) | currencies table + ExchangeRateService + provider interface + refresh command + order snapshots. Missing only: admin UI for manual override + stale-rate warning banner (P2, low). Reference is weaker — do not port |
| **Social login** | MISSING | Socialite adapters (Google/Apple/Microsoft first); providers table with **encrypted credentials** (Laravel `encrypted` cast); render only configured providers (rule already enforced in header). R3 |
| **Social media chat (WA/Messenger/Telegram)** | MISSING | adapter interfaces only; activate per-marketplace where approved + consent + audit logs. Internal NeoGiga chat first (support-ticket model → conversations). R3/R4 |
| **Business settings** (company/legal/tax IDs/logo/order numbering/timezone/maintenance/feature flags) | PARTIAL | pieces exist across config + marketplaces table (currency/timezone/locale per marketplace) + DocumentNumberService (numbering) + host-guard flag pattern (feature flags). Missing: a single `settings` key-value store (grouped, typed, encrypted-when-secret) + `/admin/settings` UI. The store must be the runtime-safe source of truth with documented per-key precedence over config/marketplace columns, a backfill + cutover plan, cache invalidation on write, rollback/fallback to config, and explicit read behavior for currency, invoices, maintenance, and feature flags during migration. **This is the R1 keystone** — currency UI, announcements, invoice settings all hang off it (P1, low risk, additive) |
| **Announcements** | MISSING | `announcements` table (scope: global/country/marketplace/role/seller/customer, severity, window, dismissible, banner/popup, internal links only). Frontend slot in layout + admin CRUD. P1, low |
| **Maintenance mode** | PARTIAL | Laravel native `down` exists; per-marketplace maintenance flag missing (settings store) |
| **Email/SMS templates** | PARTIAL | marketing mailer jobs exist; template admin missing (R4) |
| **Theme/branding per marketplace** | PARTIAL | marketplace domain/SEO config live; logo/branding fields per marketplace missing (additive columns) |
| **Environment/database settings UI (reference)** | REJECT | reference exposes env editing in admin — a known foot-gun (config-cache incident on record). NeoGiga policy: env stays on disk; settings store covers runtime-safe values only |

**R1 build order:** `admin.user:settings.manage` guard/permission + write-policy tests →
settings store + admin UI → announcements → currency override UI → invoice settings → feature
flags migrate into store. Everything additive; every write must be permission-checked, audited,
cache-invalidated, and covered by policy tests before release.
