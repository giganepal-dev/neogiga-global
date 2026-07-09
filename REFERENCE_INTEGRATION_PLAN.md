# REFERENCE_INTEGRATION_PLAN (2026-07-09)

Principle: **no archive code executes in NeoGiga** — modules are rebuilt natively against NeoGiga's
schema/design-system, using the archive as functional blueprint. Nothing is deleted.

## 1. Admin Orders (integrate NOW)
| Aspect | Detail |
|---|---|
| Source (blueprint) | `archive/app/Http/Controllers/OrderController.php`, `backend/orders/*` |
| Target | `Admin\DashboardController::{orders,order}` + `resources/views/admin/{orders,order-detail}.blade.php` |
| Refactor | full native rebuild; Eloquent `Order/OrderItem/OrderStatusHistory` models |
| DB dependency | existing `orders`, `order_items`, `order_status_histories`, `payments` (no migration) |
| Routes | `GET admin/orders`, `GET admin/orders/{id}`, `POST admin/orders/{id}/status` (admin.web group) |
| Permissions | session `admin.web` guard (roles super_admin/admin); permission-key placeholders documented in routes comment (`admin.orders.view/update`) pending RBAC wiring of the web console |
| UI dependency | existing admin layout/design tokens; new sidebar link (Commerce section) |
| Migration risk | none (read + status update only; status whitelisted to the DB enum; every change appended to `order_status_histories`) |
| Testing | local render vs `neogiga_test`; live: page 302-gated, render-on-prod, wallet canary |

## 2. Invoice print/download (integrate NOW)
Source blueprint `backend/invoices` → target `admin/invoice.blade.php` + `DashboardController::invoice`.
Print-CSS (browser Print → PDF = download). NeoGiga brand header (navy/cyan/gold). Route
`GET admin/orders/{id}/invoice`. No DB writes. Risk: none.

## 3. Frontend product pages (integrate NOW — native; archive has no storefront)
`Web\ProductPageController::{index,show}` + `frontend/products/{index,show}.blade.php`.
Routes `GET /products` (search `q`, category filter, pagination), `GET /products/{slug}`.
Reads `products` (+brand, category, marketplace prices if present). NeoGiga extensions on the page:
MPN, SKU, technical-spec table, regional-stock badge, datasheet link (if `product_datasheets` row),
Bulk-RFQ CTA (→ existing public RFQ flow), Ask-AI-Engineer CTA placeholder, B2B-price login prompt
placeholder, related products (same category), LMS-tutorial placeholder. SSR + meta/canonical/JSON-LD.
Risk: low (read-only). Empty-catalog states designed in (1 product currently seeded).

## 4. Support chat (BACKLOG #1 — not in this cycle)
New guarded additive migration: `conversations` (id, subject, user_id, vendor_id nullable, assigned_admin_id
nullable, type enum support|seller|product_qa|ai, product_id nullable, status open|assigned|resolved|closed,
last_message_at) + `conversation_messages` (conversation_id, sender_user_id, body, is_internal_note,
read_at). API: customer create/list/reply; admin inbox list/assign/reply/close. Admin UI `/admin/chats`.
Placeholders: AI-assistant responder, human-handoff flag. Blueprint: archive `ChatController`/
`ConversationController`. Risk: medium (new tables) — needs its own cycle with tests.

## 5. Product reviews (BACKLOG #2)
`product_reviews` additive migration (product_id, user_id, order_id nullable, rating 1-5, title, body,
status pending|approved|rejected) + admin moderation page + frontend display on product page.
Blueprint: archive `ReviewController`.

## Explicitly NOT integrated (exists in NeoGiga, archive weaker): POS, catalog mgmt, customers,
sellers, marketing, media, settings, staff, website setup, wallet, coupons, offline payments, geo, addons.
