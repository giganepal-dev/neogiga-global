# NeoGiga — Architecture Gap Report

**Baseline:** Blueprint EAS-NG-001 · **Date:** 2026-07-06

Legend: 🔴 missing · 🟠 partial/broken · 🟢 present

| # | Blueprint requirement | § | State | Gap detail |
|---|---|---|---|---|
| 1 | Global Control Plane + Regional Data Planes | 6 | 🔴 | Single Laravel app; no plane separation. Acceptable at this stage **if** module boundaries mirror the seams (they partially do via namespaces) |
| 2 | Modular monolith with DDD seams | 7 | 🟠 | Namespaced controllers per context exist; models split across two trees with inconsistent namespacing (`App\Models\X` vs `App\Models\Marketplace\X`) — seams currently broken |
| 3 | API gateway (Kong/Envoy), 2-tier edge | 8 | 🔴 | None. Not expected yet; Laravel middleware must stand in (throttle, auth) — currently absent |
| 4 | API versioning `/v1/` | 8 | 🔴 | Routes unversioned (`/api/products`). Cheap to fix now, expensive later |
| 5 | Idempotency-Key on mutating endpoints | 8 | 🔴 | No support |
| 6 | Event backbone (Kafka/Redpanda), outbox, sagas | 9 | 🔴 | No events, no queues configured beyond default database driver |
| 7 | PostgreSQL 16 as SoR, UUIDv7 PKs, soft delete, row_version, audit triggers | 10 | 🟠 | Migrations use bigint auto-increment ids, no soft deletes on most tables, no row_version, no audit triggers. DB engine undecided (`.env` missing) |
| 8 | CQRS-lite read models (OpenSearch/Redis) | 10–11 | 🔴 | None; `LIKE`-style search will be the only option |
| 9 | Search: OpenSearch parametric/faceted, MPN normalization | 11 | 🔴 | `products.mpn` column exists; no normalized MPN, no trigram, no index infra |
| 10 | CDN/edge caching, tagged purge | 12 | 🔴 | Out of repo scope; no cache headers set by app either |
| 11 | AI platform: orchestrator, tool registry, RAG, guardrails, audit | 13 | 🟠 | Four mock services exist (BOM builder w/ hardcoded component rules, cart, POS invoice, LMS matcher) — broken imports; no orchestrator/guardrails/audit. Blueprint's "AI must never invent price/stock" not enforced anywhere |
| 12 | Identity: Keycloak/OIDC, JWT, MFA, passkeys | 14 | 🔴 | Stock Laravel `User`; no API auth package at all |
| 13 | RBAC+ABAC via OPA | 15 | 🔴 | No roles on marketplace side; legacy IoT `roles` table unused |
| 14 | Multi-tenant: country=physical, org/seller=RLS | 16 | 🟠 | `marketplace_id` columns present in schema; no scoping middleware, `marketplace_id=1` hardcoded in AiCartService |
| 15 | Multi-country cells, ccTLD routing | 17 | 🟠 | `marketplaces` + `marketplace_domains` tables + resolver service designed correctly — the strongest part of the codebase. Broken by namespace bug; no config for neogiga.com/.in/giganepal.com seeded domains verified |
| 16 | Regional inventory (on_hand/reserved/available), soft-reserve TTL | 18 | 🟠 | `inventory_stocks`, `reserved_stocks` tables exist (unloaded); `reserve`/`releaseReservation` endpoints routed but handlers absent; no oversell guard |
| 17 | Manufacturer master + aliases + golden record | 19 | 🔴 | Only `product_brands`. No manufacturers, no aliases, no MPN resolution |
| 18 | Product master: GPID, spec EAV, assets versioning, lifecycle, xrefs | 20 | 🟠 | `products` covers commerce basics; `product_specs`/`spec_groups` real; documents/videos/compat/BOM/related tables are **empty shells**; no GPID/lifecycle/HS-code/ECCN |
| 19 | Warehouse WMS-lite | 21 | 🟠 | `warehouses` table real; movements table real; no receiving/picking flows |
| 20 | Logistics adapter layer | 22 | 🔴 | Absent |
| 21 | Payment adapter pattern, state machine, ledger | 23 | 🟠 | `payments`/`refunds` tables only; no adapters (eSewa/Khalti/Razorpay), no state machine |
| 22 | B2B (companies, approvals, credit) | 24 | 🔴 | Absent |
| 23 | Marketplace 1P+3P, buy-box | 25–26 | 🟠 | Vendor onboarding schema exists; no offers-on-GPID model, no buy-box |
| 24 | LMS (course→lesson→quiz→cert) | 27 | 🟠 | Tables are empty shells; models stubs; routed endpoints unimplemented |
| 25 | Community | 28 | 🔴 | Absent |
| 26 | Observability (OTel), monitoring/SLOs | 31–32 | 🔴 | Default Laravel logging only |
| 27 | DR/HA/backups | 33–35 | 🔴 | Out of scope for code; no notes/runbooks |
| 28 | DevSecOps pipeline | 36–37 | 🔴 | No CI at all; no composer.lock |
| 29 | Zero-trust security layers | 40 | 🔴 | See SECURITY_GAP_REPORT.md |
| 30 | Performance (cache hierarchy, N+1 discipline) | 41 | 🔴 | See PERFORMANCE_GAP_REPORT.md |
| 31 | SEO architecture | 42–43 | 🔴 | See SEO_GAP_REPORT.md |
| 32 | Monorepo layout (apps/services/packages/infra) | Repo §10 | 🟠 | Actual layout: orphan `app/` + `giga-nepal-backend/`. Needs consolidation before it hardens |

## Top 5 architectural actions (this phase)

1. **Single source of truth:** consolidate to `giga-nepal-backend/`; fix all model namespaces; register marketplace migrations.
2. **Boot-ability:** `.env.example`, migrations loadable, seeders runnable — the app must run before architecture debates matter.
3. **Version + gate the API now:** `/api/v1/...`, `throttle`, auth middleware on admin/commerce (cheap now, breaking later).
4. **Keep the seams:** one controller/service/model namespace per bounded context, matching Blueprint §7 service names, so future extraction is mechanical.
5. **Contract-first:** add OpenAPI stubs for implemented endpoints (foundation for Blueprint §44).
