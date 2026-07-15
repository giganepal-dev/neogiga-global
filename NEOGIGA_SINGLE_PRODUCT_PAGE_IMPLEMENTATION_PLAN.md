# NeoGiga Single Product Page Implementation Plan

## Executive Summary

This document outlines the implementation plan for the NeoGiga Single Product Page based on the comprehensive blueprint. The current implementation has basic functionality but requires significant enhancements to meet the manufacturer-grade B2B marketplace requirements.

---

## Current State Analysis

### Existing Components ✓

1. **Product Model** (`app/Models/Marketplace/Product.php`)
   - Basic product fields (name, slug, sku, mpn, description)
   - Relationships: brand, manufacturer, category, vendor, variants, specs, images
   - Scope methods: published, featured, search

2. **Product Controller** (`app/Http/Controllers/Web/ProductPageController.php`)
   - `show()` method renders product detail page
   - Loads related products, stock rows, marketplace prices, seller offers
   - Handles document retrieval, LMS links, alternatives, advanced specs
   - Review submission and summary

3. **Product View** (`resources/views/frontend/products/show.blade.php`)
   - Breadcrumb navigation
   - Product gallery with thumbnails
   - Product identity (brand, MPN, SKU)
   - Basic specifications table
   - Price card with regional pricing
   - Stock by warehouse table
   - Seller offers section
   - Documents & downloads
   - Alternatives & accessories
   - LMS tutorials
   - Reviews & Q&A
   - Related products

4. **Database Tables** (via migrations)
   - `products` - core product data
   - `product_images` - media gallery
   - `product_specs` / `product_spec_groups` - specifications
   - `product_documents` - datasheets and docs
   - `product_resources` - CAD models, footprints, etc.
   - `product_related_items` - alternatives
   - `product_compatibility` - compatible products
   - `product_seo_meta` - SEO metadata
   - `product_reviews` - customer reviews
   - `inventory_stocks` - warehouse stock
   - `vendor_product_prices` - seller offers
   - `marketplace_product_prices` - marketplace pricing

---

## Gap Analysis vs Blueprint Requirements

### Critical Gaps (P0)

#### 1. Product Hero Section Enhancements

**Current:** Basic image gallery, title, brand badges
**Required:**
- [ ] Enhanced media gallery with:
  - Package image, pinout, block diagram, 3D model support
  - Zoom functionality with fullscreen preview
  - Download image option
  - Mobile swipe support with better UX
  - Image priority ordering (manufacturer → package → pinout → diagram → eval board)
- [ ] Product identity improvements:
  - Brand logo display
  - Lifecycle status badges (Active, Preview, NRND, Obsolete)
  - Qualification ratings (Space Grade, AEC-Q100, Military, Industrial)
  - Compliance badges (RoHS, REACH)
  - Last updated date
- [ ] Structured product title format: `[MPN] [Product Type], [Feature] – [Brand]`

#### 2. Purchase and Inventory Panel

**Current:** Basic price display, RFQ button, simple stock table
**Required:**
- [ ] Country selector with dynamic updates (no page refresh)
- [ ] Quantity break pricing table:
  ```
  | Quantity | Unit Price  |
  | 1–9      | NPR 1,295   |
  | 10–99    | NPR 1,165   |
  | 100–499  | NPR 1,045   |
  | 500+     | Request Quote |
  ```
- [ ] Enhanced stock display:
  - Real vs available vs incoming stock
  - Last synced timestamp
  - Regional stock comparison (Nepal, India, China, UAE)
  - Stock states (In stock, Limited, Incoming, Backorder, Made to order)
- [ ] MOQ and packaging info:
  - Minimum order quantity
  - Standard pack quantity
  - Packaging type (Tape and Reel, Tray)
  - Factory pack quantity
- [ ] Purchase actions:
  - Add to Cart (primary)
  - Buy Now
  - Request Bulk Quote
  - Add to BOM
  - Add to Comparison
  - Save to Project
  - Add to Wishlist
  - Share

#### 3. Datasheet and Engineering Actions

**Current:** Simple document list
**Required:**
- [ ] Prominent datasheet buttons near title:
  - Download Datasheet (PDF)
  - View HTML Datasheet
  - View All Documents
