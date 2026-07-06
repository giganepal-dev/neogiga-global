# 15 Priority Actions

## Executive Summary

NeoGiga should not build more feature breadth until the foundation can safely support identity, commerce, payments, inventory, seller operations, and tests.

## P0 - Must Fix First

| Action | Reason | Related files | Effort |
| --- | --- | --- | --- |
| Implement auth | All commerce/admin/seller flows need identity | `routes/api.php`, `User.php` | 1-2 weeks |
| Add RBAC/policies | Prevent cross-seller/admin access | `EnsureAdminToken.php`, controllers | 1-2 weeks |
| Encrypt sensitive config | Plain secrets are high risk | `create_device_configs_table.php` | 2-4 days |
| Build test suite | Current tests are examples only | `tests/*` | 2-4 weeks |
| Decide duplicate root app tree | Avoid autoload/ownership confusion | root `app/`, backend `app/` | 1 week |
| CI pipeline | Prevent regressions | repo root / GitHub workflow | 2-5 days |

## P1 - Build Next

- Authenticated cart.
- Server-side pricing.
- Inventory reservation.
- Checkout draft.
- Payment sandbox adapter.
- Order creation.
- Seller/admin portal MVP.
- SSR product/category pages.
- Redis cache/queue setup.

## P2 - Build After P1

- RFQ/quotation.
- POS.
- LMS MVP.
- AI tool dispatcher and RAG ingestion.
- Product document/datasheet ingestion.
- Marketplace analytics.

## P3 - Postpone

- Full microservices.
- Advanced AI autonomous procurement.
- Manufacturing/OEM/ODM/EMS.
- Global warehouse optimization.
- Knowledge graph productionization.
- Community moderation at scale.
- Multi-country tax automation beyond initial target regions.

## Technical Debt Must Be Fixed First

1. Missing auth/RBAC.
2. Weak tests.
3. Duplicate model tree.
4. Inconsistent UUID/audit/soft-delete strategy.
5. Plain sensitive fields.
6. Route contracts that look live but return `501`.
7. No CI/CD.

## Enterprise Improvements

- SSO/SAML/OIDC.
- Audit logs for every admin/seller/commercial action.
- Data retention policy.
- Backup and disaster recovery.
- Observability with traces, metrics, logs, and alerts.
- Security scanning and dependency audit.
- SLA/SLO definitions.
- Versioned public APIs and developer portal.

## Features To Become The World's Leading Engineering Marketplace

- Deep product knowledge graph.
- Datasheet search with citations.
- AI BOM builder with real inventory and alternatives.
- LMS projects linked to purchasable kits.
- Verified seller/manufacturer network.
- RFQ and procurement automation.
- Regional stock and delivery transparency.
- CAD/firmware/example-code library.
- Compliance/lifecycle/cross-reference intelligence.
- Enterprise APIs for factories, universities, and makerspaces.

## Final Answers

1. Already built: Laravel backend, marketplace schema, public catalog APIs, vendor onboarding shell, inventory read APIs, SEO landing, AI foundation, API contracts.
2. Partially complete: marketplace, regional architecture, pricing, inventory, seller approvals, AI, SEO, LMS schema, POS schema.
3. Missing: auth/RBAC, commerce execution, payments, order lifecycle, seller/admin portals, LMS runtime, POS runtime, RFQ, CI/CD, monitoring, domain tests.
4. Build next: auth/RBAC, tests, secure cart/checkout/order/payment, seller/admin MVP.
5. Postpone: microservices, advanced AI autonomy, manufacturing workflows, global warehouse optimization.
6. Technical debt first: auth gap, duplicate app tree, weak tests, sensitive fields, incomplete route contracts.
7. Enterprise improvements: SSO, audit logs, CI/CD, observability, backup/DR, API governance.
8. World-leading features: AI BOM, datasheet RAG, knowledge graph, verified sellers, LMS-project kits, regional inventory, procurement APIs.

