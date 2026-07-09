# REFERENCE_CODE_AUDIT — `~/Downloads/archive` (2026-07-09)

**Identity:** Active eCommerce CMS (CodeCanyon) production dump of **mystorenepal.com** — Laravel 8-era
monolith, Blade admin ("backend") + separate mobile app dir (`mystoreapp/`). 4 Envato purchase-code
`.txt` files present at root → user-owned licenses (adaptation into the user's own product permitted;
redistribution/resale not).

**⚠ Sensitive content found — never copy:** root `.env` (live MyStoreNepal credentials),
`shop.sql` (production DB dump incl. customer data), Google/Yandex site-verification files,
`vendor/` tree. These are excluded from all reuse.

## Inventory
| Area | Contents | State |
|---|---|---|
| Controllers (49) | Admin: Order, Invoice, Pos, Product, Category, Brand, Attribute(+Value), Review, Chat, Conversation, Customer, Staff, Role, Setting, AizUpload (media), Blog(+Category), Coupon, Offer/Marketing, ManualPaymentMethod (offline pay), Addon, Wallet, ClubPoint, Newsletter/Subscriber, Tax, Currency, Country/State/City/Zone, Page, Update/Install | Complete, production-tested |
| Routes | `admin.php`, `pos.php`, `seller.php`, `offline_payment.php`, `refund.php`, `web.php`, `api.php` | Complete admin surface |
| Admin views (`resources/views/backend/`) | `dashboard.blade.php`, `orders/`, `invoices/`, `pos/`, `product/`, `chats/`, `conversations/`, `marketing/`, `customers/`, `staff/`, `settings/`, `system/`, `uploaded_files/`, `website_settings/`, `blog/`, `addons/`, `layouts/`, `inc/` | Complete admin UI (Bootstrap/AIZ theme) |
| Frontend views | `app.blade.php` + `inc/` + `payment/` only | **Storefront absent** (was a separate app) — no product/category/cart pages to reuse |
| Migrations | 3 only (schema lived in `shop.sql`) | Not reusable |
| Assets | `public/` AIZ theme CSS/JS, jQuery/Bootstrap stack | Conflicts with NeoGiga's self-hosted CSP-strict SSR design system |
| Addons | empty dir | n/a |

## Per-feature classification
| Feature (prompt list) | Archive location | Classification | Reason |
|---|---|---|---|
| Admin dashboard layout / sidebar / topbar / cards / charts | `backend/layouts,inc,dashboard` | **Design reference only** | NeoGiga admin (navy/cyan design system, CSP `script-src 'self'`, no jQuery) is live and newer; AIZ theme incompatible |
| POS system | `PosController`, `backend/pos` | **Do not use** | NeoGiga PosService + admin POS page already live |
| Product/Category/Brand mgmt | resp. controllers + `backend/product` | **Do not use** (code) / design ref | NeoGiga catalog admin live; schema differs fundamentally (multi-marketplace) |
| Attribute mgmt | `AttributeController` | Design reference only | NeoGiga has spec/attribute tables (PR#3 + prod product_stock) |
| Product reviews | `ReviewController` | **Reuse after refactor (pattern)** | NeoGiga has NO reviews table — schema pattern useful; backlog |
| Orders / Order detail | `OrderController`, `backend/orders` | **Reuse as pattern → rebuild native** | NeoGiga has rich orders schema but NO admin orders UI — top gap, integrate now |
| Invoice print/download | `InvoiceController`, `backend/invoices` | **Reuse as pattern → rebuild native** | Print-friendly Blade invoice — integrate now |
| Seller / Customer modules | resp. controllers | Do not use | NeoGiga seller portal + customers exist (prod parallel build) |
| Marketing / Newsletter | `OfferController` etc. | Do not use | NeoGiga marketing automation module live is richer |
| Blog module | `BlogController` | Design reference only | Blog absent in NeoGiga; low priority — backlog |
| Product query module | (part of conversations) | Backlog | pairs with chat |
| Uploaded files (AizUpload) | `AizUploadController`, `backend/uploaded_files` | Do not use | NeoGiga media admin page exists |
| Support chat | `ChatController`, `ConversationController`, `backend/chats,conversations` | **Reuse schema pattern → rebuild native** | NeoGiga has NO conversations tables; highest-value backlog (see plan) |
| Offline payment | `ManualPaymentMethodController` | Design reference only | NeoGiga payment_providers abstraction (bank_transfer/cod seeded) covers it |
| Website setup / Settings / Staff / System / Addon manager | resp. controllers | Do not use | NeoGiga settings/RBAC/admin exist; addon system is CMS-specific |
| Frontend homepage / listing / product / category / search / cart-checkout UI | — | **Absent in archive** | Nothing to reuse; NeoGiga product pages built native (this cycle) |
| CSS/JS assets | `public/` | Do not use | jQuery/Bootstrap vs NeoGiga CSP-strict vanilla SSR |
| Chat/support features | see Support chat | pattern reuse | — |
| Image/media handling | AizUpload | Do not use | NeoGiga media module exists |

**Summary verdict:** the archive's storefront is absent and its admin theme is incompatible, but its
**module patterns** (orders admin, invoice, conversations schema, reviews schema) are valuable blueprints.
Integrate: Orders admin + Invoice (now), frontend product pages (native build, now), chat + reviews (backlog).
