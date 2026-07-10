# NeoGiga Warehouse Management System - Dubai Distribution Center

## Overview
This document outlines the implementation of the warehouse management system for NeoGiga, including the new Middle East Distribution Center in Dubai, UAE.

## 🏗️ Database Schema

### Tables Created

#### 1. `warehouses`
Main warehouse master table with location and operational details.

**Key Fields:**
- `id` (UUID) - Primary key
- `name`, `code` - Warehouse identification
- `region`, `country`, `city` - Geographic location
- `latitude`, `longitude` - GPS coordinates
- `timezone`, `currency_code` - Local settings
- `status` - active/inactive/maintenance
- `contact_info` (JSON) - Phone, email, manager details
- `operating_hours` (JSON) - Weekly schedule
- `capacity_units`, `current_stock_units` - Capacity tracking
- `is_distribution_center` - Hub for regional distribution
- `is_fulfillment_center` - Direct-to-customer fulfillment
- `allows_cross_border` - International shipments enabled

#### 2. `warehouse_products`
Product inventory tracking per warehouse.

**Key Fields:**
- `warehouse_id`, `product_id`, `product_variant_id` - Composite unique key
- `quantity_available`, `quantity_reserved`, `quantity_incoming` - Stock levels
- `reorder_level`, `reorder_quantity` - Auto-reorder triggers
- `cost_price`, `selling_price` - Pricing per warehouse
- `bin_location`, `zone` - Physical storage location
- `last_counted_at`, `last_restocked_at` - Audit timestamps

#### 3. `warehouse_shipments`
Inter-warehouse transfer tracking.

**Key Fields:**
- `shipment_number` - Auto-generated unique identifier
- `from_warehouse_id`, `to_warehouse_id` - Route
- `type` - transfer/inbound/outbound/return
- `status` - pending/in_transit/delivered/cancelled
- `carrier`, `tracking_number` - Logistics info
- `customs_documents` (JSON) - Cross-border paperwork
- `expected_departure_date`, `expected_arrival_date`
- `actual_departure_at`, `actual_arrival_at` - Actual timestamps

#### 4. `warehouse_shipment_items`
Individual items in each shipment.

**Key Fields:**
- `shipment_id`, `product_id`, `product_variant_id`
- `quantity`, `unit_cost`, `unit_price`
- `batch_number`, `expiry_date` - Batch tracking

---

## 📦 Pre-configured Warehouses

### 1. Middle East Distribution Center (Dubai, UAE)
```
Code: ME-DC-DXB-001
Location: Jebel Ali Free Zone (JAFZA), Dubai
Coordinates: 25.0118°N, 55.1203°E
Capacity: 50,000 units
Currency: AED
Timezone: Asia/Dubai
Features: 
  ✓ Distribution Center
  ✓ Fulfillment Center
  ✓ Cross-border enabled
Operating Hours: Sun-Thu 08:00-20:00, Sat 10:00-16:00, Fri Closed
Contact: +971-4-888-8888 | dubai.warehouse@neogiga.com
Manager: Ahmed Al-Mansouri
```

### 2. Nepal Main Warehouse (Kathmandu)
```
Code: NP-MW-KTM-001
Location: Birtamod Industrial Area, Jhapa
Capacity: 30,000 units
Currency: NPR
Features: 
  ✓ Distribution Center
  ✓ Fulfillment Center
  ✓ Cross-border enabled
```

### 3. Nepal Regional Warehouse (Pokhara)
```
Code: NP-RW-PKR-001
Location: Industrial Area, Pokhara
Capacity: 15,000 units
Currency: NPR
Features:
  ✓ Fulfillment Center
  ✗ Cross-border disabled (domestic only)
```

---

## 🔌 API Endpoints

### Warehouse Management
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/warehouses` | List all warehouses (with filters) |
| GET | `/api/admin/warehouses/stats` | Get warehouse statistics |
| GET | `/api/admin/warehouses/middle-east-centers` | Get Middle East DCs |
| POST | `/api/admin/warehouses` | Create new warehouse |
| GET | `/api/admin/warehouses/{id}` | Get warehouse details |
| PUT/PATCH | `/api/admin/warehouses/{id}` | Update warehouse |
| DELETE | `/api/admin/warehouses/{id}` | Delete warehouse |

### Shipment Management
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/warehouse-shipments` | List shipments (with filters) |
| POST | `/api/admin/warehouse-shipments` | Create new shipment |
| GET | `/api/admin/warehouse-shipments/{id}` | Get shipment details |
| POST | `/api/admin/warehouse-shipments/{id}/status` | Update shipment status |
| DELETE | `/api/admin/warehouse-shipments/{id}` | Cancel/delete shipment |

