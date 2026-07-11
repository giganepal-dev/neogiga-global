# PCB DFM (Design for Manufacturing) Guide

## 1. Executive Summary

This guide defines the **NeoGiga DFM Engine**, a rule-based system that analyzes PCB designs (Gerber, BOM, CPL) against manufacturer capabilities to identify producibility issues before fabrication.

**Core Principle:** DFM checks are advisory until engineering approval. Automated detection must state confidence levels and never block orders without human review for "blocking" severity items.

## 2. Architecture Overview

### 2.1 DFM Workflow
1. **Trigger:** Gerber/BOM/CPL upload completed.
2. **Extract:** Parse geometric and component data.
3. **Rule Engine:** Apply manufacturer-specific ruleset.
4. **Report:** Generate issue list with severity and location.
5. **Resolve:** Customer/Engineer addresses issues or accepts risk.
6. **Lock:** Approved DFM report attached to production order.

### 2.2 Integration Points
- **Input:** `pcb_files` (Gerber), `pcb_component_matches` (BOM), `pcb_cpl_lines`.
- **Reference:** `pcb_manufacturer_capabilities`.
- **Output:** `pcb_dfm_runs`, `pcb_dfm_issues`.

## 3. Database Schema

### 3.1 DFM Runs
Tracks each analysis execution.

```sql
CREATE TABLE pcb_dfm_runs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pcb_project_id UUID NOT NULL REFERENCES pcb_projects(id),
    file_version_id UUID REFERENCES pcb_file_versions(id),
    manufacturer_id UUID REFERENCES pcb_manufacturers(id), -- Ruleset used
    
    status VARCHAR(50) DEFAULT 'pending', -- pending, running, completed, failed
    parser_version VARCHAR(50), -- Version of DFM engine
    
    summary_json JSONB, -- { total_issues: int, blocking: int, warning: int }
    
    started_at TIMESTAMP WITH TIME ZONE,
    completed_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_dfm_run_project ON pcb_dfm_runs(pcb_project_id);
```

### 3.2 DFM Issues
Individual violations found.

```sql
CREATE TABLE pcb_dfm_issues (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    dfm_run_id UUID NOT NULL REFERENCES pcb_dfm_runs(id) ON DELETE CASCADE,
    
    rule_id VARCHAR(100), -- e.g., "MIN_TRACE_WIDTH", "DRILL_TO_COPPER"
    severity VARCHAR(50), -- 'info', 'warning', 'blocking', 'engineering_review'
    
    location_description TEXT, -- Human readable (e.g., "Top Layer, near U3")
    coordinates_json JSONB, -- { x: float, y: float, layer: "top" } for viewer highlighting
    
    detected_value DECIMAL(10,4), -- e.g., 0.15 (mm)
    required_value DECIMAL(10,4), -- e.g., 0.20 (mm)
    unit VARCHAR(20) DEFAULT 'mm',
    
    message TEXT, -- "Trace width 0.15mm is below manufacturer minimum 0.20mm"
    suggestion TEXT, -- "Increase trace width to 0.20mm or select advanced manufacturer"
    
    status VARCHAR(50) DEFAULT 'open', -- open, acknowledged, waived, resolved
    resolved_by UUID REFERENCES users(id),
    resolution_note TEXT,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_dfm_issue_run ON pcb_dfm_issues(dfm_run_id);
CREATE INDEX idx_dfm_issue_severity ON pcb_dfm_issues(severity);
```

### 3.3 DFM Rules
Configurable rules per manufacturer.

