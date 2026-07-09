# NEOGIGA_REFERENCE_GAP_ANALYSIS (2026-07-09)

Reference (Active eCommerce/MyStoreNepal archive) vs NeoGiga blueprint modules.
NeoGiga state verified live 2026-07-09 (admin.neogiga.com + backend API + PostgreSQL schema).

| NeoGiga module | NeoGiga state | Archive has | Gap → action |
|---|---|---|---|
| Admin AI Dashboard | KPI dashboard live; CommerceAI module live (API) | basic sales dashboard | no gap from archive; AI-chat admin = backlog |
| POS System | PosService + admin POS page live | full POS | none |
| Product Catalog / Global vs Regional | multi-marketplace schema + admin pages live | single-store products | none (archive is weaker) |
| Categories / Brands / Manufacturers | 177 categories live, brand tables | flat categories/brands | none |
| Attributes/Specifications | PR#3 spec tables + prod product_stock tables | attribute CRUD | admin UI for specs = backlog |
| **Orders / Seller Orders** | **schema live, NO admin UI** | full orders admin | **✅ CLOSED this cycle: /admin/orders (+detail, +status flow, +audit timeline)** |
| **Invoice** | none | invoice print | **✅ CLOSED: /admin/orders/{id}/invoice print view** |
| RFQ Orders | RFQ/Quotation module live (API + /admin/quotations) | none | none |
| Inventory / Warehouse | inventory services + region-stock module live | basic stock | none |
| Customers | /admin/users live (+verified badge, reset action) | customer CRUD | none |
| Sellers | seller portal + /admin/applications live | seller module | none |
| Marketing Campaigns | full marketing automation live | offers/newsletter | none |
| LMS | LMS platform live | none | none |
| Community | not built | none | future roadmap |
| **Support Chat** | **not built** | conversations/chat module | **backlog #1 — plan §Chat in REFERENCE_INTEGRATION_PLAN** |
| Product Reviews | not built | review moderation | backlog #2 |
| **Frontend product pages** | **none (landing/categories only)** | none (storefront absent) | **✅ CLOSED: native /products + /products/{slug}** |
| Files/Assets | media admin live | AIZ uploader | none |
| Website Setup / System Settings | settings admin live | website_settings | none |
| Roles/Permissions | RBAC + permission middleware live | staff/roles | admin staff UI = backlog-low |

Regional architecture note: neogiga.com (global) / neogiga.in (India) / giganepal.com (Nepal) is
served by the marketplaces + resolver system already live; new product pages read the same
marketplace-aware catalog, so no divergence is introduced.
