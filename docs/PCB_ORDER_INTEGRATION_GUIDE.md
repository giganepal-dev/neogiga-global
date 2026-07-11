# PCB Order Integration Guide

## 1. Executive Summary

This guide defines how PCB projects, quotes, and manufacturing requirements integrate with the **existing NeoGiga cart and order system**.

**Core Principle:** Do not create isolated PCB orders. Every PCB/PCBA purchase must be a standard NeoGiga order referencing PCB-specific metadata. This ensures unified accounting, customer history, and fulfillment tracking.

## 2. Architecture Overview

### 2.1 Order Flow
1. **Quote Approval:** Customer approves PCB/PCBA quote.
2. **Cart Conversion:** System creates cart items linked to PCB project.
3. **Checkout:** Standard NeoGiga checkout (shipping, payment).
4. **Order Creation:** Standard `orders` record created.
5. **PCB Linkage:** `pcb_order_links` table connects order to project/files.
6. **Fulfillment:** Supplier portal receives production instructions.
7. **Tracking:** Production updates flow back to order status.

### 2.2 Integration Points
- **Existing:** `carts`, `cart_items`, `orders`, `order_items`, `invoices`, `payments`.
- **New:** `pcb_order_links`, `pcb_production_events`.

## 3. Database Schema Extensions

### 3.1 PCB Order Links
Connects standard orders to PCB projects.

```sql
CREATE TABLE pcb_order_links (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    order_item_id UUID REFERENCES order_items(id), -- Specific line item if split
    
    pcb_project_id UUID NOT NULL REFERENCES pcb_projects(id),
    project_version_id UUID REFERENCES pcb_project_versions(id),
    
    -- Linked Resources
    gerber_file_version_id UUID REFERENCES pcb_file_versions(id),
    bom_version_id UUID REFERENCES bom_versions(id), -- Existing BOM system
    cpl_version_id UUID REFERENCES pcb_cpl_imports(id),
    
    -- Configuration Snapshot
    pcb_config_json JSONB, -- Layers, material, finish, etc.
    pcba_config_json JSONB, -- Assembly options, testing, etc.
    
    -- Supplier Assignment
    fabrication_supplier_id UUID REFERENCES suppliers(id),
    assembly_supplier_id UUID REFERENCES suppliers(id),
    component_supplier_id UUID REFERENCES suppliers(id),
    
    -- Quote Reference
    rfq_quote_id UUID REFERENCES pcb_rfq_quotes(id),
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_pcb_order_link ON pcb_order_links(order_id);
CREATE INDEX idx_pcb_order_project ON pcb_order_links(pcb_project_id);
```

### 3.2 Split Purchase Orders
For turnkey orders involving multiple suppliers.

```sql
CREATE TABLE pcb_order_splits (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    parent_order_id UUID NOT NULL REFERENCES orders(id),
    
    split_type VARCHAR(50), -- 'fabrication', 'assembly', 'components', 'stencil'
    supplier_id UUID REFERENCES suppliers(id),
    
    split_order_number VARCHAR(100), -- Supplier-facing PO number
    split_amount DECIMAL(12,2),
    currency CHAR(3) DEFAULT 'USD',
    
    status VARCHAR(50) DEFAULT 'pending', -- pending, sent, confirmed, shipped, completed
    
    notes TEXT,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_order_split_parent ON pcb_order_splits(parent_order_id);
```

## 4. Cart Conversion Logic

### 4.1 Single-Supplier Flow
```php
class PcbCartConverter
{
    public function convert(ApprovedQuote $quote): Order
    {
        // 1. Create standard cart items
        $cartItem = CartItem::create([
            'product_type' => 'pcb_service',
            'description' => "PCB Fabrication + Assembly - {$quote->project->code}",
            'quantity' => $quote->quantity,
            'unit_price' => $quote->total_price,
            'metadata' => [
                'pcb_project_id' => $quote->project->id,
                'quote_id' => $quote->id,
                'config_snapshot' => $quote->configuration
            ]
        ]);

        // 2. Process checkout (standard NeoGiga flow)
        $order = CheckoutService::process($cartItem);

        // 3. Create PCB linkage
        PcbOrderLink::create([
            'order_id' => $order->id,
            'pcb_project_id' => $quote->project->id,
            'gerber_file_version_id' => $quote->approved_gerber_id,
            'bom_version_id' => $quote->approved_bom_id,
            'pcb_config_json' => $quote->configuration
        ]);

        return $order;
    }
}
```

### 4.2 Multi-Supplier Split Flow
For turnkey orders:
1. **Fabrication Line:** PCB bare board → Fab Supplier.
2. **Assembly Line:** PCBA service → Assembly Supplier.
3. **Component Lines:** Individual parts → Component Suppliers (or consolidated).

