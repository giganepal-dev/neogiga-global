# REMAINING_TASKS.md

Generated: 2026-07-11

## Phase 1 - Admin Control Center

- Add enterprise KPI dashboard widgets for revenue, orders, RFQs, BOM imports, PCB orders, customers, products, countries, warehouses, queue health, import status, search index, API health, cache, disk, and database.
- Add Admin UI for customer BOM imports: list, view lines, rematch, manual product assignment, convert to RFQ.
- Add Admin UI for product image candidates: list, filter by confidence/status, export manifest, mark approved/rejected.
- Add unified Import Center for JLCPCB, licensed catalog feeds, BOM imports, image candidates, and import error logs.

## Phase 2 - Media and Product Completeness

- Acquire approved manufacturer/distributor image feeds.
- Run `product-images:import-licensed-manifest` for confirmed image sets.
- Add derivative generation for WebP/AVIF thumbnails.
- Add product media review states to Admin product detail.
- Promote reviewed high-quality products from marketplace-only to public SEO where appropriate.

## Phase 3 - Search and SEO

- Decide database search vs Elasticsearch/OpenSearch.
- If Elasticsearch is selected, implement index mappings, queue rebuild, health checks, and fallback.
- Expand public SEO publication only for reviewed products/categories/manufacturers.
- Generate country/category/manufacturer landing pages with hreflang and canonical rules.

## Phase 4 - Commerce Operations

- Complete payment provider integration with capture/refund/webhooks.
- Complete shipping quote/label/tracking provider integration.
- Add order timeline and fulfillment automation.
- Add settlement and accounting reports.
- Add seller/distributor payout workflows.

## Phase 5 - Security and Permissions

- Audit every write route for auth, CSRF/token, permission, validation, and audit log.
- Replace remaining coarse admin gates with explicit policies/permissions.
- Add destructive action confirmations and audit events.
- Add CI checks for route protection and permission coverage.

## Phase 6 - AI, PCB, LMS, Knowledge

- Connect AI BOM assistant to safe product search/RFQ tools with advisory disclaimers.
- Build PCB order admin workflow.
- Expand LMS product-linked lessons and course SEO.
- Add CMS/blog/knowledge base admin module.

