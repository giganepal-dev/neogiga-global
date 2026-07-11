# MISSING_MODULES.md

Generated: 2026-07-11

## Missing or Not Verified

- Elasticsearch service, index templates, ingestion jobs, and admin search health panel.
- Redis-backed queue/cache/session verification and admin Redis health panel.
- Full system health dashboard for disk, Postgres, Redis, cache, queues, imports, search, API health.
- Real-time dashboard widgets for live visitors, realtime sales, exchange rates, queue latency, import progress.
- Provider-backed payment gateway operations.
- Provider-backed shipping/carrier operations.
- Complete accounting ledger, settlement, payout, tax, and invoice reporting.
- Licensed source image feeds for real product photos.
- Admin UI for reviewing `product_image_candidates` and converting approved rows into licensed manifests.
- Admin UI for newly deployed customer BOM imports.
- Admin UI for PCB orders and manufacturing workflow.
- Blog/CMS/knowledge base admin editorial tools.
- Complete role-permission policy matrix covering all admin/API write actions.
- Full audit-log integration for every destructive/admin operation.
- CDN/media transform pipeline for WebP/AVIF responsive derivatives.

## Duplicated / Needs Rationalization

- Public and admin BOM concepts exist separately: curated BOM projects, custom builds, and customer BOM imports. These need one Admin BOM section with tabs.
- Import systems exist in multiple layers: generic imports, JLCPCB import, licensed production catalog pipeline, customer BOM import. Admin should group them under Import Center.
- Product visibility gates use product status, approval status, source review status, search status, and visibility status. Admin should expose these as one clear workflow.

