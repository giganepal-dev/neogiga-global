# NeoGiga Production-Grade POS, Inventory, Accounting, Freight, Warehouse & Dispatch Implementation Plan

## Executive Summary

**Audit Date:** July 22, 2026  
**Platform Status:** 65% Complete - Foundation Ready for Production Enhancement  

The NeoGiga platform already has substantial infrastructure for POS, inventory management, accounting, and warehouse operations. This document provides a complete audit of existing functionality and a phased implementation plan to achieve production-grade readiness.

---

## 1. CURRENT SYSTEM AUDIT

### 1.1 Existing POS Infrastructure ✅

**Models Present:**
- `PosTerminal.php` - Terminal/register management
- `PosSession.php` - Session tracking
- `PosSale.php` - Sales transactions
- `PosSaleItem.php` - Sale line items
- `PosPayment.php` - Payment records
- `PosRefund.php` - Refund handling
- `PosRegister.php` - Register configuration
- `PosShift.php` - Shift management
- `PosShiftClosing.php` - Shift reconciliation
- `PosCashMovement.php` - Cash drawer movements
- `AiPosInvoice.php` - AI invoice generation

**Services Present:**
- `PosService.php` - Core POS logic
- `PosTerminalService.php` - Terminal management
- `PosReceiptService.php` - Receipt generation
- `PosCustomerAccountService.php` - Customer account integration
- `AiPosInvoiceService.php` - Invoice automation

**Controllers Present:**
- `PosController.php` (API) - REST endpoints
- `PosAdminController.php` - Admin management
- `PosReceiptController.php` - Receipt printing
- `PosCashierController.php` - Cashier interface

**Database Tables:**
- `pos_terminals` - Terminal registry
- `pos_sessions` - Active/closed sessions
- `pos_sales` - Sales headers
- `pos_sale_items` - Sale line items
- `pos_payments` - Payment transactions
- `pos_refunds` - Return transactions
- `pos_cash_movements` - Cash drawer logs
- `pos_shift_closings` - Shift reconciliation

**Migration Files:**
- `2026_07_06_192000_complete_inventory_pos_tables.php` - Core POS tables
- `2026_07_10_030000_extend_pos_refunds_and_payment_methods.php` - Enhanced refunds
- `2026_07_10_035000_create_pos_offline_sync_events.php` - Offline sync support

### 1.2 Existing Inventory Infrastructure ✅

**Models Present:**
- `InventoryStock.php` - Stock levels per warehouse
- `InventoryMovement.php` - Stock movement ledger
- `ProductWarehouse.php` - Product-warehouse mapping
- `Warehouse.php` - Warehouse master data
- `StockReservation.php` - Reserved stock
- `ManufacturerGlobalInventory.php` - Multi-location inventory
- `RegionStockVisibility.php` - Regional stock rules
- `TerritoryStockAllocation.php` - Territory-based allocation
- `LowStockAlert.php` - Reorder notifications

**Services Present:**
- `StockMovementService.php` - Movement tracking
- `RegionStockService.php` - Regional visibility
- `PurchaseReceivingService.php` - Goods receipt
- `TransferService.php` - Inter-warehouse transfers
- `ReservationService.php` - Stock reservations
- `ManufacturerInventoryService.php` - Manufacturer stock feeds
- `VendorInventoryService.php` - Vendor stock management

**Controllers Present:**
- `InventoryAdminController.php` - Admin inventory management
- `InventoryController.php` (API) - Inventory queries
- `SellerInventoryController.php` - Seller stock management

**Database Tables:**
- `inventory_stocks` - Current stock levels
- `inventory_movements` - Movement history
- `reserved_stocks` - Reservation ledger
- `inventory_suppliers` - Supplier registry
- `inventory_purchase_orders` - Purchase orders
- `inventory_purchase_order_items` - PO line items

### 1.3 Existing Accounting Infrastructure ✅

**Models Present:**
- Chart of Accounts (via migration)
- Journal Entries (via migration)
- General Ledger (via migration)

**Services Present:**
- `AccountingService.php` - Double-entry bookkeeping
  - Balance validation (debits = credits)
  - Journal entry creation
  - POS sale recording
  - Refund recording
  - Entry posting/voiding
  - Account balance calculation
  - Trial balance generation