System creates one customer-facing order with multiple internal split POs.

## 5. Order Item Metadata

PCB order items include extended metadata:

```json
{
  "service_type": "pcb_turnkey",
  "project_code": "NEO-PCB-2024-001",
  "board_specs": {
    "layers": 4,
    "dimensions": "100x80mm",
    "material": "FR-4 TG135",
    "thickness": "1.6mm",
    "copper": "1oz",
    "finish": "ENIG"
  },
  "assembly_specs": {
    "smt_side": "both",
    "joint_count": 145,
    "testing": ["AOI", "Flying Probe"]
  },
  "files": {
    "gerber_version": "v3.2",
    "bom_version": "v2.1",
    "cpl_version": "v2.1"
  },
  "supplier_ids": {
    "fabrication": "uuid-fab-001",
    "assembly": "uuid-asm-001"
  },
  "dfm_approved": true,
  "engineering_notes": "Impedance control verified"
}
```

## 6. API Endpoints

### 6.1 Convert Quote to Cart
`POST /api/v1/pcb/quotes/{quote_id}/convert-to-cart`

- **Action:** Creates cart items with PCB metadata.
- **Response:** `{ "cart_id": "uuid", "items": [...] }`

### 6.2 Get Order PCB Details
`GET /api/v1/orders/{order_id}/pcb-details`

- **Response:** Full PCB project linkage, file versions, supplier assignments.

### 6.3 Create Split PO
`POST /api/v1/pcb/orders/{order_id}/split`

- **Body:** `{ "type": "assembly", "supplier_id": "uuid", "amount": 1500.00 }`
- **Permission:** Admin or fulfillment manager.

## 7. Fulfillment Integration

### 7.1 Supplier Notification
When order is paid:
1. System generates supplier-specific packing list.
2. Sends notification via supplier portal + email.
3. Provides secure download links for Gerber/BOM/CPL.
4. Sets deadline based on lead time.

### 7.2 Production Updates
Suppliers update status via portal:
- `order_confirmed`
- `materials_prepared`
- `fabrication_started`
- `assembly_started`
- `quality_inspection`
- `shipped`

Each update triggers:
- `pcb_production_events` log entry.
- Customer notification (optional).
- Order timeline update.

## 8. Accounting Integration

### 8.1 Cost Allocation
Order item costs are split for margin calculation:

| Cost Type | Account | Source |
|-----------|---------|--------|
| Fabrication | COGS - PCB | Supplier quote |
| Assembly | COGS - PCBA | Supplier quote |
| Components | COGS - Parts | BOM match prices |
| Freight | COGS - Freight | Shipping quote |
| Duty | COGS - Duty | Tax engine |
| Revenue | Sales - PCB Services | Customer price |

### 8.2 Margin Tracking
```php
$grossProfit = $orderItem->total - (
    $pcbLink->fab_cost + 
    $pcbLink->assembly_cost + 
    $pcbLink->component_cost + 
    $pcbLink->freight_cost
);
```

## 9. Customer Experience

### 9.1 Order History
Customers see:
- Standard order details (date, total, shipping).
- PCB-specific tab: "Manufacturing Details".
- Links to project workspace, files, production timeline.

### 9.2 Reorder Flow
- "Reorder this PCB" button clones project to new version.
- Preserves all specs, allows quantity/config changes.
- Skips DFM if no changes made (fast-track).

## 10. Security & Validation

- **Authorization:** Only order owners and admins can view PCB details.
- **File Access:** Download links expire; re-authentication required.
- **Audit:** All split POs logged with user ID and justification.
- **Validation:** Prevent order completion if required files missing.

## 11. Testing Strategy

- **Integration Tests:** Quote → Cart → Order → PCB Link flow.
- **Split Order Tests:** Verify cost allocation across suppliers.
- **Accounting Tests:** Confirm margin calculations match expectations.
- **Regression Tests:** Ensure standard NeoGiga orders unaffected.

## 12. Deployment Checklist

- [ ] Create `pcb_order_links` and `pcb_order_splits` tables.
- [ ] Extend cart item schema for PCB metadata (if needed).
- [ ] Update checkout flow to preserve PCB data.
- [ ] Configure supplier notification templates.
- [ ] Test end-to-end with sample PCB order.
- [ ] Verify accounting integration (cost/revenue mapping).

## 13. Edge Cases

- **Partial Cancellation:** Customer cancels assembly but keeps fab? → Requires admin intervention.
- **Component Shortage:** Some parts unavailable → Split shipment or hold order?
- **DFM Failure Post-Order:** Engineering finds issue after payment → Refund or redesign RFQ?
- **Multi-Currency:** Supplier costs in CNY, customer pays USD → FX risk handling.
