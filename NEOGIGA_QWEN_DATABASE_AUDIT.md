# NeoGiga Database and Migration Audit Report

**Generated:** 2026-07-08  
**Auditor:** Qwen Code Audit System  
**Purpose:** Database schema and migration analysis

---

## A. Migration Summary

| Metric | Count |
|--------|-------|
| Total migrations | 132 |
| Marketplace migrations | ~95 (in marketplace/ subdirectory) |
| IoT/Device migrations | ~27 (legacy) |
| ERP/B2B migrations | ~10 |
| Marketing migrations | Included in count |

---

## B. Existing Tables by Domain

### Core Auth & System
```
users                       - User accounts
roles                       - Role-based access control
password_reset_tokens       - Password resets
sessions                    - Session storage
cache                       - Cache store
jobs                        - Job queue
failed_jobs                 - Failed job tracking
```

### Nepal Geography (Preserved)
```
provinces                   - Nepal provinces
districts                   - Districts (FK to provinces)
municipalities              - Municipalities (FK to districts)
wards                       - Wards (FK to municipalities)
customers                   - Customer records
```

### IoT/Device Modules (Preserved)
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

### Multi-Country Marketplace
```
countries                   - Country definitions
currencies                  - Currency definitions
marketplaces                - Marketplace entities
marketplace_domains         - Domain mappings
marketplace_settings        - Marketplace settings
regions                     - Geographic regions
cities                      - Cities
tax_zones                   - Tax zones
delivery_zones              - Delivery zones
```

### Product Catalog
```
product_categories          - Category tree
product_category_translations - Category translations
product_brands              - Brand definitions
products                    - Product master table
product_variants            - Product variants
product_spec_groups         - Specification groups
product_specs               - Product specifications
product_images              - Product images
product_documents           - Product documents (datasheets)
product_videos              - Product videos
product_compatibility       - Compatible products
product_related_items       - Related products
product_bom_items           - BOM items
product_lms_links           - LMS course links
product_seo_meta            - SEO metadata
product_approval_status     - Approval status tracking
marketplace_products        - Marketplace-product visibility
```

### Vendor System
```
vendors                     - Vendor master
vendor_profiles             - Vendor profiles
vendor_marketplace_approvals - Marketplace approvals
vendor_warehouses           - Vendor warehouse assignments
vendor_documents            - Vendor documents
vendor_staff                - Vendor staff users
vendor_payout_methods       - Payout method definitions
vendor_audit_logs           - Vendor activity logs
vendor_ratings              - Vendor ratings/reviews
vendor_inventory            - Vendor inventory view
vendor_product_prices       - Vendor pricing
vendor_orders               - Vendor orders
vendor_order_items          - Vendor order items
vendor_payouts              - Vendor payouts (may be incomplete)
vendor_payout_items         - Payout line items
```

### Inventory Management
```
warehouses                  - Warehouse definitions
warehouse_locations         - Warehouse locations/bins
inventory_stocks            - Stock levels by warehouse
inventory_movements         - Stock movement history
reserved_stocks             - Reserved/pending stock
damaged_stocks              - Damaged/defective stock
incoming_stocks             - Incoming/purchase order stock
vendor_inventory            - Vendor-specific inventory view
regional_inventory_visibility - Regional visibility flags
```

### Pricing & Tax
```
marketplace_product_prices  - Marketplace-specific prices
vendor_product_prices       - Vendor-specific prices
bulk_price_tiers            - Quantity discount tiers
currency_exchange_rates     - Exchange rate history
tax_rules                   - Tax rule definitions
import_duty_rules           - Import duty calculations
shipping_fee_rules          - Shipping fee calculations
```

### Cart & Orders
```
carts                       - Shopping carts
cart_items                  - Cart line items
orders                      - Order master
order_items                 - Order line items
order_status_history        - Status change history
invoices                    - Invoice records
invoice_items               - Invoice line items
payments                    - Payment records
refunds                     - Refund records
shipments                   - Shipment records
shipment_tracking           - Tracking information
returns                     - Return requests
return_items                - Return line items
warranty_claims             - Warranty claims
```