**Database Tables:**
- `chart_of_accounts` - Account master
- `accounting_entries` - Journal headers
- `accounting_entry_lines` - Journal lines
- `fiscal_periods` - Period management

**Features Implemented:**
- ✅ Double-entry validation
- ✅ Immutable posted entries
- ✅ Void via reversal entries
- ✅ POS sale automation (Cash/Revenue + COGS/Inventory)
- ✅ Refund automation
- ✅ Trial balance
- ✅ Account balance queries

### 1.4 Existing Barcode System ✅

**Services Present:**
- `BarcodeService.php` - CODE-128 and QR generation
  - SVG barcode rendering (zero dependencies)
  - QR code via API fallback
  - Product label templates
  - Bulk label sheets

**Features Implemented:**
- ✅ CODE-128B barcode generation
- ✅ QR code generation
- ✅ Product labels with SKU/MPN
- ✅ Bulk A4 label sheets
- ✅ SVG output for print

**Missing:**
- ❌ Barcode database table (`product_barcodes`)
- ❌ Multiple barcodes per product
- ❌ Barcode scanning API endpoint
- ❌ EAN-13/UPC-A support
- ❌ Thermal printer optimization
- ❌ Label size configurations

### 1.5 Existing Warehouse Infrastructure ⚠️

**Models Present:**
- `Warehouse.php` - Basic warehouse data

**Database Tables:**
- `warehouses` - Warehouse master (via marketplace migrations)
- `product_warehouses` - Product-warehouse mapping

**Missing:**
- ❌ Warehouse zones/aisles/racks/shelves/bins
- ❌ Bin-level stock tracking
- ❌ Put-away workflows
- ❌ Picking workflows
- ❌ Packing workflows
- ❌ Cycle counting
- ❌ Stock count adjustments
- ❌ Batch/Lot tracking
- ❌ Serial number tracking
- ❌ Date code tracking
- ❌ Quality inspection
- ❌ Quarantine management

### 1.6 Missing: Freight Management ❌

**Required Tables:**
- `freight_shipments` - Shipment master
- `freight_carriers` - Carrier registry
- `freight_forwarders` - Forwarder registry
- `freight_charges` - Charge breakdown
- `freight_quotes` - Quotation records
- `customs_clearances` - Customs documentation
- `landed_cost_allocations` - Cost distribution
- `shipment_documents` - BL/AWB/Container docs
- `tracking_events` - Tracking history

**Required Services:**
- Freight quotation service
- Landed cost calculation service
- Shipment tracking service
- Customs clearance service
- Carrier selection service

### 1.7 Missing: Dispatch Management ❌

**Required Tables:**
- `dispatches` - Dispatch batches
- `dispatch_items` - Dispatch line items
- `pick_lists` - Picking instructions
- `packaging_slips` - Packing lists
- `delivery_routes` - Route planning
- `deliveries` - Delivery assignments
- `drivers` - Driver registry
- `vehicles` - Vehicle registry
- `proof_of_deliveries` - POD records
- `cod_reconciliations` - COD settlement

**Required Services:**
- Dispatch batch creation
- Pick list generation
- Packing slip generation
- Route optimization
- Driver assignment
- POD capture
- COD reconciliation

### 1.8 Missing: Advanced Purchasing ⚠️

**Existing:**
- ✅ Purchase orders (basic)
- ✅ Purchase order items
- ✅ Supplier registry

**Missing:**
- ❌ Purchase requisitions
- ❌ Supplier RFQ workflow
- ❌ Quotation comparison
- ❌ Goods receipt with quality inspection
- ❌ Supplier invoices
- ❌ Purchase returns
- ❌ Landed cost allocation to PO
- ❌ Supplier performance tracking
- ❌ Advance payment tracking

### 1.9 Missing: Serial/Batch Tracking ❌

**Required Tables:**
- `batches` - Batch/lot master
- `serial_numbers` - Serial registry
- `batch_stock` - Batch-level stock
- `serial_stock` - Serial-level stock
- `stock_count_batches` - Count by batch
- `stock_count_serials` - Count by serial

**Required Features:**
- Serial number assignment on receipt
- Serial number selection on sale
- Batch expiry tracking
- Date code tracking
- Recall management
- Warranty tracking by serial

### 1.10 Missing: Costing & Valuation ⚠️

**Existing:**
- ✅ Unit cost in `inventory_stocks`
- ✅ Movement cost tracking