```sql
CREATE TABLE pcb_dfm_rules (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    manufacturer_id UUID REFERENCES pcb_manufacturers(id),
    
    rule_code VARCHAR(100) UNIQUE, -- "MIN_TRACE_WIDTH"
    category VARCHAR(50), -- 'fabrication', 'assembly', 'drill', 'material'
    
    parameter_name VARCHAR(100), -- "min_trace_width_outer"
    operator VARCHAR(10), -- '>=', '<=', '==', 'in_range'
    threshold_value DECIMAL(10,4),
    threshold_min DECIMAL(10,4), -- For ranges
    threshold_max DECIMAL(10,4),
    
    unit VARCHAR(20),
    severity_default VARCHAR(50), -- 'warning', 'blocking'
    
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

## 4. DFM Check Categories

### 4.1 Fabrication Checks (Gerber)

| Rule Code | Description | Severity | Detection Method |
|-----------|-------------|----------|------------------|
| `MISSING_OUTLINE` | No board outline detected | Blocking | Layer parsing |
| `MIN_TRACE_WIDTH` | Trace < manufacturer limit | Warning/Blocking | Vector analysis |
| `MIN_CLEARANCE` | Copper-to-copper spacing | Warning | Vector analysis |
| `DRILL_SIZE_MIN` | Hole diameter too small | Blocking | Drill file parse |
| `DRILL_SIZE_MAX` | Hole diameter too large | Warning | Drill file parse |
| `ANNULAR_RING` | Ring < 0.15mm or broken | Warning | Vector overlap |
| `DRILL_TO_COPPER` | Drill too close to copper | Warning | Distance calc |
| `SILK_OVER_PAD` | Silkscreen on solder pad | Info | Layer overlap |
| `MASK_SLIVER` | Narrow mask between pads | Warning | Vector analysis |
| `COPPER_TO_EDGE` | Copper too close to edge | Warning | Boundary check |

### 4.2 Assembly Checks (BOM + CPL)

| Rule Code | Description | Severity | Detection Method |
|-----------|-------------|----------|------------------|
| `BOM_CPL_MISMATCH` | Designator in CPL not in BOM | Blocking | Set comparison |
| `MISSING_FOOTPRINT` | Component has no footprint | Blocking | CPL parse |
| `POLARITY_MISSING` | Polarized part no orientation | Warning | Library check |
| `THERMAL_RELIEF` | Large pad no thermal relief | Info | Gerber analysis |
| `COMPONENT_CLEARANCE` | Parts too close physically | Warning | Coordinate check |
| `REFDES_DUPLICATE` | Same designator used twice | Blocking | CPL parse |
| `DNP_CONFLICT` | DNP part has placement coords | Warning | CPL logic |

## 5. Implementation Strategy

### 5.1 Phase 1: Basic Geometric Checks
- Implement drill file parser (Excellon format).
- Detect board outline from G-code (G36/G37 or contour).
- Calculate bounding box and area.
- Check drill sizes against simple min/max thresholds.

### 5.2 Phase 2: Vector Analysis
- Integrate open-source Gerber parser (e.g., `tracespace/gerber-parser`).
- Build copper polygon representation.
- Implement minimum distance algorithms (trace-to-trace, drill-to-copper).
- Cache analysis results to avoid re-processing.

### 5.3 Phase 3: Assembly Intelligence
- Cross-reference BOM and CPL.
- Validate footprints against library.
- Check component height vs. manufacturer limits.
- Identify polarized components (capacitors, diodes, ICs).

### 5.4 Phase 4: Advanced Features
- Impedance calculation verification (if stackup provided).
- Castellated hole detection.
- Panelization analysis (V-score, tab routing).
- Thermal analysis basics (large copper pours).

## 6. API Endpoints

### 6.1 Run DFM Analysis
`POST /api/v1/pcb/projects/{uuid}/dfm/run`

- **Body:** `{ "manufacturer_id": "uuid", "file_version_id": "uuid" }`
- **Response:** `{ "dfm_run_id": "uuid", "status": "queued" }`
- **Queue:** `pcb-dfm`

### 6.2 Get DFM Report
`GET /api/v1/pcb/projects/{uuid}/dfm/runs/{run_id}`

- **Response:** Full report with issues grouped by severity.

### 6.3 Acknowledge/Waive Issue
`POST /api/v1/pcb/dfm/issues/{issue_id}/waive`

- **Body:** `{ "reason": "Accepted risk for prototype" }`
- **Permission:** `pcb.dfm.review` or project owner.

## 7. Frontend Integration

### 7.1 DFM Dashboard
- **Summary Cards:** Total issues by severity (Blocking, Warning, Info).
- **Issue List:** Filterable table with rule, location, message.
- **Viewer Overlay:** Click issue → Zoom Gerber viewer to coordinates.

### 7.2 Interactive Resolution
- **Acknowledge:** User confirms they see the issue.
- **Waive:** User accepts risk (requires comment for blocking items).
- **Fix Later:** Mark for redesign in next version.

## 8. Manufacturer Capability Integration

DFM rules are loaded dynamically based on selected manufacturer:

```php
$rules = PcbDfmRule::where('manufacturer_id', $manufacturerId)
    ->where('is_active', true)
    ->get();

foreach ($rules as $rule) {
    $violation = $this->checkRule($parsedData, $rule);
    if ($violation) {
        $issues->add($violation);
    }
}
```

## 9. Security & Performance

- **Timeout:** DFM jobs must timeout after 10 minutes to prevent hangs.
- **Memory Limit:** Large Gerber files processed in chunks.
- **Access Control:** DFM reports are private to project members.
- **Audit:** All waivers logged with user ID and timestamp.

## 10. Testing Strategy

- **Unit Tests:** Individual rule logic (e.g., `testMinTraceWidthRule`).
- **Fixture Tests:** Run against known-good and known-bad Gerber sets.
- **Performance Tests:** Verify processing time for 12-layer, high-density boards.
- **False Positive Audit:** Regular review of flagged issues to tune rules.

## 11. Deployment Checklist

- [ ] Seed `pcb_dfm_rules` with baseline IPC-2221 Class 2 standards.
- [ ] Configure `pcb-dfm` queue with adequate memory (2GB+).
- [ ] Install Gerber parsing library.
- [ ] Create admin interface to manage DFM rules.
- [ ] Test with real customer files (anonymized).

## 12. Limitations & Disclaimers

- **Not IPC Certified:** This tool does not guarantee IPC compliance.
- **Advisory Only:** Final manufacturing feasibility determined by supplier.
- **Parser Limits:** Complex polygons or non-standard Gerber extensions may fail.
- **No Electrical Check:** Does not verify signal integrity or power delivery.