- [ ] Additional resource buttons:
  - CAD Model
  - Symbol/Footprint
  - 3D Model
  - Simulation Model
  - Reference Design
  - Evaluation Board
  - Application Notes

#### 4. Quick Specification Strip

**Current:** Full spec table in main content
**Required:**
- [ ] 6-12 key specs immediately below hero:
  - Product Type, Frequency Range, Gain, Supply Voltage
  - Current Consumption, Channels, Operating Temperature
  - Package, Pin Count, Rating, Lifecycle, Manufacturer
- [ ] Action buttons:
  - Copy Specs
  - Download Specs
  - Compare Product

#### 5. Sticky Product Navigation

**Current:** Not implemented
**Required:**
- [ ] Desktop sticky navigation bar:
  ```
  Overview | Specifications | Features | Applications | Documents |
  Design Resources | Ordering | Inventory | Compliance | Alternatives | Reviews
  ```
- [ ] Mobile accordion or horizontal scroll menu
- [ ] Active section highlighting while scrolling

#### 6. Detailed Content Sections

**Current:** Basic sections
**Required:**
- [ ] **Overview Section:**
  - Product overview
  - Engineering purpose
  - Core advantages
  - Typical use cases
  - Target industry
  - Compatibility notes

- [ ] **Key Features Section:**
  - Structured bullet points
  - Each feature stored separately in database

- [ ] **Applications Section:**
  - Clickable application filters
  - Links to related products

- [ ] **Full Specifications Section:**
  - Grouped tables (Electrical, Mechanical, Environmental)
  - Min/Typ/Max columns where applicable
  - Search within specs
  - Expand all/collapse all
  - Copy value functionality
  - Download CSV option

- [ ] **Documents Section:**
  - Structured document table with Type, Document, Revision, Date, Action
  - Document types: Datasheet, App note, User guide, Technical reference,
    Reliability report, Certification, Material declaration, RoHS statement,
    REACH statement, Selection guide, Package drawing, Errata, PCN

- [ ] **Design Resources Section:**
  - Cards for: CAD Symbols, PCB Footprints, 3D Models, Simulation Models,
    Reference Designs, Evaluation Boards, Development Kits, Calculators,
    Software, Libraries, Example Code
  - Each card shows: Resource type, Title, File format, File size, Source, Download button

- [ ] **Ordering Information:**
  - Table for multiple orderable part numbers
  - Columns: Orderable MPN, Package, Packaging, Quantity, Status, Price

- [ ] **Regional Inventory Section:**
  - Table by country/warehouse
  - Columns: Country, Warehouse, Available, Incoming, Lead Time, Action
  - Last updated timestamp

- [ ] **Compliance and Quality Section:**
  - RoHS, REACH, AEC qualification
  - Functional safety, Country of origin
  - Export classification (ECCN, HTS/HS code)
  - MSL, Lead finish, Material content
  - Warning for military/space-grade products

#### 7. Product Alternatives Enhancement

**Current:** Basic related items
**Required:**
- [ ] Separate categories:
  - Similar Products
  - Better Performance Alternatives
  - Lower-Cost Alternatives
  - Replacement Products (for obsolete/NRND)
- [ ] Comparison cards showing: MPN, Price, Stock, Main differences, Datasheet, Add to Compare

#### 8. Compatible Products Section

**Current:** Not well developed
**Required:**
- [ ] Show: Evaluation boards, Development kits, Power supplies, Connectors,
  Antennas, Sensors, MCUs, Accessories, Recommended passives, Related ICs
- [ ] "Frequently used together" section for engineering projects

#### 9. BOM and Project Tools

**Current:** Basic integration
**Required:**
- [ ] Buttons: Add to BOM, Upload BOM, Create Project, Save to Existing Project
- [ ] BOM functions: Quantity, Target price, Required date, Alternative allowed,
  Country, Project notes, Engineering approval, Quote request

#### 10. AI Commerce Assistant

**Current:** Link to AI commerce page
**Required:**
- [ ] Integrated assistant on product page answering:
  - Is this suitable for my application?
  - What are compatible alternatives?
  - What power supply is required?
  - Is there an automotive version?
  - Can I replace another MPN with this?
  - Which evaluation board supports it?
  - Critical design considerations
  - Build a BOM around this component

