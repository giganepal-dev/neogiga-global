# NeoGiga Codebase Structure Audit

**Generated:** 2026-07-08  
**Auditor:** Qwen Code Audit System  
**Purpose:** Comprehensive codebase structure analysis

---

## A. Framework Detection

### Backend Framework
| Component | Technology | Version | Status |
|-----------|------------|---------|--------|
| Framework | Laravel | ^11.31 | ✅ Installed |
| PHP | PHP | ^8.2 | Required (server has 8.4) |
| Build Tool | Vite | ^6.0.11 | ✅ Configured |
| CSS | Tailwind CSS | ^3.4.13 | ✅ Configured |
| Testing | PHPUnit | ^11.0.1 | ✅ Configured |
| Database | PostgreSQL | 16 | ✅ Production |
| Package Manager | Composer | - | ✅ composer.lock present |
| JS Package Manager | npm | - | ✅ package.json present |

### Frontend Framework
| Component | Technology | Status |
|-----------|------------|--------|
| SPA Framework | None (Blade templates) | Server-rendered admin |
| Build Tool | Vite 6.x | ✅ Configured |
| CSS | Tailwind CSS 3.x | ✅ Configured |
| JavaScript | Vanilla JS + Axios | ✅ Configured |
| Admin Views | Blade templates | ✅ Deployed on admin.neogiga.com |
| Public Views | Blade landing page | ✅ Deployed on neogiga.com |

---

## B. File Counts Summary

| Category | Count | Location |
|----------|-------|----------|
| **Migrations** | 132 | `database/migrations/` |
| **Models** | 148 | `app/Models/` |
| **Controllers** | 74 | `app/Http/Controllers/` |
| **Services** | 82 | `app/Services/` |
| **Middleware** | 5 | `app/Http/Middleware/` |
| **Form Requests** | 20 | `app/Http/Requests/` |
| **Seeders** | 11 | `database/seeders/` |
| **API Routes** | 321 | `routes/api.php` (541 lines) |
| **Web Routes** | ~50 | `routes/web.php` (estimated) |

---

## C. Route Analysis

### API Routes (routes/api.php - 541 lines, 321 Route:: definitions)

#### Public Read Routes (no auth)
```
GET /api/v1/marketplaces/*          - Marketplace resolution
GET /api/v1/categories/*            - Category browsing
GET /api/v1/brands/*                - Brand browsing
GET /api/v1/products/*              - Product catalog
GET /api/v1/vendors                 - Vendor listing
GET /api/v1/vendors/{slug}          - Vendor detail
```

#### Protected Routes (api.token middleware)
```
POST /api/v1/auth/register          - User registration
POST /api/v1/auth/login             - User login
GET  /api/v1/auth/me                - Current user
POST /api/v1/auth/logout            - Logout

POST /api/v1/vendors/register       - Vendor registration
POST /api/v1/vendors/{vendor}/apply-marketplace

GET  /api/v1/seller/*               - Seller panel (permission:seller.access)
GET  /api/v1/distributor/*          - Distributor panel
GET  /api/v1/b2b/*                  - B2B commerce
GET  /api/v1/bom/*                  - BOM projects
GET  /api/v1/ai/*                   - AI commerce (returns 501)
GET  /api/v1/pos/*                  - POS operations
GET  /api/v1/lms/*                  - LMS operations
GET  /api/v1/cart/*                 - Cart operations
GET  /api/v1/orders/*               - Order management
GET  /api/v1/inventory/*            - Inventory management
```

#### Admin Routes (admin.token middleware)
```
GET  /api/v1/admin/*                - Admin console
GET  /api/v1/admin/vendors/*        - Vendor admin
GET  /api/v1/admin/distributors/*   - Distributor admin
GET  /api/v1/admin/b2b/*            - B2B admin
GET  /api/v1/admin/bom/*            - BOM admin
GET  /api/v1/admin/inventory/*      - Inventory admin
GET  /api/v1/admin/lms/*            - LMS admin
GET  /api/v1/admin/import-export/*  - Import/export
```

### Web Routes (routes/web.php)
```
GET  /                              - Landing page
GET  /categories/{slug}             - Category pages (built, may not be deployed)
GET  /products/{slug}               - Product detail pages (if exists)
GET  /cart                          - Cart page (if exists)
GET  /checkout                      - Checkout page (if exists)

// Admin routes (admin.web middleware)
GET  /admin                         - Admin dashboard
GET  /admin/categories              - Category management
GET  /admin/products                - Product management
GET  /admin/vendors                 - Vendor management
GET  /admin/marketplaces            - Marketplace management
GET  /admin/users                   - User management
```

---

## D. Model Structure by Domain

