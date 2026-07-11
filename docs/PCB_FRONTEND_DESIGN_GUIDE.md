# PCB Frontend Design Guide

## 1. Executive Summary

This guide defines the **NeoGiga PCB Frontend Design System**, creating a unique, professional interface for pcb.neogiga.com that integrates seamlessly with the main NeoGiga platform while providing specialized tools for PCB engineers.

**Core Principle:** Do not clone JLCPCB. Build a distinct NeoGiga brand identity that emphasizes engineering excellence, transparency, and global marketplace integration.

## 2. Brand Identity

### 2.1 Visual Language
- **Logo:** "NeoGiga PCB" lockup (main logo + PCB subscript).
- **Color Palette:**
  - Primary: NeoGiga Blue (#0066CC)
  - Secondary: PCB Green (#00A86B) - subtle nod to solder mask
  - Accent: Copper Orange (#D97706) - for CTAs
  - Neutral: Slate Grays (#475569, #F1F5F9)
- **Typography:** Inter (UI), JetBrains Mono (code/MPN tables).
- **Iconography:** Technical, precise, engineering-focused.

### 2.2 Tone & Voice
- **Professional:** Clear, concise, technical accuracy.
- **Helpful:** Guided workflows, tooltips, contextual help.
- **Transparent:** Show all costs, lead times, risks upfront.
- **Global:** Multi-language, multi-currency ready.

## 3. Information Architecture

### 3.1 Public Routes
```
/                       → Homepage (hero, services, value prop)
/pcb-quote              → Quote configurator (upload + config)
/pcb-design             → Design service landing
/pcb-assembly           → PCBA service landing
/component-sourcing     → BOM sourcing landing
/smt-stencil            → Stencil ordering
/dfm-review             → DFM tool preview
/capabilities           → Manufacturing capabilities matrix
/materials              → Substrate, finish options
/pricing                → Price calculator / examples
/resources              → Tutorials, guides, blog
/support                → Help center, contact
```

### 3.2 Authenticated Routes
```
/projects               → Dashboard (list of user's projects)
/projects/{id}          → Project workspace (tabs below)
  /requirements         → Project brief, specs
  /design               → Design service requests
  /files                → File manager (Gerber, schematic)
  /gerber               → Gerber viewer
  /bom                  → BOM editor + matching
  /cpl                  → Pick-and-place data
  /dfm                  → DFM report + resolution
  /quote                → Quote configurator + comparison
  /suppliers            → RFQ management
  /orders               → Production orders linked to project
  /quality              → Inspection reports, complaints
  /activity             → Audit log, messages
```

### 3.3 Admin Routes
```
/admin/pcb              → Admin dashboard
/admin/pcb/projects     → Project management
/admin/pcb/quotes       → Quote oversight
/admin/pcb/suppliers    → Supplier management
/admin/pcb/capabilities → Capability configuration
/admin/pcb/pricing      → Pricing rules engine
/admin/pcb/quality      → Quality claims review
```

## 4. Key Page Designs

### 4.1 Homepage Hero Section
```
┌─────────────────────────────────────────────────────────────┐
│  [NeoGiga PCB Logo]                              [Login]    │
│                                                             │
│           Build, Source & Manufacture Your Electronics      │
│                                                             │
│    From prototype to production – integrated with the       │
│    world's largest electronic component marketplace         │
│                                                             │
│    [Upload Gerber]  [Upload BOM]  [Start PCB Design]       │
│                                                             │
│    [Ask AI Engineer →]                                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 4.2 Quote Configurator (Desktop)
```
┌───────────────────────────────────────────────────────────────┐
│  Step 1: Upload Files        │  │  Order Summary             │
│  ┌────────────────────────┐  │  │                            │
│  │  Drag & Drop Gerber    │  │  │  PCB Fabrication: $125.00 │
│  │  or Browse...          │  │  │  PCBA Assembly:   $85.50  │
│  │                        │  │  │  Components:      $234.00 │
│  │  ✓ gerber_project.zip  │  │  │  Stencil:         $25.00  │
│  │  ✓ drill_file.txt      │  │  │  Engineering:     $0.00   │
│  │                        │  │  │  Freight:         $35.00  │
│  │  Layers Detected: 4    │  │  │                            │
│  │  Board Size: 100x80mm  │  │  │  ─────────────────────    │
│  └────────────────────────┘  │  │  Total:         $504.50   │
│                                │  │                            │
│  Step 2: Configuration       │  │  Lead Time: 5-7 days       │
│  ┌────────────────────────┐  │  │  Delivery: Dec 15-18       │
│  │  Layers:       [4 ▼]   │  │  │                            │
│  │  Material:     [FR-4▼] │  │  │  [Save Quote]              │
│  │  Thickness:    [1.6▼]  │  │  │  [Submit for Review]       │
│  │  Copper:       [1oz▼]  │  │  │  [Add to Cart]             │
│  │  Finish:       [ENIG▼] │  │  │                            │
│  │  Qty:          [5  ▼]  │  │  ⚠ 2 DFM warnings detected    │
│  └────────────────────────┘  │  │                            │
└───────────────────────────────────────────────────────────────┘
```

### 4.3 Project Workspace Tabs
```
┌─────────────────────────────────────────────────────────────┐
│  Project: NEO-PCB-2024-001              [v2.3] [Edit]       │
│  Status: Files Ready                    Owner: John Doe     │
├─────────────────────────────────────────────────────────────┤
│  [Overview] [Requirements] [Design] [Files] [Gerber]        │
│  [BOM] [CPL] [DFM] [Quote] [Suppliers] [Orders] [Quality]   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─ Gerber Viewer ──────────────────────────────────────┐  │
│  │                                                       │  │
│  │   [Top Copper] [Bottom Copper] [Silkscreen] ...       │  │
│  │                                                       │  │
│  │          (Interactive Gerber Rendering)               │  │
│  │                                                       │  │
│  │   Zoom: [+][-]  Fit  Measure  [Export PNG]            │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                             │
│  Recent Activity:                                           │
│  • DFM analysis completed - 2 warnings                      │
│  • BOM matched: 142/145 components                          │
│  • Quote requested from 3 suppliers                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 4.4 BOM Matching Dashboard
```
┌─────────────────────────────────────────────────────────────┐
│  BOM Match Results                          [Re-run Match]  │
├─────────────────────────────────────────────────────────────┤
│  Total Lines: 145  ✓ Matched: 142  ⚠ Review: 2  ✗ No: 1   │
├─────────────────────────────────────────────────────────────┤
│  Ref Des  │ Requested MPN      │ Matched Product   │ Status │
│  ─────────┼────────────────────┼───────────────────┼────────│
│  C1-C10   │ GRM188R71H104KA01D │ ✓ Same (Murata)   │ Approved│
│  R1-R5    │ RC0603FR-0710KL    │ ✓ Same (Yageo)    │ Approved│
│  U3       │ STM32F103C8T6      │ ⚠ Alt (LCSC)      │ Review │
│  J1       │ Custom_Conn_001    │ ✗ Not Found       │ Sourcing│
│                                                             │
│  [Select Row] → [View Alternatives] [Request Sourcing]      │
└─────────────────────────────────────────────────────────────┘
```

### 4.5 Mobile Quote Wizard
```
┌─────────────────────────┐
│  < Back    Step 2 of 5  │
├─────────────────────────┤
│                         │
│   Board Configuration   │
│                         │
│   Layers                │
│   ○ 2  ● 4  ○ 6  ○ 8+   │
│                         │
│   Material              │
│   [FR-4 TG135       ▼]  │
│                         │
│   Thickness             │
│   [1.6mm            ▼]  │
│                         │
│   ───────────────────   │
│                         │
│   Est. Price: $125.00   │
│                         │
│   [Continue →]          │
│                         │
└─────────────────────────┘
```

## 5. Component Library

### 5.1 Core Components (Nuxt 3)
- `PcbUploadZone` - Drag-drop file upload with progress.
- `PcbLayerMapper` - Visual layer assignment for Gerber files.
- `PcbConfigurator` - Stepper form for board specs.
- `PcbPriceSummary` - Sticky summary card with breakdown.
- `PcbGerberViewer` - Canvas-based Gerber rendering.
- `PcbBomTable` - Virtualized table for large BOMs.
- `PcbMatchIndicator` - Confidence score badges.
- `PcbDfmIssueCard` - Issue detail with location link.
- `PcbQuoteComparison` - Side-by-side supplier quotes.
- `PcbTimeline` - Production status tracker.

### 5.2 State Management (Pinia)
```typescript
// stores/pcb-project.ts
export const usePcbProject = defineStore('pcbProject', {
  state: () => ({
    currentProject: null as PcBProject | null,
    activeTab: 'overview',
    files: [] as PcBFile[],
    bomLines: [] as BomLine[],
    quote: null as Quote | null,
  }),
  actions: {
    async loadProject(uuid: string) { ... },
    async uploadFile(file: File) { ... },
    async runDfm() { ... },
  },
});
```

## 6. Responsive Design Rules

### 6.1 Breakpoints
- **Mobile:** < 640px (single column, wizard flow)
- **Tablet:** 640px - 1024px (two columns, collapsible sections)
- **Desktop:** > 1024px (multi-column, always-visible summary)

### 6.2 Mobile Optimizations
- Sticky footer with total price + CTA.
- Collapsible configuration sections.
- Touch-friendly layer toggles (large tap targets).
- Simplified BOM view (card layout vs. table).
- Bottom sheet for file selection.

## 7. Accessibility (a11y)

- **WCAG 2.1 AA Compliance:** Color contrast, keyboard navigation.
- **Screen Reader Support:** ARIA labels for Gerber viewer controls.
- **Focus Management:** Clear focus states for form inputs.
- **Error Messages:** Descriptive, linked to offending fields.

## 8. Performance Optimization

- **Lazy Loading:** Defer Gerber viewer until tab activated.
- **Virtual Scrolling:** For BOM tables with 1000+ lines.
- **Image Optimization:** WebP for product thumbnails.
- **Code Splitting:** Separate chunks for public vs. authenticated routes.
- **Caching:** SWR pattern for project data, stale-while-revalidate.

## 9. SEO Integration

### 9.1 Public Pages
- Dynamic meta titles/descriptions per service page.
- Structured data (Service schema) for PCB fabrication.
- Canonical URLs to pcb.neogiga.com.
- Hreflang tags for localized versions (/np, /in, etc.).

### 9.2 Private Pages
- `noindex, nofollow` meta robots.
- Exclude from sitemap.xml.
- Require authentication middleware.

## 10. Testing Strategy

- **Visual Regression:** Percy/Chromatic for key pages.
- **E2E Tests:** Cypress/Playwright for quote flow.
- **Accessibility Tests:** axe-core automated scans.
- **Performance Tests:** Lighthouse CI integration.

## 11. Deployment Checklist

- [ ] Build Nuxt app with `npm run build`.
- [ ] Configure Nginx for SSR/SPA hybrid.
- [ ] Set up CDN for static assets (public only).
- [ ] Verify private file routes are not cached.
- [ ] Test responsive layouts on real devices.
- [ ] Run accessibility audit.
- [ ] Validate SEO meta tags.