### AI Commerce
```
ai_sessions                 - AI conversation sessions
ai_messages                 - AI messages
ai_product_recommendations  - AI recommendations
ai_bom_builds               - AI-generated BOMs
ai_bom_items                - AI BOM line items
ai_cart_actions             - AI cart actions
ai_pos_invoices             - AI POS invoices
ai_lms_recommendations      - AI LMS recommendations
ai_sample_code_snippets     - AI code samples
```

### POS System
```
pos_terminals               - POS terminal definitions
pos_sessions                - POS session records
pos_sales                   - POS sales records
pos_sale_items              - POS sale line items
pos_payments                - POS payment records
pos_refunds                 - POS refund records
pos_cash_movements          - POS cash drawer movements
pos_shift_closings          - Shift closing records
```

### LMS Integration
```
lms_courses                 - Course definitions
lms_modules                 - Course modules
lms_lessons                 - Lesson records
lms_projects                - Project definitions
lms_project_components      - Project components/parts
lms_code_samples            - Code sample library
lms_product_links           - Product-course links
lms_enrollments             - Student enrollments
lms_progress_events         - Progress tracking
lms_certificates            - Certificate records
lms_assignments             - Assignments
lms_assignment_submissions  - Assignment submissions
lms_quizzes                 - Quiz definitions
lms_quiz_questions          - Quiz questions
lms_quiz_attempts           - Quiz attempts
```

### ERP/Procurement
```
suppliers                   - Supplier definitions
purchase_orders             - Purchase orders
purchase_order_items        - PO line items
rfq_requests                - RFQ requests
rfq_items                   - RFQ line items
quotations                  - Quotations
quotation_items             - Quotation items
expenses                    - Expense records
document_number_sequences   - Document numbering
```

### Marketing & Promotions
```
affiliates                  - Affiliate records
referral_codes              - Referral code definitions
referral_attributions       - Attribution tracking
commission_rules            - Commission rules
commission_ledger           - Commission ledger
payout_requests             - Payout requests
payout_batches              - Payout batch processing
coupons                     - Coupon definitions
coupon_uses                 - Coupon usage tracking
gift_cards                  - Gift card definitions
gift_card_transactions      - Gift card transactions
```

---

## C. Missing Expected Tables

| Table | Priority | Reason |
|-------|----------|--------|
| `distributors` | P1 | Distributor network missing |
| `distributor_profiles` | P1 | Profile data needed |
| `distributor_territories` | P1 | Territory assignment |
| `distributor_downlines` | P2 | MLM structure |
| `distributor_leads` | P1 | Lead management |
| `distributor_customers` | P1 | Customer assignment |
| `distributor_orders` | P1 | Order attribution |
| `distributor_commission_rules` | P1 | Commission calculation |
| `distributor_commissions` | P1 | Commission tracking |
| `distributor_payouts` | P1 | Payout processing |
| `distributor_activity_logs` | P2 | Activity tracking |
| `b2b_accounts` | P2 | B2B account layer |
| `b2b_account_users` | P2 | Account user management |
| `b2b_price_lists` | P2 | B2B pricing |
| `b2b_quote_requests` | P2 | B2B quotes |
| `bom_projects` | P2 | BOM project-commerce |
| `bom_project_items` | P2 | BOM line items |
| `bom_user_builds` | P3 | User BOM builds |
| `bom_cart_conversions` | P3 | BOM-to-cart tracking |
| `vendor_reviews` | P2 | Detailed reviews (separate from ratings) |
| `vendor_branches` | P3 | Vendor branch locations |
| `vendor_roles` | P3 | Vendor-specific roles |
| `vendor_permissions` | P3 | Vendor permissions |
| `vendor_commission_rules` | P2 | Vendor commission |

---

## D. Schema Risks

### Unsafe Cascade Risks
```
⚠️ Need to verify foreign key CASCADE DELETE rules
⚠️ products deletion could orphan reviews/images/specs
⚠️ vendors deletion could orphan products/orders
⚠️ warehouses deletion could orphan inventory
```

