# NeoGiga — Deployment Notes (Phase 0-R)

## Runtime requirements

- PHP ≥ 8.2 (tested on 8.4), Composer 2.x
- PostgreSQL 16+ (blueprint SoR; SQLite acceptable for local dev only)
- Redis recommended for staging/prod (`CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`)

## Deploy checklist

1. `composer install --no-dev --optimize-autoloader`
2. `.env` from `.env.example`; **must set:**
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
   - `APP_KEY` via `php artisan key:generate`
   - `DB_*` (PostgreSQL)
   - `ADMIN_API_TOKEN` — 64-hex random (`php -r "echo bin2hex(random_bytes(32));"`); admin API
     is **fail-closed** without it
   - `SESSION_SECURE_COOKIE=true`
   - `SEED_ADMIN_PASSWORD` if seeding in production (otherwise a random one is printed once)
3. `php artisan migrate --force` (marketplace schema loads via AppServiceProvider)
4. `php artisan db:seed --force` (idempotent: countries, currencies, marketplaces + domains, taxonomy)
5. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
6. Web root = `giga-nepal-backend/public` only. Never expose `storage/` or the repo root.
7. Queue worker (`php artisan queue:work`) — required once Phase-1 imports/notifications land.

## Domain / edge

- Point neogiga.com / neogiga.in / giganepal.com at the app; marketplace resolution is
  host-based via `marketplace_domains` (seeded). **No forced geo-redirects** (SEO, Blueprint §42).
- Update `Sitemap:` line in `public/robots.txt` per edition domain at deploy time (currently
  neogiga.in), or template it in the web-server config.
- TLS 1.3; HSTS is emitted by the app on HTTPS. Put Cloudflare (WAF/bot/rate-limit L1) in
  front per Blueprint §12 when available.

## Security posture shipped in this phase

- Security headers + CSP middleware globally; API throttled 60/min (10/min anonymous writes).
- `/api/v1/admin/*` requires `X-Admin-Token` (constant-time compare, fail-closed).
- Unimplemented commerce endpoints return structured 501 — they cannot leak or mutate.
- Known gaps before real traffic: no user auth (Phase-1 Sanctum), no CI security scanning,
  IDOR scoping pending policies. See SECURITY_GAP_REPORT.md.

## Rollback

Stateless app + forward-only migrations: rollback = redeploy previous tag. Do **not**
`migrate:rollback` in production; write compensating migrations instead.
