# 02 Architecture Audit

## Executive Summary

NeoGiga has a Laravel monolith foundation with API-first intentions, domain-specific controllers, service classes, and a large schema footprint. It is not yet a clean DDD, modular monolith, or microservice-ready architecture. The repository includes duplicate root-level `app/` domain models outside the real Laravel app, which creates ownership and autoload ambiguity.

## Current Status

- Main runtime app: `giga-nepal-backend`.
- Root-level duplicate model tree: `app/Models/*`, `app/Services/*`.
- API routes: `giga-nepal-backend/routes/api.php`.
- Web routes: `giga-nepal-backend/routes/web.php`.
- Service layer exists for marketplace resolution, BOM, LMS matching, AI cart/POS, and AI tools.
- No repository layer, DTO layer, request classes, events/listeners, jobs, or command bus pattern visible.

## Completed

- API-first route structure with `/api/v1`.
- Domain route groups for marketplaces, categories, brands, products, vendors, inventory, cart, checkout, orders, AI, POS, LMS, and admin import/export.
- Baseline service binding in `AppServiceProvider`.
- Marketplace migrations loaded from a subdirectory via `loadMigrationsFrom`.
- SSR landing page separate from API layer.

## Partially Completed

- Layer separation: controllers, models, services exist, but business logic is still controller/model-heavy.
- Marketplace architecture: marketplace/domain/country/currency tables exist, but tenant scoping is not consistently enforced across controllers.
- Regional architecture: schema exists, execution is partial.
- Global catalog: product/brand/category schema exists, but global vs regional product governance is not fully implemented.
- Microservice readiness: domain boundaries are named, but no service contracts, queues, idempotency, or integration boundaries are mature.

## Missing

- Clear bounded-context modules.
- Repository interfaces.
- DTOs/resources for API output.
- FormRequest classes for reusable validation.
- Events/listeners/jobs for async work.
- Queue-backed workflows.
- Tenant/marketplace context middleware.
- Domain services for checkout, payment, order lifecycle, RFQ, seller ops.
- Versioned API resources and deprecation policy.
- Monorepo package structure or workspace tooling.

## Risk

Architecture risk is medium-high. The current structure can support early MVP work, but continued feature additions without module boundaries will create a large, tightly coupled Laravel monolith.

## Recommendation

Adopt a modular-monolith layout before microservices: `Catalog`, `Marketplace`, `Inventory`, `Pricing`, `Commerce`, `Seller`, `AI`, `LMS`, `POS`, `Admin`, `SEO`. Add service contracts, request classes, API resources, policy gates, and job/event boundaries.

## Priority

P0: Remove or quarantine root-level duplicate `app/` tree from runtime ambiguity.  
P1: Add auth/policy middleware and marketplace context enforcement.  
P2: Introduce modules, DTOs, resources, jobs/events.

## Estimated Effort

2-4 weeks for modular-monolith cleanup and conventions.  
8-12 weeks for full enterprise architecture maturation.

