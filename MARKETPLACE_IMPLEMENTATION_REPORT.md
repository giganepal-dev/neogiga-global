# MARKETPLACE_IMPLEMENTATION_REPORT (2026-07-11)

Country-marketplace domain + SEO configuration system. Foundational, additive, **inert** slice —
no marketplace was activated, no domain marked verified, no production domain changed. Built against
the existing 26-marketplace module (see MARKETPLACE_DOMAIN_SEO_AUDIT.md).

## 1. Tables / models created or extended
- **Migration** `2026_07_10_150000_extend_marketplaces_domain_seo_config.php` (all `hasColumn`/
  `hasTable`-guarded, additive):
  - `marketplaces` +55 columns: denormalized country/currency, full domain block (`domain_mode`,
    `generated_domain`, `canonical_domain`, `force_https`, `www_redirect_mode`, `ssl_status`,
    `is_domain_locked`, …), full SEO block (`seo_*`, `sitemap_enabled`, `hreflang_enabled`,
    `indexable`, `seo_is_auto_generated`, `seo_manual_override_fields`, …), status/content/system
    (`is_visible`, `maintenance_*`, `disabled_*`, `logo`, `favicon`, `created_by`, soft `deleted_at`).
  - `marketplace_domains` +4: `domain_type`, `redirect_url`, `verified_at`, `ssl_status`.
  - **new** `marketplace_audit_logs` (marketplace_id, user_id, action, old/new JSON, ip, user_agent).
- **Models**: `Marketplace` (extended fillable/casts + `SoftDeletes`), `MarketplaceDomain`
  (extended), **new** `MarketplaceAuditLog` (+ static `record()` helper).

## 2. Services
- `App\Services\Marketplace\MarketplaceDomainService` — `suggestGeneratedDomain()` (ISO alpha-2 →
  `{iso2}.neogiga.com`, GLOBAL→root), `sanitizeHostname()` (strip scheme/path/port/space, lowercase),
  `isValidHostname()` (reject localhost/IP/wildcard/malformed), `isDuplicateDomain()`,
  `backfillGeneratedDomain()` (never clobbers a custom/locked domain).
- `App\Services\Marketplace\MarketplaceSeoService` — `suggest()` / `apply($onlyEmpty)` country-
  template SEO fill respecting `seo_manual_override_fields`; `robotsFor()` (index,follow only when
  active + visible + indexable, else noindex,nofollow).
- Reused as-is: `MarketplaceContextResolver`, `DomainMarketplaceResolver`, `CountryResolver`,
  `MarketplaceUrlGenerator`, `MarketplacePathResolver` (host resolution already exists).

## 3. Seeder + generated domains
`MarketplaceDomainSeoSeeder` — idempotent, non-destructive. Preserves custom domains, generates the
rest, keeps everything **inactive**, ssl_status **pending**, auto-fills empty SEO.

| Code | Domain | Mode | Status |
|---|---|---|---|
| GLOBAL | neogiga.com | custom (locked) | live (unchanged) |
| NEPAL | giganepal.com | custom (locked) | live (unchanged) |
| INDIA | neogiga.in | custom (locked) | live (unchanged) |
| BANGLADESH | bd.neogiga.com | subdomain | generated, inactive, ssl pending |
| SRILANKA | lk.neogiga.com | subdomain | generated, inactive, ssl pending |
| PAKISTAN | pk.neogiga.com | subdomain | generated, inactive, ssl pending |
| BHUTAN bt · MALDIVES mv · UAE ae · SAUDI sa · QATAR qa · OMAN om · KUWAIT kw · USA us · CANADA ca | {iso2}.neogiga.com | subdomain | generated, inactive, ssl pending |
| (all other seeded countries) | {iso2}.neogiga.com | subdomain | generated, inactive, ssl pending |

## 4. Permission matrix (defined in the codex; enforcement DEFERRED)
| Permission | Super Admin | Marketplace Admin | Notes |
|---|---|---|---|
| marketplaces.view / view_audit | ✓ | ✓ (own) | |
| marketplaces.create/update | ✓ | ✓ (own) | |
| marketplaces.enable/disable | ✓ | ✓ (own) | validation gate required |
| marketplaces.manage_domain | ✓ | ✗ | primary prod domain = Super Admin only |
| marketplaces.verify_domain | ✓ | ✓ | real DNS/HTTP check only |
| marketplaces.manage_seo | ✓ | ✓ (own) | |
| marketplaces.manage_scripts | ✓ | ✗ | header/footer scripts = Super Admin only |
| unlock domain / force-enable / alter canonical after launch | ✓ | ✗ | Super Admin only |