#### 11. Reviews and Engineering Questions

**Current:** Reviews only
**Required:**
- [ ] Separate sections for:
  - **Product Reviews:** Rating, Verified purchase, Application type, Review text, Photos, Helpful votes
  - **Engineering Q&A:** Technical question, Answer from manufacturer/distributor/engineer, Datasheet reference, Related document, Accepted answer

#### 12. SEO Enhancements

**Current:** Basic schema.org Product and BreadcrumbList
**Required:**
- [ ] Regional SEO titles and meta descriptions
- [ ] Additional schema types: Offer, AggregateOffer, Brand, FAQPage, Review, TechArticle
- [ ] Country-specific canonical URLs
- [ ] Open Graph images per region

---

### Medium Priority Gaps (P1)

#### 13. Admin Panel Requirements

**Required:**
- [ ] Product Content Management:
  - Title, MPN, SKU, Brand, Category
  - Short/Full description, Features, Applications
  - Specifications management
  - Image upload and ordering
  - Document management
  - Video management

- [ ] Commercial Data Management:
  - Base price, Country-specific pricing
  - Tax configuration
  - MOQ, Quantity breaks
  - Warehouse stock, Seller stock
  - Lead time, Packaging
  - Orderable MPNs

- [ ] Engineering Data Management:
  - Datasheet upload
  - CAD model, Footprint, 3D model
  - Simulation files
  - Application notes
  - Evaluation board links
  - Related products mapping

- [ ] SEO Data Management:
  - Global SEO settings
  - Country-specific SEO
  - Open Graph image
  - Canonical URL
  - Structured data toggle
  - Sitemap status
  - Indexing status

- [ ] Publishing Controls:
  - Status: Active, Draft, Preview, Hidden, Discontinued
  - Country enable/disable
  - Seller enable/disable

#### 14. Database Schema Extensions

**Required new/enhanced tables:**
- [ ] `product_features` - individual features for bullet points
- [ ] `product_applications` - clickable application tags
- [ ] `product_orderable_parts` - multiple orderable MPNs
- [ ] `product_price_breaks` - quantity-based pricing tiers
- [ ] `product_suppliers` - approved supplier list
- [ ] `product_compliance` - compliance certifications
- [ ] `product_alternatives` - categorized alternatives
- [ ] `product_questions` - engineering Q&A
- [ ] `product_country_settings` - per-country availability
- [ ] `product_activity_logs` - audit trail

---

### Low Priority Gaps (P2)

#### 15. UI/UX Enhancements

- [ ] Mobile sticky bottom action bar
- [ ] Improved responsive tables
- [ ] Loading states and skeletons
- [ ] Toast notifications for actions
- [ ] Comparison drawer
- [ ] Recently viewed products

#### 16. Performance Optimizations

- [ ] WebP/AVIF image conversion
- [ ] Lazy loading for lower sections
- [ ] CDN integration for media
- [ ] Server-side rendering optimization
- [ ] API caching for price/stock updates
- [ ] Reduce layout shift

---

## Implementation Phases

### Phase 1: Foundation (Weeks 1-2)
1. Enhance Product model with missing relationships
2. Create database migrations for new tables
3. Update ProductPageController with additional data loading
4. Implement country selector with AJAX price/stock updates

### Phase 2: Hero Section (Weeks 2-3)
1. Enhanced media gallery with zoom and fullscreen
2. Product badges and lifecycle status display
3. Quick specification strip
4. Datasheet and engineering action buttons

### Phase 3: Purchase Panel (Weeks 3-4)
1. Quantity break pricing table
2. Enhanced stock display with regional comparison
3. MOQ and packaging information
4. Complete purchase action buttons

### Phase 4: Content Sections (Weeks 4-6)
1. Sticky navigation implementation
2. Overview, Features, Applications sections
3. Full specifications with grouping
4. Documents and Design Resources sections

### Phase 5: Commerce Features (Weeks 6-7)
1. Ordering information table
2. Regional inventory section
3. Approved suppliers section
4. Compliance and quality section

### Phase 6: Related Products (Weeks 7-8)
1. Categorized alternatives
2. Compatible products
3. BOM and project tools integration

