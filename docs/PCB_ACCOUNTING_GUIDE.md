# PCB Accounting Integration Guide

## 1. Executive Summary

This guide defines how PCB manufacturing costs, revenues, and margins integrate with the **existing NeoGiga accounting system**.

**Core Principle:** Use immutable order-line cost and price snapshots. Do not expose purchase costs or margins publicly. All PCB-specific costs must map to standard NeoGiga general ledger accounts.

## 2. Architecture Overview

### 2.1 Cost Flow
1. **Quote Stage:** Supplier provides cost breakdown (fab, assembly, components).
2. **Order Stage:** Costs locked in `pcb_order_links` snapshot.
3. **Invoice Stage:** Customer invoiced at selling price.
4. **Settlement Stage:** Suppliers paid at cost price.
5. **Reconciliation:** Margin calculated as difference.

### 2.2 Integration Points
- **Existing:** `orders`, `order_items`, `invoices`, `payments`, `accounting_entries`, `supplier_settlements`.
- **New:** `pcb_cost_snapshots`, `pcb_margin_calculations`.

## 3. Database Schema Extensions

### 3.1 Cost Snapshots
Immutable record of costs at order time.

```sql
CREATE TABLE pcb_cost_snapshots (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pcb_order_link_id UUID NOT NULL REFERENCES pcb_order_links(id) ON DELETE CASCADE,
    
    -- Fabrication Costs
    fab_base_cost DECIMAL(12,2), -- Board fabrication
    fab_setup_cost DECIMAL(12,2), -- Engineering/setup charge
    fab_tooling_cost DECIMAL(12,2), -- Stencils, molds
    
    -- Assembly Costs
    asm_setup_cost DECIMAL(12,2), -- SMT line setup
    asm_placement_cost DECIMAL(12,2), -- Per joint or hourly
    asm_tht_cost DECIMAL(12,2), -- Through-hole manual labor
    
    -- Component Costs
    component_total_cost DECIMAL(12,2), -- Sum of all matched parts
    component_freight_cost DECIMAL(12,2), -- Inbound freight to assembly house
    
    -- Testing & Quality
    testing_cost DECIMAL(12,2), -- AOI, X-ray, flying probe
    quality_inspection_cost DECIMAL(12,2),
    
    -- Logistics
    outbound_freight_cost DECIMAL(12,2), -- To customer
    duty_cost DECIMAL(12,2), -- Import duty
    tax_cost DECIMAL(12,2), -- VAT/GST if not passed to customer
    
    -- Fees
    payment_processing_fee DECIMAL(12,2), -- Stripe/PayPal fees
    marketplace_fee DECIMAL(12,2), -- Internal allocation
    
    -- Totals
    total_cogs DECIMAL(12,2), -- Sum of all costs
    currency CHAR(3) DEFAULT 'USD',
    
    -- Exchange Rates (if multi-currency)
    exchange_rate_to_usd DECIMAL(10,6),
    rate_snapshot_date DATE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_cost_snapshot_link ON pcb_cost_snapshots(pcb_order_link_id);
```

### 3.2 Margin Calculations
Derived profit metrics.

```sql
CREATE TABLE pcb_margin_calculations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pcb_order_link_id UUID NOT NULL REFERENCES pcb_order_links(id),
    
    -- Revenue
    customer_selling_price DECIMAL(12,2),
    shipping_revenue DECIMAL(12,2), -- If charged separately
    
    -- Costs (from snapshot)
    total_cogs DECIMAL(12,2),
    
    -- Margins
    gross_profit DECIMAL(12,2), -- Selling Price - COGS
    gross_margin_percent DECIMAL(5,2), -- Gross Profit / Selling Price * 100
    
    -- Adjustments
    discount_given DECIMAL(12,2),
    referral_commission DECIMAL(12,2),
    
    net_profit DECIMAL(12,2), -- Gross Profit - adjustments
    net_margin_percent DECIMAL(5,2),
    
    -- Benchmarking
    expected_margin_percent DECIMAL(5,2), -- Target margin for this service type
    variance_percent DECIMAL(5,2), -- Actual vs Expected
    
    calculated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_margin_calc_link ON pcb_margin_calculations(pcb_order_link_id);
```

## 4. Cost Allocation Rules

### 4.1 Direct Costs (COGS)
| Cost Type | GL Account | Source |
|-----------|------------|--------|
| PCB Fabrication | 5000 - COGS PCB Fab | Supplier invoice |
| PCBA Assembly | 5010 - COGS PCBA Assy | Supplier invoice |
| Components | 5020 - COGS Components | BOM match + freight |
| Stencils/Tooling | 5030 - COGS Tooling | One-time charge |
| Testing (AOI/X-ray) | 5040 - COGS Testing | Supplier invoice |
| Inbound Freight | 5050 - COGS Freight In | Logistics provider |
| Import Duty | 5060 - COGS Duty | Customs declaration |

### 4.2 Operating Expenses (OpEx)
| Cost Type | GL Account | Source |
|-----------|------------|--------|
| Payment Processing | 6000 - OpEx Payment Fees | Stripe/PayPal statement |
| Platform/Hosting | 6010 - OpEx Infrastructure | AWS/Azure bills |
| Engineering Review | 6020 - OpEx Engineering Labor | Internal allocation |
| Customer Support | 6030 - OpEx Support Labor | Internal allocation |

