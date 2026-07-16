# NeoGiga UI & RFQ/BOM System Audit Report

**Date:** 2026-07-15  
**Repository:** giga-nepal-backend  
**Laravel Version:** 12.x  
**PHP Version:** 8.2+

---

## 1. Executive Summary

The NeoGiga marketplace has a **solid foundation** for RFQ/BOM functionality but requires significant upgrades to match the reference site (SIC Components) and the blueprint requirements. The existing system includes:

- ✅ Basic RFQ submission form (single-item focused)
- ✅ BOM import parsing service (CSV/text only)
- ✅ Product matching engine
- ✅ Quotation data model
- ✅ Admin RFQ management views
- ✅ Regional marketplace architecture
- ✅ Multi-warehouse inventory system
- ✅ Product catalog with specifications

**Critical gaps identified:**
- ❌ No multi-line RFQ builder UI
- ❌ No dedicated BOM tool page with spreadsheet interface
- ❌ Limited file upload support (no XLSX, PDF drag-drop)
- ❌ No customer dashboard for tracking RFQs/BOMs
- ❌ No admin workflow for supplier quotation management
- ❌ Homepage lacks engineering marketplace positioning
- ❌ Product listing pages need filter enhancements
- ❌ No AI commerce assistant integration on product pages
- ❌ Missing manufacturer/brand discovery features

---

## 2. Existing Implementation Analysis

### 2.1 Database Schema

#### RFQ/Quotation Tables (✅ Complete Foundation)
```
rfq_requests          - Core RFQ records with status tracking
rfq_items             - Line items for each RFQ
quotations            - Customer-facing quotations
quotation_items       - Quotation line items
rfq_status_histories  - Audit trail for status changes
```

**Missing tables:**
```
bom_uploads           - Dedicated BOM upload tracking (uses bom_imports)
bom_column_mappings   - User-defined column mappings
bom_alternatives      - Customer-approved alternative parts
supplier_quotes       - Supplier-side quotations
supplier_quote_items  - Supplier quotation lines
customer_quotes       - Consolidated customer quotes
quote_versions        - Quote revision history
quote_approvals       - Approval workflow tracking
quote_activity_logs   - Comprehensive activity audit
product_aliases       - MPN alias mapping
manufacturer_aliases  - Brand name normalization
notification_logs     - Notification delivery tracking
```

#### BOM Tables (⚠️ Partial)
```
bom_imports           - Basic import tracking
bom_import_lines      - Parsed line items with match status
bom_projects          - Project-based BOM organization
bom_project_items     - Project line items
bom_project_categories- Project categorization
```

**Assessment:** The BOM schema supports basic imports but lacks:
- Column mapping storage
- Alternative approval tracking
- Version control for revisions
- Supplier quote linkage

### 2.2 Models

#### Existing Models (✅ Functional)
```
App\Models\Erp\RfqRequest
App\Models\Erp\RfqItem
App\Models\Erp\Quotation
App\Models\Erp\QuotationItem
App\Models\Bom\BomImport
App\Models\Bom\BomImportLine
App\Models\Bom\BomProject
App\Models\Bom\BomProjectItem
```

**Missing Models:**
```
BomUpload              - File metadata, virus scan status
BomColumnMapping       - Header detection and mapping
BomMatch               - Match results with confidence scoring
BomAlternative         - Customer-approved substitutions
SupplierQuote          - Incoming supplier offers
SupplierQuoteItem      - Supplier line items
CustomerQuote          - Outgoing customer quotations
CustomerQuoteItem      - Customer quote lines
QuoteVersion           - Revision tracking
QuoteApproval          - Approval workflow
ProductAlias           - MPN normalization
ManufacturerAlias      - Brand name aliases
RfqAssignment          - Sales team assignments
RfqMessage             - Internal/external messaging
RfqAttachment          - Secure file attachments
NotificationLog        - Delivery tracking
```

### 2.3 Services

#### Existing Services (✅ Core Logic Present)
```
App\Services\Erp\RfqService           - RFQ creation
App\Services\Erp\QuotationService     - Quote generation
App\Services\Bom\BomImportService     - Import orchestration
App\Services\Bom\BomImportParser      - CSV/text parsing
App\Services\Bom\BomComponentMatcher  - Product matching
App\Services\Bom\BomPricingService    - Price calculation
App\Services\Bom\BomAvailabilityService - Stock checking
```

**Missing Services:**
```
BomFileUploadService      - File handling, validation, scanning
BomColumnMapperService    - Intelligent header detection
BomWorkspaceService       - Spreadsheet-style editing
BomExportService          - Excel/CSV export with formatting
SupplierQuoteService      - Supplier RFQ distribution
CustomerQuoteService      - Customer quote consolidation
QuoteRevisionService      - Version management
QuoteComparisonService    - Multi-quote comparison
RfqAssignmentService      - Team assignment logic
RfqMessagingService       - Communication threading
ProductAliasService       - MPN normalization
ManufacturerAliasService  - Brand normalization
BomVirusScanService       - Security scanning
```

