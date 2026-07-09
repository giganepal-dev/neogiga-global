# REUSABLE_FEATURE_MAP — archive → NeoGiga (2026-07-09)

Legend: ✅ integrate now · 🔁 pattern-reuse later (backlog) · 🎨 design reference only · ⛔ do not use

| # | Archive feature | Verdict | NeoGiga target |
|---|---|---|---|
| 1 | Orders admin (list, filters, status flow) | ✅ | `/admin/orders` — native rebuild on existing `orders`/`order_items`/`order_status_histories` tables |
| 2 | Order detail (items, payment, addresses, timeline) | ✅ | `/admin/orders/{id}` |
| 3 | Invoice print/download | ✅ | `/admin/orders/{id}/invoice` — print-CSS Blade (browser print = PDF download) |
| 4 | Product listing + single product frontend | ✅ (native — archive has none) | `/products`, `/products/{slug}` SSR with MPN/spec/stock/RFQ/AI CTAs |
| 5 | Support chat (conversations/messages, admin inbox, staff assignment) | 🔁 top backlog | new additive migration + `/admin/chats` (plan in REFERENCE_INTEGRATION_PLAN) |
| 6 | Product reviews (schema + moderation UI) | 🔁 backlog | `product_reviews` additive migration + admin moderation |
| 7 | Product Q&A (queries) | 🔁 backlog | rides on conversations schema |
| 8 | Blog module | 🎨 backlog-low | needs content strategy first |
| 9 | Dashboard cards/charts, sidebar, topbar | 🎨 | NeoGiga admin design system already live |
| 10 | POS, product/category/brand mgmt, customers, sellers, marketing, media, settings, staff/roles, website setup, addons, wallet, coupons, taxes, currencies, geo (country/state/city/zone), newsletter | ⛔ | all already exist in NeoGiga (live, richer, multi-marketplace) |
| 11 | Offline/manual payment methods | 🎨 | covered by `payment_providers` (bank_transfer/cod seeded, admin toggle live) |
| 12 | AIZ uploader, theme assets (jQuery/Bootstrap) | ⛔ | CSP-strict vanilla SSR design system in place |
| 13 | `.env`, `shop.sql`, verification files | ⛔ NEVER | security exclusions |

NeoGiga-specific extensions carried into the ✅ builds: marketplace (global/regional) column on orders list,
MPN + spec table + regional-stock badge + RFQ/Ask-AI CTAs on product pages, LMS-link placeholder,
B2B price prompt placeholder.
