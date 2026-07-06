# NeoGiga — Environment Variables

Canonical template: [`giga-nepal-backend/.env.example`](giga-nepal-backend/.env.example).
This document explains each NeoGiga-specific variable.

| Variable | Required | Purpose |
|---|---|---|
| `APP_KEY` | ✅ | Laravel encryption key — `php artisan key:generate` |
| `APP_DEBUG` | ✅ (`false` in prod) | Debug pages leak stack traces/config — never true in production |
| `APP_URL` | ✅ | Canonical URL for the served edition (e.g. `https://neogiga.in`) |
| `DB_CONNECTION` etc. | ✅ | PostgreSQL 16+ per blueprint; `sqlite` allowed for local dev |
| `ADMIN_API_TOKEN` | prod ✅ | Interim admin gate (`X-Admin-Token` header). Unset ⇒ all admin requests refused (fail closed). Generate: `php -r "echo bin2hex(random_bytes(32));"` |
| `SEED_ADMIN_PASSWORD` | optional | Admin seeder password; if unset a random one is generated and printed once |
| `SESSION_SECURE_COOKIE` | prod ✅ `true` | Cookies over HTTPS only |
| `CACHE_STORE` / `SESSION_DRIVER` / `QUEUE_CONNECTION` | recommended `redis` in prod | Blueprint §41 cache hierarchy |
| `NEOGIGA_GLOBAL_DOMAIN` / `NEOGIGA_INDIA_DOMAIN` / `NEOGIGA_NEPAL_DOMAIN` | optional | Domain hints for seeders/config; runtime resolution is DB-driven (`marketplace_domains`) |
| `ANTHROPIC_API_KEY` | Phase 2 | AI orchestrator. **Leave unset** — no paid AI API is called anywhere while absent |
| `AI_MODEL` | Phase 2 | Model id for the orchestrator (default `claude-sonnet-5`) |

Rules:
- Never commit `.env`; `.gitignore` already excludes it.
- Secrets rotate via deploy pipeline (Vault/KMS in later phases, Blueprint §40.5).