### 2.4 Controllers

#### Web Controllers (⚠️ Minimal)
```
RfqPageController         - Single-item RFQ form (create/store)
AiCommercePageController  - AI BOM build (basic)
CartPageController        - Cart with RFQ option
ProductPageController     - Product detail with RFQ button
```

**Missing Controllers:**
```
BomToolController         - Dedicated BOM workspace
CustomerRfqController     - Customer dashboard RFQ section
CustomerBomController     - Customer dashboard BOM section
AdminRfqController        - Enhanced admin RFQ management
AdminBomController        - Admin BOM review/approval
AdminSupplierQuoteController - Supplier quote management
AdminQuoteController      - Customer quote management
ManufacturerController    - Manufacturer discovery
EnhancedCategoryController- Advanced filtering
```

#### API Controllers (⚠️ Partial)
```
Api/Sales/RfqController           - Basic RFQ CRUD
Api/B2B/B2BQuotationController    - B2B quotes
Api/Admin/QuotationAdminController- Admin quote ops
Api/Admin/B2BAdminController      - B2B admin ops
```

**Missing API Endpoints:**
```
POST /api/bom/upload              - File upload
POST /api/bom/parse               - Parse and validate
POST /api/bom/map-columns         - Column mapping
POST /api/bom/match               - Product matching
GET  /api/rfq/{id}/status         - Status tracking
POST /api/rfq/{id}/message        - Add message
GET  /api/customer/rfqs           - Customer RFQ list
GET  /api/customer/boms           - Customer BOM list
POST /api/quote/{id}/accept       - Accept quote
POST /api/quote/{id}/convert-order- Convert to order
GET  /api/manufacturers           - Manufacturer list
```

### 2.5 Views

#### Existing Views (⚠️ Basic)
```
frontend/rfq/create.blade.php     - Single-item RFQ form
frontend/ai-commerce.blade.php    - AI BOM builder
admin/rfq-detail.blade.php        - Admin RFQ view
admin/quotations.blade.php        - Admin quote list
frontend/layout.blade.php         - Main layout (dark theme)
frontend/products/show.blade.php  - Product detail
frontend/brands/index.blade.php   - Brand list
frontend/categories/show.blade.php- Category listing
```

**Missing Views:**
```
frontend/bom-tool/index.blade.php           - BOM upload/workspace
frontend/customer/rfqs/index.blade.php      - Customer RFQ dashboard
frontend/customer/quotes/show.blade.php     - Quote detail
frontend/rfq/multi-line.blade.php           - Multi-product RFQ builder
frontend/manufacturers/index.blade.php      - Manufacturer directory
frontend/homepage-redesigned.blade.php      - New homepage
admin/rfq/index.blade.php                   - RFQ management dashboard
admin/quotes/create.blade.php               - Quote builder
```

---

## 3. Missing Capabilities Assessment

### 3.1 RFQ System Gaps

| Feature | Status | Priority |
|---------|--------|----------|
| Multi-line RFQ builder | ❌ Missing | Critical |
| RFQ from cart/wishlist | ❌ Missing | High |
| Guest RFQ submission | ✅ Exists | - |
| RFQ draft saving | ❌ Missing | High |
| RFQ status tracking page | ❌ Missing | High |
| RFQ attachment upload | ❌ Missing | Medium |
| Alternative part suggestion in RFQ | ❌ Missing | High |

### 3.2 BOM Tool Gaps

| Feature | Status | Priority |
|---------|--------|----------|
| XLSX/XLS file upload | ❌ Missing | Critical |
| Drag-and-drop upload | ❌ Missing | High |
| Virus/malware scanning | ❌ Missing | Critical |
| Column auto-detection | ❌ Missing | Critical |
| Manual column mapping UI | ❌ Missing | Critical |
| Spreadsheet-style editor | ❌ Missing | Critical |
| Inline cell editing | ❌ Missing | Critical |
| Large BOM handling (1000+ lines) | ❌ Untested | Medium |

### 3.3 Customer Dashboard Gaps

| Feature | Status | Priority |
|---------|--------|----------|
| My RFQs list | ❌ Missing | Critical |
| RFQ detail with timeline | ❌ Missing | Critical |
| Accept/reject/revise quote | ❌ Missing | Critical |
| Convert quote to order | ❌ Missing | Critical |
| My BOMs list | ❌ Missing | Critical |
| Download PDF/Excel | ❌ Missing | High |

### 3.4 Admin Management Gaps

| Feature | Status | Priority |
|---------|--------|----------|
| RFQ assignment to salesperson | ❌ Missing | Critical |
| Supplier quotation intake | ❌ Missing | Critical |
| Supplier quote comparison | ❌ Missing | Critical |
| Margin application | ❌ Missing | Critical |
| Quote to order conversion | ❌ Missing | Critical |

---

## 4. Reusable Components

### 4.1 Can Be Reused As-Is