### 4.3 Revenue Recognition
| Revenue Type | GL Account | Notes |
|--------------|------------|-------|
| PCB Fabrication Sales | 4000 - Revenue PCB Fab | Recognized on shipment |
| PCBA Assembly Sales | 4010 - Revenue PCBA Assy | Recognized on shipment |
| Component Sales | 4020 - Revenue Components | Recognized on shipment |
| Design Services | 4030 - Revenue Services | Recognized on milestone |
| Shipping Charges | 4040 - Revenue Shipping | Pass-through |

## 5. Margin Calculation Logic

```php
class PcbMarginCalculator
{
    public function calculate(Order $order, PcbOrderLink $pcbLink): MarginResult
    {
        // 1. Get revenue
        $revenue = $order->total_amount;
        $shippingRevenue = $order->shipping_cost;
        
        // 2. Get costs from snapshot
        $snapshot = PcbCostSnapshot::where('pcb_order_link_id', $pcbLink->id)->first();
        $cogs = $snapshot->total_cogs;
        
        // 3. Calculate gross margin
        $grossProfit = $revenue - $cogs;
        $grossMarginPercent = ($grossProfit / $revenue) * 100;
        
        // 4. Apply adjustments
        $discounts = $order->discount_amount ?? 0;
        $commissions = $this->getReferralCommission($order);
        $paymentFees = $snapshot->payment_processing_fee;
        
        $netProfit = $grossProfit - $discounts - $commissions - $paymentFees;
        $netMarginPercent = ($netProfit / $revenue) * 100;
        
        // 5. Record
        return PcbMarginCalculation::create([
            'pcb_order_link_id' => $pcbLink->id,
            'customer_selling_price' => $revenue,
            'total_cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_margin_percent' => $grossMarginPercent,
            'net_profit' => $netProfit,
            'net_margin_percent' => $netMarginPercent,
        ]);
    }
}
```

## 6. Multi-Currency Handling

### 6.1 Scenario
- Customer pays in **USD**.
- PCB Supplier charges in **CNY**.
- Component Supplier charges in **EUR**.
- Assembly Supplier charges in **USD**.

### 6.2 Solution
1. **Snapshot Exchange Rates:** Lock rates at order creation time.
2. **Base Currency:** Convert all costs to USD for margin calculation.
3. **FX Gain/Loss:** Track variance between snapshot and actual settlement.

```sql
ALTER TABLE pcb_cost_snapshots ADD COLUMN fx_gain_loss DECIMAL(12,2);
ALTER TABLE pcb_cost_snapshots ADD COLUMN fx_rate_source VARCHAR(50); -- e.g., "OpenExchangeRates"
```

## 7. Supplier Settlement

### 7.1 Workflow
1. Order shipped → Supplier invoice received.
2. Accounts Payable verifies against `pcb_cost_snapshots`.
3. Variance > 5% → Flag for review.
4. Approved → Payment scheduled per terms (Net 30, etc.).
5. Payment recorded → `supplier_settlements` updated.

### 7.2 Retention for Quality
Hold 5-10% of payment until:
- Customer confirms receipt.
- No quality complaints within 14 days.
- First Article Inspection (FAI) approved.

## 8. Financial Reporting

### 8.1 PCB-Specific Reports
- **Margin by Service Type:** Fab vs. Assembly vs. Turnkey.
- **Margin by Supplier:** Identify most profitable partners.
- **Margin by Region:** Domestic vs. Offshore production.
- **Margin by Volume:** Prototype (<10) vs. Production (>1000).

### 8.2 Integration with NeoGiga ERP
PCB margin data flows into:
- **P&L Statement:** Revenue, COGS, Gross Profit lines.
- **Balance Sheet:** Accounts Receivable, Accounts Payable.
- **Cash Flow:** Operating activities section.

## 9. Security & Access Control

- **Cost Visibility:** Only admins and finance team can see costs.
- **Customer View:** Customers see only selling price.
- **Supplier View:** Suppliers see only their line item cost.
- **Audit Trail:** All margin recalculations logged.

## 10. Testing Strategy

- **Unit Tests:** Margin calculation formulas.
- **Integration Tests:** Order → Snapshot → Margin flow.
- **FX Tests:** Multi-currency conversion accuracy.
- **Regression Tests:** Verify standard NeoGiga orders unaffected.

## 11. Deployment Checklist

- [ ] Create `pcb_cost_snapshots` and `pcb_margin_calculations` tables.
- [ ] Map PCB cost types to NeoGiga GL accounts.
- [ ] Configure exchange rate service integration.
- [ ] Set up supplier settlement workflow.
- [ ] Create admin dashboard for margin reporting.
- [ ] Test with sample orders in multiple currencies.

## 12. Compliance Considerations

- **Revenue Recognition:** Follow ASC 606 / IFRS 15 (performance obligation = shipment).
- **Transfer Pricing:** Document inter-company transactions if applicable.
- **Tax Compliance:** Ensure VAT/GST correctly calculated per destination.
- **Audit Readiness:** Maintain immutable cost snapshots for 7 years.
