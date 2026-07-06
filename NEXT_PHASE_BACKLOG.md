# NeoGiga — Next Phase Backlog (Phase 1)

Ordered by dependency. Each item references the audit finding or blueprint section it serves.

## P0 — Blockers (do first, in order)

1. **Schema reconciliation (DB-02/DB-04).** Fill the 34 empty-shell migrations (AI, POS, LMS,
   product extras, import/export) using the orphaned root-tree models as the spec; reconcile
   model↔migration drift (`products.mpn` vs model fillable, cart_items AI columns, etc.).
2. **Merge orphaned `app/` tree** into `giga-nepal-backend/app/` (namespaced per bounded
   context: `Models\Lms`, `Models\Pos`, `Models\Ai`, …), update imports, then retire root tree.
3. **Adopt blueprint migration template** before first production data: UUIDv7 PKs, soft
   deletes, `row_version`, `created_by/updated_by`, audit trigger → `audit_log` (Blueprint §10).
4. **Auth: Laravel Sanctum** + roles (`customer`, `vendor_admin`, `regional_ops`, `catalog_ops`,
   `finance`, `global_admin`) + policies on every route-model binding (SEC-01/03/10 — kills the
   IDOR class). Replace `admin.token` middleware with real RBAC (SEC-02).

## P1 — Nepal commerce core (Blueprint Phase 1)

5. Cart/checkout/orders: server-side pricing only; oversell guard via conditional
   `available >= qty` update; order status history; NP VAT 13% tax rule; invoice numbering per
   NP rules (Blueprint §17 regulatory packs).
6. Inventory soft-reserve with TTL (15 min) + release job (Blueprint §18).
7. Payment adapter interface + eSewa/Khalti/FonePay sandbox + COD; payment state machine
   (`initiated → authorized → captured → …`) with evented transitions (Blueprint §23).
8. Import/export as **queued jobs**: dry-run diff, per-row errors, secure upload pipeline
   (type sniffing, size caps, AV hook — SEC-14).
9. Vendor portal endpoints (offer/stock management) with vendor-scoped policies.

## P2 — Hardening & platform

10. CI: GitHub Actions — `composer audit`, Pint, PHPUnit, Gitleaks, Semgrep (SEC-15).
11. Feature tests for every implemented endpoint; contract tests against OpenAPI spec.
12. OpenAPI 3.1 document for `/api/v1` (Blueprint §44).
13. Redis for cache/session/queue in staging/prod (PERF-01); response cache tags.
14. Audit logging for admin/state-changing actions wired to `audit_logs` (SEC-11).
15. Observability baseline: structured JSON logs with request IDs; health metrics (Blueprint §31).

## P3 — SEO & content (feeds Phase 2)

16. Category/product page templates (SSR) with `Product`/`Offer`/`BreadcrumbList` JSON-LD,
    reading `seo_meta` columns.
17. Sharded sitemaps + `lastmod` from events; image sitemap.
18. hreflang expansion: `hi-IN`, `ne-NP` locale content chain (Blueprint §17 fallback chain).
19. Manufacturer master + `mpn_normalized` + trigram index → MPN search (Blueprint §19–20).

## P4 — AI beta prerequisites (Phase 2)

20. AI orchestrator service: Claude API router (env-gated), tool registry over `AiToolsContract`,
    conversation store, immutable `ai_audit` log, grounding guardrails, human handoff queue
    (Blueprint §13). No paid API calls before this ships.
21. RAG ingestion for datasheets/courses (pgvector) once knowledge assets exist.

## Deliberately deferred decisions

- **ADR-001 needed:** stay Laravel modular-monolith vs. begin NestJS/Next.js migration
  (blueprint stack). Recommendation: decide before Phase-2 frontend work; the Blade landing
  page is a stopgap keeping SEO live.
- Kafka/OpenSearch/Keycloak introductions are Phase-2+ infrastructure tracks.