### Missing Indexes (Likely)
```
⚠️ products.slug - Should be indexed for lookups
⚠️ products.category_id - FK index
⚠️ products.brand_id - FK index
⚠️ vendors.user_id - FK index
⚠️ inventory_stocks.product_id - FK index
⚠️ inventory_stocks.warehouse_id - FK index
⚠️ orders.user_id - FK index
⚠️ order_items.order_id - FK index
```

### Missing Foreign Keys (Possible)
```
❓ Need to verify all relationships have proper FK constraints
❓ Some tables may use soft references without FK
```

### Stock/Inventory Schema Risks
```
✅ inventory_stocks has warehouse_id, product_id
✅ inventory_movements tracks changes
✅ reserved_stocks prevents overselling
⚠️ Need to verify atomic operations for stock updates
⚠️ Need to verify no race conditions in reserve/release
```

### Product Attribute/Spec Schema Risks
```
✅ product_spec_groups organizes specs
✅ product_specs stores individual specs
✅ product_variants handles variants
⚠️ No generic attributes table found (key/value pairs)
⚠️ No option values table for product options
```

### Vendor/Distributor Schema Risks
```
✅ vendors table exists with status
✅ vendor_marketplace_approvals for multi-marketplace
⚠️ No dedicated distributor tables
⚠️ vendor_payouts may be incomplete
```

### Warranty/Datasheet Schema Risks
```
❓ product_documents table status unclear
❓ warranty fields in products table need verification
❓ country_of_origin field needs verification
```

### Auth/User Schema Risks
```
✅ users table standard Laravel
✅ roles table with JSON permissions
⚠️ No separate seller/distributor user types
⚠️ Uses role_id for differentiation
```

### Migration Naming Conflicts
```
✅ All marketplace migrations properly prefixed
✅ IoT migrations preserved with original timestamps
✅ No obvious naming conflicts detected
```

---

## E. Region-Wise Stock Support

| Requirement | Schema Support | Status |
|-------------|----------------|--------|
| Country-wise stock | `countries` + `regional_inventory_visibility` | ✅ Supported |
| Marketplace-wise stock | `marketplaces` + visibility table | ✅ Supported |
| Region/province stock | `regions` table exists | ✅ Supported |
| City-wise stock | `cities` table exists | ✅ Supported |
| Warehouse-wise stock | `warehouses` + `inventory_stocks` | ✅ Supported |
| Seller-wise stock | `vendor_inventory` view | ✅ Supported |
| Distributor territory | ❌ No tables | 🔴 Missing |
| Inventory movements | `inventory_movements` | ✅ Complete |
| Reservations | `reserved_stocks` | ✅ Complete |
| Low stock alerts | ❌ No alert table | 🔴 Missing |
| Backorder support | ❌ No backorder field | 🔴 Missing |
| Stock adjustments | ❌ No adjustment table | 🔴 Missing |
| Stock transfers | `TransferService` exists | 🟡 Service only |

---

## F. Per-Marketplace Vendor Approval

| Requirement | Schema Support | Status |
|-------------|----------------|--------|
| Vendor exists globally | `vendors` table | ✅ Supported |
| Per-marketplace approval | `vendor_marketplace_approvals` | ✅ Complete |
| Approval status tracking | `status` field in approvals | ✅ Complete |
| Approval workflow | ❌ No workflow table | 🔴 Logic missing |
| Rejection reasons | ❌ No rejection table | 🔴 Missing |
| Approval audit log | `vendor_audit_logs` | ✅ Supported |

---

## G. Product Variants/Options/Spec Templates

| Requirement | Schema Support | Status |
|-------------|----------------|--------|
| Product variants | `product_variants` | ✅ Complete |
| Variant options | ❌ No option values table | 🔴 Missing |
| Size/color/voltage | ❌ No dedicated fields | 🔴 Missing |
| Category spec templates | `product_spec_groups` | 🟡 Partial |
| Spec inheritance | ❌ No template system | 🔴 Missing |
| Spec validation | ❌ No validation rules | 🔴 Missing |

---

*Database audit completed by Qwen Code Audit System - 2026-07-08*
