# PCB Component Matching Guide

## 1. Executive Summary

This guide details the implementation of the **NeoGiga Component Matching Engine**, a critical subsystem that links PCB Assembly (PCBA) requirements to the canonical NeoGiga product catalog.

**Core Principle:** Never create a disconnected PCBA component catalog. Every component on a PCB BOM must resolve to an existing `products` record or a verified "Request for Sourcing" entry.

## 2. Architecture Overview

### 2.1 Data Flow
1. **Ingest:** User uploads BOM/CPL (CSV/XLSX).
2. **Normalize:** System cleans MPNs, manufacturers, and references.
3. **Match:** Algorithm queries `products`, `manufacturers`, and `seller_offers`.
4. **Rank:** Results scored by availability, price, and lifecycle.
5. **Review:** Engineer/Customer approves matches or selects alternatives.
6. **Lock:** Approved matches linked to `pcb_project_versions`.

### 2.2 Integration Points
- **Existing:** `products`, `manufacturers`, `brands`, `seller_offers`, `warehouses`, `inventory`.
- **New:** `pcb_component_matches`, `pcb_component_substitutions`, `pcb_cpl_lines`.

## 3. Database Schema Extensions

### 3.1 Component Match Records
Tracks the result of matching a BOM line to a catalog product.

```sql
CREATE TABLE pcb_component_matches (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pcb_project_id UUID NOT NULL REFERENCES pcb_projects(id) ON DELETE CASCADE,
    bom_line_ref VARCHAR(255), -- Reference designator group (e.g., "C1-C10")
    requested_mpn VARCHAR(255),
    requested_manufacturer VARCHAR(255),
    
    -- Match Result
    matched_product_id UUID REFERENCES products(id), -- Null if no match found
    matched_seller_offer_id UUID REFERENCES seller_offers(id),
    match_confidence_score DECIMAL(5,2), -- 0.00 to 100.00
    match_type VARCHAR(50), -- 'exact', 'manufacturer_alias', 'functional_alt', 'sourcing_request'
    
    -- Status
    status VARCHAR(50) DEFAULT 'pending', -- pending, auto_matched, engineer_review, approved, rejected
    approved_by UUID REFERENCES users(id),
    approved_at TIMESTAMP WITH TIME ZONE,
    
    -- Metadata
    mismatch_reason TEXT, -- Why it wasn't auto-approved
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_pcm_project ON pcb_component_matches(pcb_project_id);
CREATE INDEX idx_pcm_status ON pcb_component_matches(status);
CREATE INDEX idx_pcm_product ON pcb_component_matches(matched_product_id);
```

### 3.2 Substitution Log
Audit trail for when an exact part is swapped for an alternative.

```sql
CREATE TABLE pcb_component_substitutions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES pcb_projects(id),
    original_product_id UUID REFERENCES products(id),
    substitute_product_id UUID NOT NULL REFERENCES products(id),
    
    reason_code VARCHAR(50), -- 'out_of_stock', 'end_of_life', 'cost_optimization', 'lead_time'
    justification TEXT,
    requested_by UUID REFERENCES users(id),
    approved_by UUID REFERENCES users(id),
    risk_level VARCHAR(20), -- 'low', 'medium', 'high' (based on package/function criticality)
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

## 4. Matching Algorithm Logic

### 4.1 Normalization Steps
Before matching, all input data is normalized:
- **MPN:** Remove spaces, convert to uppercase, strip common suffixes (e.g., "-TR", "ND").
- **Manufacturer:** Map aliases (e.g., "TI" → "Texas Instruments", "AD" → "Analog Devices").
- **Package:** Standardize footprints (e.g., "0603" → "0603 Metric", "SOIC-8" → "SOIC-8").

### 4.2 Matching Priority Tiers

| Tier | Strategy | Confidence Score | Action |
|------|----------|------------------|--------|
| 1 | Exact MPN + Exact Manufacturer | 100% | Auto-approve if stock > MOQ |
| 2 | Exact MPN + Manufacturer Alias | 95% | Auto-approve |
| 3 | Exact MPN + Any Verified Seller | 90% | Flag for price review |
| 4 | Functional Equivalent (Same Specs) | 70% | Engineer Review Required |
| 5 | Cross-Reference (Distributor SKU) | 60% | Engineer Review Required |
| 6 | No Match | 0% | Create "Sourcing Request" |

### 4.3 Pseudo-Code Implementation

```php
class ComponentMatchingService
{
    public function matchLine(BomLine $line, PcBProject $project): ComponentMatchResult
    {
        $normalizedMpn = $this->normalizeMpn($line->mpn);
        $normalizedMfg = $this->normalizeManufacturer($line->manufacturer);

        // Tier 1: Exact Match
        $product = Product::where('mpn_normalized', $normalizedMpn)
            ->whereHas('manufacturers', function($q) use ($normalizedMfg) {
                $q->where('name_normalized', $normalizedMfg);
            })
            ->first();

        if ($product) {
            return $this->createMatch($project, $line, $product, 'exact', 100);
        }

        // Tier 2: MPN Only (Multiple Mfg)
        $product = Product::where('mpn_normalized', $normalizedMpn)->first();
        if ($product) {
            return $this->createMatch($project, $line, $product, 'mpn_only', 90);
        }

        // Tier 3: Fuzzy/Alternative Search (Requires AI or Spec Engine)
        // ... implementation details
    }
}
```

## 5. API Endpoints

### 5.1 Upload and Process BOM
`POST /api/v1/pcb/projects/{uuid}/bom/match`

- **Input:** JSON array of BOM lines or file ID.
- **Process:** Triggers `PcbComponentMatchJob`.
- **Response:** `{ job_id, status: 'processing' }`

### 5.2 Get Match Results
`GET /api/v1/pcb/projects/{uuid}/bom/matches`

- **Query Params:** `status=pending`, `confidence_lt=90`
- **Response:** Paginated list of matches with product details.

### 5.3 Approve/Substitute
`POST /api/v1/pcb/projects/{uuid}/bom/matches/{match_id}/approve`
`POST /api/v1/pcb/projects/{uuid}/bom/matches/{match_id}/substitute`

- **Body:** `{ "substitute_product_id": "uuid", "reason": "..." }`

## 6. Frontend Integration (Nuxt)

### 6.1 BOM Match Dashboard
A split-view interface:
- **Left:** Uploaded BOM lines.
- **Right:** Matched products with stock, price, and datasheet links.
- **Actions:** "Approve", "Find Alternative", "Ignore".

### 6.2 Component Detail Modal
Shows side-by-side comparison:
- **Requested:** MPN, Package, Specs.
- **Proposed:** MPN, Package, Specs, Stock, Price Breaks.
- **Delta:** Highlight differences in package or electrical specs.

## 7. Security & Validation

- **Authorization:** Only project members and assigned engineers can approve matches.
- **Audit:** Every substitution is logged with user ID and timestamp.
- **Validation:** Prevent approval if substitute package differs physically (e.g., 0603 vs 0805) without explicit warning acknowledgment.

## 8. Testing Strategy

- **Unit Tests:** Normalize MPN strings, manufacturer alias mapping.
- **Integration Tests:** End-to-end BOM upload → Match → Approve flow.
- **Data Tests:** Verify matching against known tricky parts (e.g., resistors with similar values).

## 9. Deployment Checklist

- [ ] Populate `manufacturer_aliases` table.
- [ ] Index `products.mpn_normalized`.
- [ ] Configure queue worker for `pcb-component-match`.
- [ ] Verify permissions for `pcb.component.approve`.