## 5. DNS records still required (before any subdomain can go live)
None of the generated subdomains resolve yet. For each marketplace to activate, DNS + TLS must be
provisioned **first**, then `verify-domain` run:
- `bd.neogiga.com`, `lk.neogiga.com`, `pk.neogiga.com`, `bt.neogiga.com`, `mv.neogiga.com`,
  `ae/sa/qa/om/kw/us/ca.neogiga.com`, and the remaining seeded countries — each needs an A/AAAA or
  CNAME to the app host and a TLS cert. `ssl_status` stays `pending` until verified.
- Existing live domains (neogiga.com/.in, giganepal.com) already have DNS + TLS — unchanged.

## 6. Tests (`MarketplaceDomainSeoTest`)
ISO domain generation · GLOBAL→root · custom-domain preservation by seeder · duplicate rejection ·
hostname sanitization · invalid-host rejection (localhost/IP/wildcard/space/no-dot) · SEO default
generation · inactive-noindex / active-index · manual SEO override preserved · regenerate-only-empty ·
seeder idempotency + audit logging. **Result: 11 passed, 28 assertions, 0 failures.** Full suite
green afterward (227 assertions, no regressions).

## 7. DEFERRED (documented, not built this cycle)
- Tabbed **admin UI** (General/Domain/Status/SEO/Branding/Advanced), list columns, filters, wizards.
- **API endpoints** `/api/admin/marketplaces*` (generate-domain, verify-domain, generate-seo,
  validate-launch, clear-cache, audit-history) and public `/api/marketplace/current`, `/marketplaces/active`.
- **Frontend SEO rendering** + per-marketplace **sitemap** indexes + hreflang emission.
- **Host-spoofing allow-list middleware** (the resolver stack exists; the explicit allowed-host
  rejection + trusted-proxy hardening is not yet wired).
- **Permission enforcement** wiring + role gates; **domain verification** (real DNS/HTTP).
Each is a substantial follow-up; the schema + services here are the foundation they consume.

## 8. Deploy & rollback commands
Deploy (from repo root, standard procedure):
```
scp giga-nepal-backend/database/migrations/2026_07_10_150000_extend_marketplaces_domain_seo_config.php precious:$P/database/migrations/
scp giga-nepal-backend/app/Models/Marketplace/{Marketplace,MarketplaceDomain,MarketplaceAuditLog}.php precious:$P/app/Models/Marketplace/
scp giga-nepal-backend/app/Services/Marketplace/{MarketplaceDomainService,MarketplaceSeoService}.php precious:$P/app/Services/Marketplace/
scp giga-nepal-backend/database/seeders/MarketplaceDomainSeoSeeder.php precious:$P/database/seeders/
ssh precious "cd $P && php artisan migrate --force --path=database/migrations/2026_07_10_150000_extend_marketplaces_domain_seo_config.php"
ssh precious "cd $P && php artisan db:seed --class=Database\\Seeders\\MarketplaceDomainSeoSeeder --force"
ssh precious "cd $P && php artisan config:clear && php artisan cache:clear && php artisan config:cache"   # config:cache LAST
# canary: curl -sL -H 'Accept: application/json' https://neogiga.com/api/v1/wallet  => 401
```
Rollback:
```
ssh precious "cd $P && php artisan migrate:rollback --step=1 --path=database/migrations/2026_07_10_150000_extend_marketplaces_domain_seo_config.php"
# (down() drops marketplace_audit_logs, the 4 marketplace_domains columns, and all added marketplaces columns — existing data/rows untouched)
```

## Invariants honoured
Existing domains preserved · generated ≠ verified (`ssl_status` pending) · no marketplace
auto-activated · malformed/duplicate/localhost/IP/wildcard hosts rejected · manual SEO never
overwritten · inactive ⇒ noindex · every change auditable.
