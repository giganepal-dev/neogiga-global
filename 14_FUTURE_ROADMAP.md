# 14 Future Roadmap

## Executive Summary

NeoGiga should evolve through disciplined platform layers: security and commerce foundation first, marketplace operations second, AI/LMS/POS third, then manufacturing, community, enterprise procurement, analytics, and global expansion.

## Roadmap

### 0-30 Days

- Implement auth with Sanctum.
- Add RBAC/ABAC and policies.
- Encrypt sensitive fields.
- Add route/feature tests for public APIs.
- Add CI for composer, npm build, PHPUnit, Pint, Larastan.
- Decide fate of root-level duplicate `app/` tree.

### 30-90 Days

- Build active cart with server-side pricing.
- Implement inventory reservation.
- Add checkout draft and order creation.
- Integrate payment provider in sandbox mode.
- Add seller/admin portal MVP.
- Add product/category SSR pages.
- Add Redis queues/cache.

### 3-6 Months

- RFQ and quotation workflow.
- B2B company accounts and approvals.
- LMS MVP with courses/projects/tutorials.
- AI tool dispatcher, RAG ingestion, vector adapter, citations.
- POS device authentication and cashier workflows.
- Import/export async jobs.

### 6-12 Months

- Procurement automation.
- Advanced seller operations and payouts.
- Marketplace analytics.
- Community Q&A.
- Product lifecycle/compliance/CAD/firmware/document workflows.
- Internationalization and multi-currency checkout.
- Observability, alerts, backups, DR.

### 12-24 Months

- Manufacturing/OEM/ODM/EMS workflows.
- Knowledge graph.
- AI procurement and engineering copilot at scale.
- Regional warehouses/fulfillment.
- Enterprise APIs and integrations.
- Data warehouse and forecasting.
- Selective microservice extraction.

## Future Recommendations By Domain

- Architecture: modular monolith before microservices.
- Database: standard UUID/audit/history conventions.
- Security: policy-first resource access.
- SEO: SSR content graph.
- AI: auditable RAG/tool architecture.
- Marketplace: secure transaction core.
- Inventory: reservations, transfers, forecasting.
- Manufacturing: OEM/ODM/EMS workflows after seller maturity.
- Battery/industrial/robotics: safety review and compliance metadata.
- LMS/community: content flywheel linked to products/projects.
- Enterprise: procurement workflows, SSO, audit logs, APIs.
- Cloud: managed Postgres, Redis, object storage, queues, monitoring.
- Analytics: event stream and warehouse.

## Priority

Security and commerce correctness first; AI and marketplace breadth second.

## Estimated Effort

World-class engineering marketplace: 12-24 months with a focused team.