### Marketplace Models (app/Models/Marketplace/)
```
Cart.php                    - Shopping cart
CartItem.php                - Cart items
City.php                    - Cities
Country.php                 - Countries
Currency.php                - Currencies
DeliveryZone.php            - Delivery zones
InventoryMovement.php       - Stock movements
InventoryStock.php          - Stock levels
Marketplace.php             - Marketplace entities
MarketplaceDomain.php       - Domain mappings
MarketplaceProductPrice.php - Marketplace prices
MarketplaceSetting.php      - Marketplace settings
Product.php                 - Products
ProductBomItem.php          - BOM items
ProductBrand.php            - Brands
ProductCategory.php         - Categories
ProductImage.php            - Product images
ProductLmsLink.php          - LMS links
ProductSeoMeta.php          - SEO metadata
ProductSpec.php             - Specifications
ProductSpecGroup.php        - Spec groups
ProductVariant.php          - Variants
Region.php                  - Regions
TaxZone.php                 - Tax zones
Vendor.php                  - Vendors
VendorMarketplaceApproval.php - Approvals
VendorOrder.php             - Vendor orders
VendorOrderItem.php         - Vendor order items
VendorPayout.php            - Vendor payouts
VendorPayoutItem.php        - Payout items
VendorProduct.php           - Vendor products
VendorProductPrice.php      - Vendor pricing
VendorProfile.php           - Vendor profiles
VendorSupportTicket.php     - Support tickets
Warehouse.php               - Warehouses
```

### AI Models (app/Models/)
```
AiBomBuild.php              - AI BOM builds
AiBomItem.php               - AI BOM items
AiCartAction.php            - AI cart actions
AiLmsRecommendation.php     - AI LMS recommendations
AiMessage.php               - AI messages
AiPosInvoice.php            - AI POS invoices
AiProductRecommendation.php - AI recommendations
AiSampleCodeSnippet.php     - AI code samples
AiSession.php               - AI sessions
```

### Other Domain Models
```
Affiliate/                  - Affiliate models
B2B/                        - B2B commerce models
Bom/                        - BOM project models
Distributor/                - Distributor network models
Erp/                        - ERP/procurement models
Lms/                        - LMS models
Marketing/                  - Marketing models
Payments/                   - Payment models
Promotion/                  - Coupon/giftcard models
```

---

## E. Controller Structure

### API Controllers (app/Http/Controllers/Api/)
```
AuthController.php          - Authentication
AI/                         - AI commerce controllers
Admin/                      - Admin API controllers
Affiliate/                  - Affiliate controllers
B2B/                        - B2B commerce controllers
Bom/                        - BOM controllers
Cart/                       - Cart controllers
Distributor/                - Distributor controllers
Inventory/                  - Inventory controllers
LMS/                        - LMS controllers
Marketing/                  - Marketing controllers
Marketplace/                - Marketplace controllers
Order/                      - Order controllers
POS/                        - POS controllers
Pricing/                    - Pricing controllers
Product/                    - Product controllers
Promotion/                  - Promotion controllers
Sales/                      - Sales controllers
Seller/                     - Seller panel controllers
Vendor/                     - Vendor controllers
Wallet/                     - Wallet controllers
```

### Admin Controllers (app/Http/Controllers/Admin/)
```
[Blade-based admin controllers for dashboard]
```

### Web Controllers (app/Http/Controllers/Web/)
```
[Frontend web controllers]
```

---

## F. Service Layer (app/Services/)

### Core Services (82 total)
```
Ai/                       - AI services
    AiCartService.php
    AiPosInvoiceService.php
    BomBuilderService.php
    LmsMatcherService.php
    
B2B/                      - B2B services
Bom/                      - BOM services
Cart/                     - Cart services
Checkout/                 - Checkout services
Coupon/                   - Coupon services
Dashboard/                - Dashboard services
Distributor/              - Distributor services
Email/                    - Email services
Erp/                      - ERP services
    RfqService.php
    QuotationService.php
    PurchaseOrderService.php
    
GiftCard/                 - Gift card services
ImportExport/             - Import/export services
Inventory/                - Inventory services
    ReservationService.php
    StockMovementService.php
    TransferService.php
    PurchaseReceivingService.php
    
Lms/                      - LMS services
Marketplace/              - Marketplace services
    MarketplaceResolverService.php
    ProductVisibilityService.php
    VendorVisibilityService.php
    MarketplacePricingResolver.php
    MarketplaceStockResolver.php
    
Marketing/                - Marketing services
Order/                    - Order services
Payment/                  - Payment services
Pos/                      - POS services
    PosService.php
    
Pricing/                  - Pricing services
    B2BPriceResolver.php
    
Product/                  - Product services
Promotion/                - Promotion services
Rfq/                      - RFQ services
Seller/                   - Seller services
Shipping/                 - Shipping services
Sitemap/                  - Sitemap services
Vendor/                   - Vendor services
    VendorRegistrationService.php
    VendorApprovalService.php
    VendorProductService.php
    VendorPricingService.php
    VendorInventoryService.php
    VendorOrderService.php
    VendorPayoutService.php
    VendorCommissionService.php
    VendorPerformanceService.php
    
Wallet/                   - Wallet services
```

