# 1 — Reference Project Inventory (2026-07-12)

**Scanned:** `~/Desktop/reference` (the mission's `/desktop/reference`). It contains **one** project,
not a folder of many. Read-only audit; nothing copied.

## Project: "MultiVendor E-Commerce System" (6valley-lineage CodeCanyon-style CMS)

| Property | Value (evidence) |
|---|---|
| Framework | **Laravel 8** (`laravel/framework ^8.65`) — **EOL since 2023** |
| PHP | `8.0\|^8.1` (NeoGiga runs 8.4/8.5) |
| Database | **MySQL** (`ext-mysqli`, install wizard) — NeoGiga is PostgreSQL 16 |
| Frontend | Blade + **Bootstrap 4** (EOL), **jQuery 3.x** (limited maintenance/security fixes; exact version must be checked), and **Vue 2** (EOL) via laravel-mix 5 |
| Modules | nwidart/laravel-modules (`Gateways` module enabled in `modules_statuses.json`) |
| Scale | 182 migrations · 83 models (legacy split `app/Model` + `app/Models`) · **238 controllers** · 371 Blade views |
| Route files | `admin.php`, `seller.php`, `customer.php`, `web.php`, `api/`, `install.php`, `update.php`, `test.php`, `shared.php` |
| Auth | Passport **and** Sanctum mixed + Socialite; separate admin/seller/customer guards |
| Permissions | `Admin/CustomRoleController` — admin custom roles (module-checkbox style, not scope-aware) |
| POS | **Yes** — `Admin/POSController` + `Seller/POSController` (search, barcode, hold order, cash) |
| Seller portal | Full: Dashboard, Product, POS, Order, Refund, Coupon, Shop, DeliveryMan, Reviews, Reports, Withdraw, Chatting |
| Customer portal | `Customer/{Auth,Payment,RewardPoint,System}` + `Web/` (loyalty, chatting, storefront) |
| Admin dashboard | Business overview, order/earning statistics widgets |
| Ecommerce | Category→Sub→SubSub (fixed **3 levels**, not unlimited), Brand, Attribute, Product, Deal (flash/featured/day), Coupon, Banner, Reviews, Wallet, Loyalty |
| Reports | Order/Product/Stock/Wishlist/Transaction/SellerProductSale reports + exports (`maatwebsite/excel`, `rap2hpoutre/fast-excel`, `app/Exports`) |
| Payment | Gateways module + SDKs: Stripe, Razorpay, PayPal (deprecated SDK), Paystack, MercadoPago, Xendit, Iyzico, Flutterwave, offline methods |
| Shipping | ShippingMethod/Type, CategoryShippingCost, ShipRocket adapter, DeliveryMan app flow (assign, cash collect, withdraw), delivery restriction |
| Chat | `ChattingController` on Admin/Seller/Web — buyer↔seller + support chat |
| Localization | Language admin + `symfony/translation`; per-entity localized fields |
| Import/export | phpspreadsheet + maatwebsite/excel: product/category bulk import, exports |
| Media | FileManager controller; intervention/image |
| SEO | Sitemap (spatie/laravel-sitemap), per-entity meta |
| Other | Employee mgmt, Support tickets/Help topics, SMS gateways (Twilio/Nexmo), Email templates, Announcement-ish notifications, Currency mgmt, Theme, Addon system, **Install/Update wizard + SoftwareUpdateController** (commercial-license machinery) |
| Barcode | `milon/barcode` — SKU/product label generation (POS) |
| Security risks | See `REFERENCE_SECURITY_RISK_REPORT.md` — EOL stack, `test.php` routes, `TestForDataInsertController`, install wizard, CKEditor 4 |
| Licensing | **CodeCanyon-style proprietary lineage** (install/update/addon machinery). Code must NOT be copied wholesale; patterns/workflows only — see reuse decision doc |

No `.env`, SQL dumps, or key material were observed at the scanned depth/scope. Excluded or deeper
paths were not opened; if later discovered, secrets/dumps must not be copied into NeoGiga or pasted
into model context.

## What this reference is good for

It is a **feature checklist and workflow encyclopedia** for a mature multi-vendor marketplace
(exactly the capability list in the mission), implemented on a stack two generations older and one
database engine away from NeoGiga. Every valuable capability must be **rebuilt natively** on
NeoGiga's Laravel 11 + PostgreSQL 16 + marketplace-scope architecture — per the mission's own
adaptation rules. Companion docs: `REFERENCE_FEATURE_MATRIX.md` (per-feature classification),
`REFERENCE_SECURITY_RISK_REPORT.md`, `REFERENCE_REUSE_DECISION.md`.