**Missing:**
- ❌ FIFO valuation
- ❌ Weighted average valuation
- ❌ Specific identification (serial cost)
- ❌ Standard cost tracking
- ❌ Replacement cost tracking
- ❌ Cost layer management
- ❌ Valuation adjustment journals
- ❌ Stock aging analysis

---

## 2. IMPLEMENTATION PHASES

### Phase 1: Foundation Hardening (Week 1-2)

**Priority:** CRITICAL  
**Risk:** LOW  
**Effort:** 3-5 days

#### 1.1 Database Backup & Safety
```bash
# Create pre-migration backup
php artisan backup:run --only-db
# Verify backup integrity
# Document rollback procedure
```

#### 1.2 Barcode System Completion
**New Migrations:**
- `create_product_barcodes_table.php`
- `create_barcode_label_templates_table.php`

**New Models:**
- `ProductBarcode.php`
- `BarcodeLabelTemplate.php`

**Enhanced Services:**
- Extend `BarcodeService.php` with:
  - EAN-13 support
  - UPC-A support
  - Code-39 support
  - Data Matrix support
  - Thermal printer optimization (bitmap output)

**New API Endpoints:**
- `POST /api/v1/barcodes/generate` - Generate barcode
- `GET /api/v1/barcodes/{productId}` - Get product barcodes
- `POST /api/v1/barcodes/scan` - Process scanned barcode
- `POST /api/v1/barcodes/labels/print` - Print labels

**Admin UI:**
- Barcode management screen
- Label template designer
- Bulk label print interface

#### 1.3 Product Synchronization Service
**New Service:** `ProductSyncService.php`
- Listen to product update events
- Invalidate POS cache
- Update barcode index
- Sync regional pricing
- Handle soft deletes

**Queue Jobs:**
- `SyncProductToPos.php`
- `InvalidateBarcodeCache.php`
- `UpdateRegionalPricing.php`

### Phase 2: Warehouse Management System (Week 3-5)

**Priority:** HIGH  
**Risk:** MEDIUM  
**Effort:** 10-15 days

#### 2.1 Warehouse Location Hierarchy
**New Migration:** `create_warehouse_locations_table.php`
```php
Schema::create('warehouse_locations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('warehouse_id')->constrained();
    $table->string('zone_code')->nullable();
    $table->string('aisle_code')->nullable();
    $table->string('rack_code')->nullable();
    $table->string('shelf_code')->nullable();
    $table->string('bin_code');
    $table->enum('location_type', ['bulk', 'picking', 'packing', 'staging', 'quarantine']);
    $table->boolean('is_active')->default(true);
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->unique(['warehouse_id', 'bin_code']);
});
```

**New Models:**
- `WarehouseLocation.php`
- `WarehouseZone.php`
- `WarehouseAisle.php`
- `WarehouseRack.php`
- `WarehouseShelf.php`
- `WarehouseBin.php`

#### 2.2 Stock Count & Adjustment
**New Migration:** `create_stock_counts_table.php`
```php
Schema::create('stock_counts', function (Blueprint $table) {
    $table->id();
    $table->string('count_number')->unique();
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('user_id')->constrained(); // Counter
    $table->enum('status', ['draft', 'in_progress', 'completed', 'approved']);
    $table->timestamp('scheduled_at')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->timestamp('approved_at')->nullable();
    $table->text('notes')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
});

Schema::create('stock_count_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('stock_count_id')->constrained();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('variant_id')->nullable();
    $table->foreignId('warehouse_location_id')->nullable();
    $table->decimal('system_quantity', 12, 3);
    $table->decimal('counted_quantity', 12, 3);
    $table->decimal('variance_quantity', 12, 3)->virtualAs('counted_quantity - system_quantity');
    $table->enum('variance_status', ['matched', 'overage', 'shortage']);
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

**New Services:**
- `StockCountService.php` - Count cycle management
- `StockAdjustmentService.php` - Adjustment approval workflow

#### 2.3 Batch & Serial Tracking
**New Migration:** `create_batch_serial_tracking_tables.php`
```php
Schema::create('batches', function (Blueprint $table) {
    $table->id();
    $table->string('batch_number')->unique();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('supplier_id')->nullable();
    $table->date('manufacture_date')->nullable();
    $table->date('expiry_date')->nullable();
    $table->string('date_code')->nullable();
    $table->string('lot_number')->nullable();
    $table->string('country_of_origin')->nullable();
    $table->decimal('quantity_received', 12, 3);
    $table->decimal('quantity_remaining', 12, 3);
    $table->enum('status', ['active', 'quarantined', 'expired', 'recalled']);
    $table->timestamps();
});