---

## 🔄 Business Logic

### Inventory Transfer Process
1. **Create Shipment** (Status: `pending`)
   - Admin creates transfer from Source → Destination
   - Items are NOT yet deducted from source

2. **Mark as In-Transit** (Status: `in_transit`)
   - Records actual departure timestamp
   - Inventory still at source

3. **Mark as Delivered** (Status: `delivered`)
   - Records actual arrival timestamp
   - **Automatic inventory transfer:**
     - Decrease `quantity_available` at source
     - Increase `quantity_available` at destination
     - Create new `warehouse_product` if doesn't exist

### Stock Reservation Flow
```php
// When cart item added
$warehouseProduct->reserveQuantity($qty);
// Decrements: quantity_available
// Increments: quantity_reserved

// On order completion
$warehouseProduct->completeSale($qty);
// Decrements: quantity_reserved

// On cart removal
$warehouseProduct->releaseQuantity($qty);
// Increments: quantity_available
// Decrements: quantity_reserved
```

### Low Stock Alerts
Products automatically flagged when:
```sql
quantity_available <= reorder_level
```

---

## 🚀 Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Initial Warehouses
```bash
php artisan db:seed --class=WarehouseSeeder
```

### 3. Verify Installation
```bash
# Check warehouses created
GET /api/admin/warehouses/stats

# Expected response:
{
  "success": true,
  "data": {
    "total_warehouses": 3,
    "active_warehouses": 3,
    "distribution_centers": 2,
    "middle_east_warehouses": 1,
    "uae_warehouses": 1
  }
}
```

---

## 📊 Usage Examples

### Create Inter-Warehouse Transfer
```json
POST /api/admin/warehouse-shipments
{
  "from_warehouse_id": "uuid-nepal-main",
  "to_warehouse_id": "uuid-dubai-dc",
  "type": "transfer",
  "carrier": "DHL Express",
  "tracking_number": "DHL123456789",
  "expected_departure_date": "2024-01-20",
  "expected_arrival_date": "2024-01-25",
  "customs_documents": {
    "commercial_invoice": "INV-2024-001.pdf",
    "packing_list": "PKL-2024-001.pdf",
    "certificate_of_origin": "CO-NP-2024-001.pdf"
  },
  "items": [
    {
      "product_id": "uuid-product-1",
      "product_variant_id": "uuid-variant-1",
      "quantity": 100,
      "unit_cost": 500.00,
      "batch_number": "BATCH-2024-001"
    }
  ]
}
```

### Update Shipment Status
```json
POST /api/admin/warehouse-shipments/{shipment-id}/status
{
  "status": "delivered"
}
```

### Filter Warehouses by Region
```bash
GET /api/admin/warehouses?region=Middle%20East&status=active
```

---

## 🔐 Permissions Required

All warehouse endpoints require:
- Authentication: `Bearer Token` (Laravel Sanctum)
- Role: `global_admin` or `warehouse_manager`
- Permission: `warehouse:*` or specific `warehouse:view`, `warehouse:create`, etc.

---

## 📈 Future Enhancements

- [ ] Barcode scanning integration
- [ ] Real-time stock level webhooks
- [ ] Automated reorder purchase orders
- [ ] Multi-currency pricing per warehouse
- [ ] Integration with shipping carriers (DHL, FedEx, Aramex)
- [ ] Warehouse capacity optimization algorithms
- [ ] Predictive stock allocation using AI

---

## 🛡️ Security Considerations

1. **Cross-Border Compliance**: All UAE-Nepal transfers include customs documentation
2. **Audit Trail**: All changes tracked with `created_by` / `updated_by`
3. **Soft Deletes**: No hard deletes to maintain audit history
4. **Role-Based Access**: Granular permissions per operation
5. **Rate Limiting**: Write operations throttled to prevent abuse

---

## 📞 Support

For warehouse system issues:
- Technical: tech@neogiga.com
- Operations: ops@neogiga.com
- Dubai Warehouse: dubai.warehouse@neogiga.com
- Kathmandu Warehouse: kathmandu.warehouse@neogiga.com