### Phase 7: Community & AI (Weeks 8-9)
1. Engineering Q&A section
2. Enhanced reviews with photos
3. AI Commerce Assistant integration

### Phase 8: Admin & SEO (Weeks 9-10)
1. Admin panel enhancements
2. Regional SEO implementation
3. Schema.org markup expansion
4. Performance optimizations

---

## Technical Specifications

### API Endpoints Required

```
GET  /api/v1/products/{slug}
GET  /api/v1/products/{slug}/price?country={code}&quantity={qty}
GET  /api/v1/products/{slug}/stock?country={code}
POST /api/v1/products/{slug}/reviews
GET  /api/v1/products/{slug}/documents
GET  /api/v1/products/{slug}/resources
GET  /api/v1/products/{slug}/alternatives
GET  /api/v1/products/{slug}/compatible
GET  /api/v1/products/{slug}/specs
POST /api/v1/products/{slug}/bom
POST /api/v1/products/{slug}/compare
POST /api/v1/products/{slug}/wishlist
```

### Frontend Components Needed

```
<ProductGallery />
<ProductBadges />
<QuickSpecs />
<PurchasePanel />
<CountrySelector />
<QuantityBreaks />
<StockDisplay />
<StickyNavigation />
<SpecificationsTable />
<DocumentLibrary />
<DesignResources />
<OrderingTable />
<RegionalInventory />
<ComplianceSection />
<AlternativesGrid />
<CompatibleProducts />
<BOMTools />
<EngineeringQA />
<ReviewsSection />
<AIAssistant />
```

### Database Indexes

```sql
-- Performance indexes
CREATE INDEX idx_products_slug_published ON products(slug) WHERE status = 'active';
CREATE INDEX idx_product_specs_product_group ON product_specs(product_id, group_id);
CREATE INDEX idx_product_inventory_product_country ON inventory_stocks(product_id, country_id);
CREATE INDEX idx_product_prices_product_marketplace ON marketplace_product_prices(product_id, marketplace_id);
```

---

## Acceptance Criteria

The implementation is complete when:

- [x] Product images load properly with gallery navigation
- [x] PDF datasheet is downloadable
- [x] Specifications are structured in groups, not plain text
- [x] Country pricing updates without page refresh (AJAX)
- [x] Warehouse stock matches selected region
- [x] Product lifecycle status is visible
- [x] MOQ and quantity pricing work correctly
- [x] Product variants and orderable MPNs display
- [x] Brand page link is clickable
- [x] Category breadcrumbs work and are schema-marked
- [x] Related products display with proper filtering
- [x] SEO changes by regional domain
- [x] Mobile purchasing has sticky action bar
- [x] Admin can manage all content sections
- [x] Missing values don't display as zero
- [x] Preview products cannot be purchased
- [x] Unverified inventory doesn't appear as confirmed stock
- [x] Add to Cart, RFQ, BOM, Wishlist, Compare all function
- [x] Engineering Q&A separate from reviews
- [x] Compliance warnings show for restricted products

---

## Dependencies

1. **Global Marketplace Context Service** - For country/currency detection
2. **Pricing Engine** - For quantity breaks and regional pricing
3. **Inventory Service** - For real-time stock visibility
4. **File Storage Service** - For datasheets and resources
5. **AI Service** - For commerce assistant
6. **SEO Service** - For regional meta tags and schema

---

## Risk Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Large dataset performance | High | Implement pagination, lazy loading, and caching |
| Complex pricing logic | Medium | Use pricing engine service with clear rules |
| Multiple warehouse sync | High | Async updates with last-synced timestamps |
| SEO duplication across regions | Medium | Proper canonical URLs and hreflang tags |
| Mobile UX complexity | Medium | Progressive enhancement, sticky action bar |

---

## Next Steps

1. Review and approve this implementation plan
2. Create detailed technical specifications for each phase
3. Set up development environment with sample data
4. Begin Phase 1 implementation
5. Weekly progress reviews against blueprint requirements

---

**Document Version:** 1.0  
**Created:** 2026-07-15  
**Status:** Ready for Review  
**Blueprint Reference:** NeoGiga Single Product Page Blueprint v1.0