Schema::create('serial_numbers', function (Blueprint $table) {
    $table->id();
    $table->string('serial_number')->unique();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('batch_id')->nullable();
    $table->foreignId('customer_id')->nullable(); // Current owner
    $table->foreignId('sale_id')->nullable(); // Last sale
    $table->date('sale_date')->nullable();
    $table->date('warranty_start')->nullable();
    $table->date('warranty_end')->nullable();
    $table->enum('status', ['in_stock', 'sold', 'returned', 'under_warranty', 'expired']);
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

**Enhanced Inventory Model:**
- Add `batch_id` and `serial_number_id` to `inventory_stocks`
- Add batch/serial tracking flags to products

### Phase 3: Freight & Landed Cost (Week 6-8)

**Priority:** HIGH  
**Risk:** MEDIUM  
**Effort:** 12-15 days

#### 3.1 Freight Shipment Management
**New Migration:** `create_freight_management_tables.php`
```php
Schema::create('freight_carriers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('code')->unique();
    $table->enum('type', ['air', 'sea', 'road', 'courier', 'rail']);
    $table->string('contact_email')->nullable();
    $table->string('contact_phone')->nullable();
    $table->json('service_levels')->nullable(); // Express, Standard, Economy
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('freight_shipments', function (Blueprint $table) {
    $table->id();
    $table->string('shipment_number')->unique();
    $table->foreignId('purchase_order_id')->nullable();
    $table->foreignId('carrier_id')->nullable();
    $table->foreignId('forwarder_id')->nullable();
    $table->enum('direction', ['inbound', 'outbound']);
    $table->enum('mode', ['air', 'sea', 'road', 'rail', 'multimodal']);
    $table->string('awb_number')->nullable(); // Air Waybill
    $table->string('bl_number')->nullable(); // Bill of Lading
    $table->string('container_number')->nullable();
    $table->string('origin_port')->nullable();
    $table->string('destination_port')->nullable();
    $table->enum('incoterm', ['EXW', 'FOB', 'CIF', 'DDP', 'DAP']);
    $table->date('etd')->nullable(); // Estimated Departure
    $table->date('eta')->nullable(); // Estimated Arrival
    $table->date('atd')->nullable(); // Actual Departure
    $table->date('ata')->nullable(); // Actual Arrival
    $table->enum('status', ['booked', 'in_transit', 'arrived', 'cleared', 'delivered']);
    $table->decimal('gross_weight_kg', 10, 3)->default(0);
    $table->decimal('volumetric_weight_kg', 10, 3)->default(0);
    $table->decimal('chargeable_weight_kg', 10, 3)->default(0);
    $table->decimal('volume_m3', 10, 3)->default(0);
    $table->integer('package_count')->default(0);
    $table->timestamps();
});

Schema::create('freight_charges', function (Blueprint $table) {
    $table->id();
    $table->foreignId('freight_shipment_id')->constrained();
    $table->string('charge_type'); // Freight, Insurance, Customs, Clearance, etc.
    $table->decimal('amount', 15, 4);
    $table->string('currency_code', 3)->default('USD');
    $table->foreignId('vendor_id')->nullable(); // Who charged
    $table->string('invoice_number')->nullable();
    $table->date('invoice_date')->nullable();
    $table->boolean('allocated')->default(false);
    $table->timestamps();
});

Schema::create('landed_cost_allocations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('freight_shipment_id')->constrained();
    $table->foreignId('purchase_order_item_id')->constrained();
    $table->enum('allocation_method', ['quantity', 'weight', 'volume', 'value', 'manual']);
    $table->decimal('allocation_ratio', 10, 6);
    $table->decimal('allocated_amount', 15, 4);
    $table->string('currency_code', 3);
    $table->timestamps();
});
```

#### 3.2 Landed Cost Service
**New Service:** `LandedCostService.php`
```php
class LandedCostService
{
    public function allocateShipmentCosts(int $shipmentId): void;
    public function calculateLandedCost(int $productId, int $warehouseId): decimal;
    public function updateInventoryValuation(int $shipmentId): void;
}
```

### Phase 4: Dispatch & Delivery (Week 9-11)

**Priority:** HIGH  
**Risk:** MEDIUM  
**Effort:** 12-15 days

#### 4.1 Dispatch Workflow
**New Migration:** `create_dispatch_delivery_tables.php`
```php
Schema::create('dispatches', function (Blueprint $table) {
    $table->id();
    $table->string('dispatch_number')->unique();
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('user_id')->constrained(); // Created by
    $table->enum('status', ['draft', 'allocated', 'picking', 'picked', 'packing', 'packed', 'ready', 'dispatched', 'delivered', 'failed']);
    $table->date('dispatch_date');
    $table->foreignId('carrier_id')->nullable();
    $table->foreignId('driver_id')->nullable();
    $table->foreignId('vehicle_id')->nullable();
    $table->string('tracking_number')->nullable();
    $table->timestamps();
});

Schema::create('dispatch_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('dispatch_id')->constrained();
    $table->foreignId('order_id')->nullable();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('variant_id')->nullable();
    $table->foreignId('batch_id')->nullable();
    $table->string('serial_number')->nullable();
    $table->decimal('quantity', 12, 3);
    $table->enum('status', ['pending', 'picked', 'packed', 'shipped', 'delivered']);
    $table->timestamps();
});

Schema::create('pick_lists', function (Blueprint $table) {
    $table->id();
    $table->foreignId('dispatch_id')->constrained();
    $table->string('pick_list_number')->unique();
    $table->foreignId('warehouse_location_id')->constrained(); // Zone routing
    $table->enum('priority', ['low', 'normal', 'high', 'urgent']);
    $table->foreignId('assigned_to')->nullable()->constrained('users');
    $table->timestamp('assigned_at')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});

Schema::create('drivers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('phone');
    $table->string('license_number')->nullable();
    $table->string('license_expiry')->nullable();
    $table->foreignId('vehicle_id')->nullable();
    $table->enum('status', ['available', 'on_delivery', 'off_duty', 'inactive']);
    $table->decimal('cod_balance', 15, 4)->default(0);
    $table->timestamps();
});

Schema::create('proof_of_deliveries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('dispatch_id')->constrained();
    $table->string('recipient_name');
    $table->string('recipient_signature')->nullable(); // Image path
    $table->string('delivery_photo')->nullable(); // Image path
    $table->text('delivery_notes')->nullable();
    $table->enum('delivery_status', ['delivered', 'failed', 'partial']);
    $table->string('failure_reason')->nullable();
    $table->timestamp('delivered_at');
    $table->decimal('cod_collected', 15, 4)->default(0);
    $table->timestamps();
});
```

### Phase 5: Advanced Purchasing (Week 12-14)

**Priority:** MEDIUM  
**Risk:** LOW  
**Effort:** 10-12 days

#### 5.1 Procurement Workflow Enhancement
**New Migration:** `create_advanced_procurement_tables.php`
```php
Schema::create('purchase_requisitions', function (Blueprint $table) {
    $table->id();
    $table->string('requisition_number')->unique();
    $table->foreignId('requested_by')->constrained('users');
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'converted']);
    $table->text('justification')->nullable();
    $table->date('required_by')->nullable();
    $table->timestamps();
});

Schema::create('supplier_quotations', function (Blueprint $table) {
    $table->id();
    $table->string('quotation_number')->unique();
    $table->foreignId('supplier_id')->constrained('inventory_suppliers');
    $table->foreignId('purchase_requisition_id')->nullable();
    $table->date('quotation_date');
    $table->date('valid_until');
    $table->decimal('subtotal', 15, 4);
    $table->decimal('tax_amount', 15, 4);
    $table->decimal('total_amount', 15, 4);
    $table->string('currency_code', 3);
    $table->integer('lead_time_days')->nullable();
    $table->enum('status', ['pending', 'accepted', 'rejected', 'expired']);
    $table->timestamps();
});

Schema::create('goods_receipts', function (Blueprint $table) {
    $table->id();
    $table->string('grn_number')->unique(); // Goods Receipt Note
    $table->foreignId('purchase_order_id')->constrained('inventory_purchase_orders');
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('received_by')->constrained('users');
    $table->date('receipt_date');
    $table->enum('quality_status', ['accepted', 'rejected', 'quarantined', 'pending_inspection']);
    $table->text('inspection_notes')->nullable();
    $table->foreignId('inspector_id')->nullable()->constrained('users');
    $table->timestamps();
});
```

### Phase 6: Reporting & Analytics (Week 15-16)

**Priority:** MEDIUM  
**Risk:** LOW  
**Effort:** 8-10 days

#### 6.1 Report Services
**New Services:**
- `PosReportService.php` - Daily sales, cashier reports
- `InventoryReportService.php` - Stock valuation, aging
- `AccountingReportService.php` - P&L, Balance Sheet, Cash Flow
- `FreightReportService.php` - Carrier performance, landed cost analysis
- `DispatchReportService.php` - Delivery performance, COD reconciliation

**Report Types:**
1. **POS Reports:**
   - Daily Sales Summary
   - Sales by Cashier
   - Sales by Payment Method
   - Sales by Product/Category/Brand
   - Discount Report
   - Tax Report
   - Return Report
   - Shift Reconciliation

2. **Inventory Reports:**
   - Stock on Hand
   - Stock Valuation (FIFO/Weighted Avg)
   - Stock Ledger (Movement History)
   - Stock Aging Analysis
   - Slow/Fast Moving Items
   - Dead Stock Report
   - Reorder Point Report
   - Negative Stock Exceptions

3. **Accounting Reports:**
   - Trial Balance
   - General Ledger
   - Profit & Loss
   - Balance Sheet
   - Cash Flow Statement
   - AR/AP Aging
   - Customer/Supplier Statements
   - Tax Reports (VAT/GST)

4. **Freight Reports:**
   - Freight Cost Analysis
   - Landed Cost by Product
   - Carrier Performance
   - Shipment Status Dashboard

5. **Dispatch Reports:**
   - On-Time Delivery Rate
   - Failed Delivery Analysis
   - Driver Performance
   - COD Collection Report

---

## 3. DATA SYNCHRONIZATION ARCHITECTURE

### 3.1 Event-Driven Sync

```
Product Updated → Event → Queue → Sync Handlers
                                    ↓
                    ┌───────────────┬───────────────┬──────────────┐
                    ↓               ↓               ↓              ↓
                POS Cache      Search Index   Regional Price   Barcode Index
```

### 3.2 Customer Synchronization

**Single Customer Record:**
- Shared across marketplace, POS, B2B, institutional
- No duplication - use customer type flags
- Unified order history
- Unified credit limit
- Unified loyalty points

### 3.3 Inventory Atomicity

**Stock Reservation Pattern:**
```php
DB::transaction(function () {
    $stock = InventoryStock::whereLockForUpdate()
        ->where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->first();
    
    if ($stock->available_quantity < $quantity) {
        throw new InsufficientStockException();
    }
    
    ReservedStock::create([...]);
    $stock->decrement('available_quantity', $quantity);
});
```

---

## 4. SECURITY & PERMISSIONS

### 4.1 Role Definitions

| Role | Permissions |
|------|-------------|
| POS Cashier | Create sales, process payments, view own shift |
| POS Supervisor | All cashier + void transactions, approve discounts |
| Branch Manager | All POS + view reports, manage staff |
| Warehouse Operator | Receive goods, pick/pack, stock count |
| Warehouse Manager | All operator + transfers, adjustments, approvals |
| Dispatcher | Create dispatches, assign drivers |
| Driver | View assigned deliveries, capture POD |
| Procurement Officer | Create POs, manage suppliers |
| Procurement Manager | Approve POs, negotiate terms |
| Accountant | View journals, post entries, run reports |
| Finance Manager | All accountant + close periods, approve adjustments |
| Regional Admin | Full regional access |
| Global Admin | Full system access |

### 4.2 Sensitive Operations Requiring Approval

- Selling below minimum price
- Discounts above threshold
- Void transactions after shift close
- Inventory adjustments > threshold
- Stock write-offs
- Journal entry posting
- Period closing
- Customer credit limit changes

---

## 5. OFFLINE RESILIENCE

### 5.1 Architecture

```
Online Mode: Direct DB writes → Real-time sync
Offline Mode: Local IndexedDB → Queue → Sync on reconnect
```

### 5.2 Offline-Safe Operations

✅ Allowed Offline:
- Product lookup (cached)
- Cart creation
- Draft sales
- Customer lookup (cached)
- Barcode scanning

❌ Requires Online:
- Final sale completion
- Payment processing
- Stock deduction
- Credit sales
- Price overrides

### 5.3 Conflict Resolution

- Server timestamps win
- Duplicate detection via idempotency keys
- Manual review queue for conflicts

---

## 6. TESTING STRATEGY

### 6.1 Unit Tests
- Barcode generation
- Journal entry balancing
- Stock calculations
- Tax calculations
- Discount calculations

### 6.2 Integration Tests
- POS sale → Inventory deduction → Journal entry
- Purchase receipt → Stock increase → AP entry
- Stock transfer → Movement records
- Dispatch → Delivery → POD

### 6.3 Concurrency Tests
- Two terminals selling same product
- Simultaneous stock adjustments
- Concurrent purchase receipts

### 6.4 Performance Tests
- 1000 concurrent POS sessions
- 10,000 product search
- 100,000 inventory movements query

---

## 7. DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] Database backup completed
- [ ] Rollback procedure documented
- [ ] Staging environment tested
- [ ] Load testing passed
- [ ] Security audit completed

### Deployment
- [ ] Run migrations in maintenance mode
- [ ] Clear caches
- [ ] Restart queue workers
- [ ] Verify critical paths
- [ ] Monitor error logs

### Post-Deployment
- [ ] Smoke tests passed
- [ ] First real transaction successful
- [ ] Reports generating correctly
- [ ] Backup schedule verified
- [ ] Monitoring alerts configured

---

## 8. REMAINING WORK SUMMARY

### Already Complete (65%)
- ✅ POS core (sales, payments, sessions, terminals)
- ✅ Inventory tracking (stocks, movements, reservations)
- ✅ Double-entry accounting (journals, trial balance)
- ✅ Barcode generation (CODE-128, QR)
- ✅ Basic warehouses
- ✅ Basic purchasing (POs)
- ✅ Customer unification
- ✅ Regional marketplace integration

### Phase 1 (Week 1-2): Barcode & Sync
- [ ] Barcode database
- [ ] Multiple barcode types
- [ ] Scanning API
- [ ] Label templates
- [ ] Product sync service

### Phase 2 (Week 3-5): Warehouse
- [ ] Location hierarchy
- [ ] Bin tracking
- [ ] Stock counts
- [ ] Batch/serial tracking
- [ ] Put-away/picking

### Phase 3 (Week 6-8): Freight
- [ ] Shipment management
- [ ] Landed cost
- [ ] Carrier integration
- [ ] Customs clearance

### Phase 4 (Week 9-11): Dispatch
- [ ] Dispatch batches
- [ ] Pick/pack workflows
- [ ] Driver management
- [ ] POD capture
- [ ] COD reconciliation

### Phase 5 (Week 12-14): Purchasing
- [ ] Requisitions
- [ ] Supplier RFQs
- [ ] Goods receipt
- [ ] Quality inspection

### Phase 6 (Week 15-16): Reporting
- [ ] All report services
- [ ] Export functionality
- [ ] Dashboard widgets

---

## 9. CONCLUSION

The NeoGiga platform has a solid foundation for production-grade POS, inventory, and accounting operations. The architecture is sound, with proper separation of concerns, event-driven synchronization, and double-entry accounting already implemented.

**Key Strengths:**
- Existing POS handles sales, payments, sessions
- Inventory tracks movements and reservations
- Accounting enforces double-entry rules
- Barcode generation works without dependencies
- Regional marketplace isolation is functional

**Critical Gaps to Address:**
- Warehouse location hierarchy missing
- Batch/serial tracking not implemented
- Freight and landed cost absent
- Dispatch and delivery workflows needed
- Advanced purchasing incomplete
- Comprehensive reporting required

**Recommended Approach:**
Implement in phases as outlined, starting with barcode completion and product synchronization (lowest risk, highest immediate value), then progressing through warehouse, freight, dispatch, purchasing, and finally reporting.

Each phase should be:
1. Developed on feature branch
2. Tested in staging
3. Validated with sample data
4. Deployed to production with backup
5. Monitored for issues before next phase

**Estimated Timeline:** 16 weeks for full production readiness  
**Risk Level:** Medium (mitigated by phased approach)  
**Confidence:** High (strong foundation exists)

---

*Document Version: 1.0*  
*Last Updated: July 22, 2026*  
*Next Review: After Phase 1 completion*