```php
// Database
- rfq_requests, rfq_items tables
- quotations, quotation_items tables
- bom_imports, bom_import_lines tables
- rfq_status_histories table

// Models
- RfqRequest, RfqItem
- Quotation, QuotationItem
- BomImport, BomImportLine

// Services
- RfqService::create()
- BomImportService::createFromContent()
- BomComponentMatcher::match()
- BomImportParser::parse()
```

### 4.2 Requires Extension

```php
// Models - Add relationships and methods
- RfqRequest: add assignments, messages, attachments
- Quotation: add versions, approvals, activity log
- BomImport: add file metadata, column mappings, versions

// Services - Add new methods
- RfqService: add update, submit, assign, message
- BomImportService: add fromFile, rematch, export
- QuotationService: add revise, compare, convertToOrder
```

---

## 5. Database Changes Required

See detailed SQL migrations in the implementation plan. Key additions:

- `rfq_assignments` - Sales team assignments
- `rfq_messages` - Communication threading
- `rfq_attachments` - Secure file storage
- `bom_uploads` - File metadata and virus scan status
- `bom_column_mappings` - Header detection
- `bom_matches` - Match results with confidence
- `supplier_quotes` - Supplier-side quotations
- `customer_quote_items` - Enhanced quote lines
- `quote_versions` - Revision tracking
- `product_aliases` - MPN normalization
- `manufacturer_aliases` - Brand normalization
- `notification_logs` - Delivery tracking

---

## 6. Security Risks Identified

### 6.1 File Upload Vulnerabilities

**Current Risk:** No file upload validation in BOM flow

**Mitigation Required:**
- MIME type verification (not just extension)
- File size limits (configurable per marketplace)
- Virus/malware scanning integration
- Private file storage with signed URLs
- Sanitization of spreadsheet formulas (CSV injection prevention)

### 6.2 Formula Injection

**Risk:** CSV/XLSX files can contain malicious formulas

**Mitigation Required:**
- Strip or escape formulas starting with `=`, `+`, `-`, `@`
- Sanitize all imported cell values
- Warn users about formula risks in exports

### 6.3 Data Access Control

**Mitigation Required:**
- User ID validation on all customer endpoints
- Role-based access control for admin functions
- Regional data isolation
- Supplier cost hiding from customers
- Secure public IDs for tracking (not sequential)

---

## 7. Deployment Plan

### Phase 1: Database & Models (Week 1)
1. Create new migrations
2. Run migrations on staging
3. Create new Eloquent models
4. Update existing models with relationships
5. Seed test data

### Phase 2: Backend Services (Week 2-3)
1. Implement file upload service
2. Build column mapping service
3. Enhance BOM matching with aliases
4. Create supplier quote service
5. Build customer quote service
6. Implement notification service

### Phase 3: API Layer (Week 3-4)
1. Build BOM tool APIs
2. Create customer RFQ/quote APIs
3. Build admin management APIs
4. Write API tests

### Phase 4: Frontend UI (Week 4-6)
1. Redesign homepage
2. Enhance header and navigation
3. Build BOM tool workspace
4. Create multi-line RFQ builder
5. Build customer dashboard sections

### Phase 5: Integration & Testing (Week 6-7)
1. End-to-end testing
2. Security penetration testing
3. Performance optimization
4. Mobile responsiveness testing

### Phase 6: Production Deployment (Week 8)
1. Database backup
2. Run migrations
3. Deploy code
4. Clear caches
5. Monitor logs

---

## 8. Rollback Plan

### Pre-Deployment Checklist
- [ ] Full database backup created
- [ ] Code snapshot tagged in Git
- [ ] Rollback migration scripts tested
- [ ] Feature flags configured to disable new modules
- [ ] Monitoring dashboards ready

### Rollback Procedure
1. Enable maintenance mode
2. Run `php artisan migrate:rollback --step=5`
3. Or restore from backup if needed
4. Checkout previous stable Git tag
5. Clear caches and restart queues
6. Verify critical paths (cart, checkout, RFQ)

### Feature Flag Strategy
```php
// config/features.php
'rfq_enhanced' => env('FEATURE_RFQ_ENHANCED', false),
'bom_tool' => env('FEATURE_BOM_TOOL', false),
'customer_dashboard' => env('FEATURE_CUSTOMER_DASHBOARD', false),
```

Enable gradually: internal → beta (10-20 users) → 10% → 50% → 100%

---

## 9. Conclusion

The NeoGiga marketplace has a **solid foundation** but requires **significant enhancement** to match the blueprint requirements.

**Key Strengths:**
- Well-structured RFQ/quotation data model
- Functional BOM parsing and matching
- Regional marketplace architecture
- Modern Laravel 12 codebase

**Critical Gaps to Address:**
- Multi-line RFQ builder UI
- Full-featured BOM tool with file upload
- Customer dashboard for tracking
- Admin workflow for supplier management
- Homepage and navigation redesign

**Estimated Timeline:** 8 weeks for full implementation  
**Risk Level:** Medium (existing functionality can be preserved)  
**Confidence:** High (solid foundation, clear requirements)

---

*This audit was conducted on 2026-07-15. All findings are based on the current state of the repository.*