---

## G. Middleware (app/Http/Middleware/)

```
AdminTokenMiddleware.php     - Admin API authentication
AdminWebMiddleware.php       - Admin web session auth
ApiTokenMiddleware.php       - API token authentication
PermissionMiddleware.php     - Role/permission checks
[Other middleware]
```

---

## H. Form Requests (app/Http/Requests/)

```
Seller/                     - Seller validation
Distributor/                - Distributor validation
B2B/                        - B2B validation
Bom/                        - BOM validation
Admin/                      - Admin validation
[20 total form request classes]
```

---

## I. Seeders (database/seeders/)

```
DatabaseSeeder.php          - Main seeder
[10 additional seeders for various modules]
```

---

## J. Important Missing/Incomplete Components

### Missing Tables (from migration audit)
- [ ] Dedicated `vendor_roles` and `vendor_permissions`
- [ ] `vendor_branches`
- [ ] `vendor_reviews` (separate from ratings)
- [ ] Complete `vendor_payouts` and `vendor_payout_items`
- [ ] `vendor_commission_rules`
- [ ] Full distributor network tables
- [ ] B2B account layer tables
- [ ] BOM project-commerce tables
- [ ] Commerce AI compatibility tables (if needed)

### Missing Controllers
- [x] Basic controllers exist for most domains
- [ ] Full CRUD implementation in admin controllers
- [ ] Seller panel UI controllers
- [ ] Distributor panel UI controllers

### Missing Services
- [x] Core services exist
- [ ] Full payment gateway adapters
- [ ] Analytics/GA4 service
- [ ] WhatsApp integration service

### Missing Tests
- [ ] Feature tests for seller panel
- [ ] Feature tests for distributor panel
- [ ] Feature tests for B2B commerce
- [ ] Feature tests for AI commerce
- [ ] Integration tests for payment gateways

---

## K. Frontend Structure

### Admin Views (resources/views/admin/)
```
dashboard.blade.php         - Admin dashboard
categories/                 - Category management
products/                   - Product management
vendors/                    - Vendor management
marketplaces/               - Marketplace management
users/                      - User management
[Deployed on admin.neogiga.com]
```

### Public Views (resources/views/)
```
welcome.blade.php           - Landing page
categories/                 - Category pages (built, deployment status unclear)
products/                   - Product detail pages (if exists)
cart/                       - Cart pages (if exists)
checkout/                   - Checkout pages (if exists)
```

---

## L. IoT/Device Modules (Preserved)

### Existing IoT Migrations
```
device_types                - Device type definitions
device_statuses             - Device status tracking
devices                     - Device registry (IMEI, MAC, serial)
device_configs              - Device configurations
firmwares                   - Firmware versions
firmware_updates            - Update history
network_providers           - Network providers (NTC, NCELL)
sites                       - Installation sites
gps_logs                    - GPS location history
rfid_logs                   - RFID scan logs
sensor_logs                 - Sensor readings
logs                        - General system logs
alerts                      - System alerts
support_tickets             - Customer support
audit_logs                  - Audit trail
```

### Nepal Geography Tables (Preserved)
```
provinces                   - Nepal provinces
districts                   - Districts (FK to provinces)
municipalities              - Municipalities (FK to districts)
wards                       - Wards (FK to municipalities)
customers                   - Customer records
```

---

## M. Code Quality Indicators

### Positive Indicators
- ✅ Proper namespacing by domain (Marketplace/, AI/, B2B/, etc.)
- ✅ Service layer abstraction (82 services)
- ✅ Form request validation (20 requests)
- ✅ Middleware for auth/permissions
- ✅ API versioning (/api/v1/)
- ✅ Rate limiting configured (throttle:writes)
- ✅ Token-based authentication
- ✅ Permission-based access control

### Areas Needing Attention
- 🟡 Test coverage unknown (need to verify tests/ directory)
- 🟡 Some admin controllers are stubs
- 🟡 AI/POS/LMS routes return 501
- 🟡 Payment gateway adapters missing
- 🟡 Frontend UX incomplete (landing-only)
- 🔴 No git repository (deployment risk)

---

## N. Deployment Structure

### Live Hostnames
```
backend.neogiga.com         - API backend
admin.neogiga.com           - Admin dashboard
neogiga.com                 - Public frontend
giganepal.com               - Nepal marketplace
neogiga.in                  - India marketplace (if configured)
```

### Database
```
Production: PostgreSQL 16 (neogiga database)
Isolated from other applications
```

### Server Configuration
```
Web Server: Apache/Nginx (Virtualmin managed)
SSL: Valid certificates for all hostnames
WWW redirect: Configured (www → non-www)
CORS: Restricted to allowed domains
Security Headers: Configured
APP_DEBUG: OFF (production safe)
```

---

*Audit generated by Qwen Code Audit System - 2026-07-08*
