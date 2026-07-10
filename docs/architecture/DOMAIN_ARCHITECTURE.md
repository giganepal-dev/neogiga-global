# NeoGiga Domain Architecture

## Overview

NeoGiga is architected as a modular, domain-driven multi-vendor marketplace for electronic components. Each domain is encapsulated with its own models, services, repositories, policies, and events.

## Core Domains

### 1. Identity Domain
**Purpose:** Authentication, authorization, user management, organization management

**Key Entities:**
- `User` - System users with roles
- `Organization` - Companies, sellers, manufacturers
- `Role` & `Permission` - RBAC system
- `OrganizationMember` - User-organization relationships
- `ApiKey` - API authentication tokens
- `TwoFactorAuthentication` - 2FA secrets and recovery codes
- `LoginSession` - Active sessions tracking
- `Device` - Known devices for security

**Relationships:**
```
User (1) ↔ (M) OrganizationMember ↔ (M) Organization
User (1) ↔ (M) Role ↔ (M) Permission
Organization (1) ↔ (M) User (as members)
```

---

### 2. Marketplace Domain
**Purpose:** Multi-country storefronts, country configuration, localization

**Key Entities:**
- `Country` - Country definitions
- `CountryStorefront` - Country-specific settings
- `Currency` - Currency definitions
- `ExchangeRate` - Historical exchange rates
- `TaxRate` - Country tax configurations
- `ImportDuty` - Import duty rules
- `PaymentGateway` - Country payment methods
- `ShippingMethod` - Country shipping options
- `Language` - Supported languages
- `CountryProductPublication` - Product availability by country

**Relationships:**
```
Country (1) ↔ (M) CountryStorefront
Country (1) ↔ (M) Currency (via pivot)
Country (1) ↔ (M) TaxRate
Product (M) ↔ (M) Country (via CountryProductPublication)
```

---

### 3. Catalogue Domain
**Purpose:** Product categorization, attribute management, brand/manufacturer

**Key Entities:**
- `Category` - Product categories (tree structure)
- `Attribute` - Product attributes (e.g., Voltage, Package)
- `AttributeGroup` - Logical grouping of attributes
- `AttributeValue` - Possible values for attributes
- `Brand` - Product brands
- `Manufacturer` - Component manufacturers
- `ProductFamily` - Product family grouping
- `ProductSeries` - Product series within family

**Relationships:**
```
Category (self-referencing for tree)
Category (1) ↔ (M) AttributeGroup
AttributeGroup (1) ↔ (M) Attribute
Attribute (1) ↔ (M) AttributeValue
Brand (1) ↔ (M) Product
Manufacturer (1) ↔ (M) Product
```

---

### 4. Product Information Management (PIM) Domain
**Purpose:** Master product data, specifications, lifecycle, compliance

**Key Entities:**
- `Product` - Master product record
- `ProductVariant` - Product variants (packaging, reel, tube)
- `ProductSpecification` - Technical specifications
- `ProductImage` - Product images
- `ProductDocument` - Datasheets, manuals, CAD files
- `ProductLifecycle` - Lifecycle status tracking
- `ComplianceCertificate` - RoHS, REACH, CE, FCC, UL
- `ProductSeo` - SEO metadata per country
- `ProductRelation` - Related products, alternates, equivalents

**Key Relationships:**
```
Product (1) ↔ (M) ProductVariant
Product (1) ↔ (M) ProductSpecification
Product (1) ↔ (M) ProductImage
Product (1) ↔ (M) ProductDocument
Product (1) ↔ (1) ProductLifecycle
Product (M) ↔ (M) Category (via pivot)
```

**Lifecycle Statuses:**
- Active
- New
- Recommended
- Not Recommended for New Design (NRND)
- Last Time Buy (LTB)
- Obsolete
- Discontinued
- Pre-release

---

### 5. Vendor Management Domain
**Purpose:** Seller onboarding, verification, performance, settlements

**Key Entities:**
- `Seller` - Marketplace seller profile
- `SellerApplication` - Application workflow
- `SellerVerification` - Verification documents and status
- `SellerPerformance` - Performance metrics
- `SellerCommission` - Commission rules
- `SellerBankAccount` - Payout bank details
- `SellerStaff` - Seller team members
- `SellerPolicy` - Return, shipping, warranty policies

**Relationships:**
```
Organization (1) ↔ (1) Seller
Seller (1) ↔ (M) SellerStaff
Seller (1) ↔ (M) SellerOffer
Seller (1) ↔ (M) SellerSettlement
```

---

### 6. Manufacturer Management Domain
**Purpose:** Manufacturer profiles, brand authorization, distributor networks

**Key Entities:**
- `ManufacturerProfile` - Extended manufacturer data
- `BrandAuthorization` - Authorized brand relationships
- `AuthorizedDistributor` - Authorized distributor network
- `ManufacturerDocument` - Certifications, quality docs
- `ManufacturerContact` - Contact persons

**Relationships:**
```
Manufacturer (1) ↔ (1) ManufacturerProfile
Manufacturer (1) ↔ (M) BrandAuthorization
Manufacturer (1) ↔ (M) AuthorizedDistributor
```

---

### 7. Distributor Management Domain
**Purpose:** Distributor tiers, regional assignments, authorization

**Key Entities:**
- `Distributor` - Distributor profile
- `DistributorTier` - Global, Country, Regional levels
- `DistributorTerritory` - Geographic territory
- `DistributorAuthorization` - Brand authorization
- `DistributorProduct` - Authorized product lines

**Types:**
- Global Distributor
- Country Distributor
- Regional Distributor

---

### 8. Inventory Domain
**Purpose:** Stock tracking, warehouse management, movements

**Key Entities:**
- `Warehouse` - Physical warehouse locations
- `WarehouseLocation` - Zone, Rack, Shelf, Bin hierarchy
- `Stock` - Current stock levels
- `StockMovement` - Immutable movement ledger
- `StockReservation` - Reserved stock
- `StockAdjustment` - Manual adjustments
- `StockTransfer` - Inter-warehouse transfers
- `Batch` - Batch/date-code tracking
- `SerialNumber` - Serial number tracking

**Stock States:**
- Available
- Reserved
- Allocated
- In Transit
- Quality Inspection
- Damaged
- Returned
- Quarantined
- Expired
- Lost
- Demo
- Sample

**Relationships:**
```
Warehouse (1) ↔ (M) WarehouseLocation
Warehouse (1) ↔ (M) Stock
Stock (1) ↔ (M) StockMovement
ProductVariant (1) ↔ (M) Stock
SellerOffer (1) ↔ (M) Stock
```

---

### 9. Procurement Domain
**Purpose:** Purchase orders, supplier management, goods receipt

**Key Entities:**
- `Supplier` - Approved suppliers
- `PurchaseOrder` - PO header
- `PurchaseOrderItem` - PO line items
- `GoodsReceipt` - Received goods
- `PurchaseInvoice` - Supplier invoices
- `LandedCost` - Freight, customs, duties allocation
- `ProcurementRequest` - Internal procurement requests

**Relationships:**
```
Supplier (1) ↔ (M) PurchaseOrder
PurchaseOrder (1) ↔ (M) PurchaseOrderItem
PurchaseOrder (1) ↔ (M) GoodsReceipt
PurchaseOrder (1) ↔ (1) PurchaseInvoice
```

---

### 10. RFQ & Quotation Domain
**Purpose:** Request for quotation, bidding, negotiations

**Key Entities:**
- `Rfq` - RFQ header
- `RfqItem` - RFQ line items
- `RfqInvite` - Invited sellers/suppliers
- `Quotation` - Seller quotation
- `QuotationItem` - Quotation line items
- `QuotationRevision` - Revision history
- `NegotiationMessage` - Negotiation thread
- `ContractPrice` - Agreed contract pricing

**Relationships:**
```
Rfq (1) ↔ (M) RfqItem
Rfq (1) ↔ (M) RfqInvite
RfqInvite (1) ↔ (M) Quotation
Quotation (1) ↔ (M) QuotationItem
Quotation (1) ↔ (M) QuotationRevision
```

---

### 11. Sales Order Domain
**Purpose:** Order management, fulfillment, returns

**Key Entities:**
- `Order` - Order header
- `OrderItem` - Order line items
- `OrderShipment` - Shipments
- `OrderReturn` - Returns
- `OrderRefund` - Refunds
- `OrderNote` - Internal/customer notes
- `OrderTimeline` - Order status history

**Relationships:**
```
Customer (1) ↔ (M) Order
Order (1) ↔ (M) OrderItem
Order (1) ↔ (M) OrderShipment
Order (1) ↔ (M) OrderReturn
OrderReturn (1) ↔ (1) OrderRefund
```

---

### 12. Checkout Domain
**Purpose:** Cart, checkout flow, address management

**Key Entities:**
- `Cart` - Shopping cart
- `CartItem` - Cart line items
- `Address` - Customer addresses
- `CheckoutSession` - Active checkout sessions
- `Coupon` - Discount coupons
- `CouponUsage` - Usage tracking

---

### 13. Payments Domain
**Purpose:** Payment processing, gateways, transactions

**Key Entities:**
- `PaymentMethod` - Available payment methods
- `PaymentTransaction` - Transaction records
- `PaymentGatewayConfig` - Gateway configurations
- `WebhookEvent` - Webhook logs
- `Chargeback` - Chargeback records

**Relationships:**
```
Order (1) ↔ (M) PaymentTransaction
PaymentGatewayConfig (1) ↔ (M) Country (pivot)
```

---

### 14. Tax Domain
**Purpose:** Tax calculation, VAT/GST, import duties

**Key Entities:**
- `TaxClass` - Tax classification
- `TaxRate` - Tax rates by jurisdiction
- `TaxRule` - Tax application rules
- `ImportDutyRule` - Import duty calculations
- `VatNumber` - Customer VAT numbers
- `TaxExemption` - Tax exemption certificates

---

### 15. Pricing Domain
**Purpose:** Dynamic pricing, price lists, volume breaks

**Key Entities:**
- `PriceList` - Price list definitions
- `PriceListItem` - Individual prices
- `VolumePrice` - Quantity break pricing
- `CustomerPrice` - Customer-specific pricing
- `PromotionalPrice` - Promotional pricing
- `CurrencyConversion` - Conversion rules

**Pricing Hierarchy:**
```
Master Product Base Price
→ Seller Offer Price
→ Country-Specific Price
→ Customer Group Price
→ Volume Tier Price
→ Promotional Override
```

---

### 16. Accounting Domain
**Purpose:** Financial tracking, profitability, ledgers

**Key Entities:**
- `Account` - Chart of accounts
- `JournalEntry` - Journal entries
- `JournalLine` - Journal line items
- `Invoice` - Customer invoices
- `Bill` - Supplier bills
- `Payment` - Payments received/made
- `CostComponent` - Cost breakdown
- `ProfitabilityRecord` - Profit calculations
- `FinancialPeriod` - Accounting periods

**Cost Components Tracked:**
- Purchase price
- Freight
- Insurance
- Customs duty
- Import duty
- Excise
- VAT/GST (recoverable)
- Clearing costs
- Local transport
- Bank charges
- Other landed costs

**Revenue Components Tracked:**
- Selling price
- Discounts
- Coupons
- Seller commission
- Payment gateway fees
- Shipping charges
- Tax collected
- Refunds
- Return costs
- Marketplace subsidies
- Promotional costs

**Profit Metrics:**
- Net Revenue
- Cost of Goods Sold (COGS)
- Gross Profit
- Gross Margin %
- Operating Expenses
- Net Profit
- Net Margin %

---

### 17. Settlement Domain
**Purpose:** Seller settlements, payouts, commissions

**Key Entities:**
- `SettlementCycle` - Settlement period definitions
- `Settlement` - Settlement records
- `SettlementItem` - Order-level settlement details
- `CommissionRule` - Commission configurations
- `Commission` - Calculated commissions
- `Payout` - Payout to sellers
- `PayoutRequest` - Seller payout requests
- `ReserveBalance` - Held reserves
- `WithholdingTax` - Tax withholdings
- `SettlementDispute` - Dispute records

**Relationships:**
```
Seller (1) ↔ (M) Settlement
Settlement (1) ↔ (M) SettlementItem
Settlement (1) ↔ (M) Payout
Seller (1) ↔ (M) CommissionRule
```

---

### 18. Returns Domain
**Purpose:** Return management, RMA, inspections

**Key Entities:**
- `ReturnRequest` - RMA requests
- `ReturnItem` - Returned items
- `ReturnInspection` - Inspection results
- `ReturnDisposition` - Final disposition
- `ReturnShipping` - Return shipping labels

---

### 19. Warranty Domain
**Purpose:** Warranty claims, coverage tracking

**Key Entities:**
- `WarrantyPolicy` - Warranty terms
- `WarrantyClaim` - Claim records
- `WarrantyCoverage` - Coverage details
- `WarrantyService` - Service records

---

### 20. Support Domain
**Purpose:** Ticketing, customer support, SLA

**Key Entities:**
- `Ticket` - Support tickets
- `TicketMessage` - Messages
- `TicketAttachment` - Attachments
- `TicketAssignment` - Agent assignments
- `SlaPolicy` - SLA definitions
- `SlaBreaches` - Breach tracking
- `CannedResponse` - Quick responses
- `SupportCategory` - Ticket categories
- `SatisfactionSurvey` - CSAT surveys

---

### 21. Workflow Domain
**Purpose:** Approval workflows, state machines

**Key Entities:**
- `Workflow` - Workflow definitions
- `WorkflowStep` - Steps in workflow
- `WorkflowTransition` - Allowed transitions
- `WorkflowInstance` - Active workflow instances
- `WorkflowApproval` - Approval records
- `WorkflowComment` - Comments
- `WorkflowAttachment` - Attachments
- `SlaDeadline` - Deadline tracking

**Configurable Workflows:**
- Seller onboarding
- Manufacturer onboarding
- Distributor onboarding
- Product approval
- Product modification
- Price change approval
- Country publication
- Purchase order approval
- RFQ approval
- Refund approval
- Return approval
- Settlement approval
- Payout approval
- SEO approval
- Mini-site approval
- Datasheet approval
- Brand authorization approval

---

### 22. Notifications Domain
**Purpose:** Event-driven notifications across channels

**Key Entities:**
- `NotificationTemplate` - Email/SMS templates
- `Notification` - Sent notifications
- `NotificationPreference` - User preferences
- `NotificationChannel` - Channel configs
- `ScheduledNotification` - Scheduled sends
- `NotificationLog` - Delivery logs

**Channels:**
- Email
- In-app notification
- Push notification
- SMS (adapter pattern)
- WhatsApp (adapter pattern)

---

### 23. SEO Domain
**Purpose:** SEO management, meta tags, sitemaps

**Key Entities:**
- `SeoMetadata` - SEO data for any entity
- `UrlRedirect` - 301/302 redirects
- `Sitemap` - Sitemap configurations
- `RobotsRule` - Robots.txt rules
- `SchemaMarkup` - Structured data
- `OpenGraphTag` - Social media tags
- `HreflangTag` - Language targeting
- `CanonicalUrl` - Canonical URLs

**SEO Coverage:**
- Product pages
- Brand pages
- Manufacturer pages
- Category pages
- Seller pages
- Distributor pages
- Country pages
- Regional pages
- Landing pages
- Blogs
- Guides

---

### 24. CMS Domain
**Purpose:** Content management, blogs, pages

**Key Entities:**
- `Page` - Static pages
- `BlogPost` - Blog articles
- `BlogCategory` - Blog categories
- `MediaLibrary` - Media assets
- `ContentBlock` - Reusable content blocks
- `NavigationMenu` - Menu structures
- `MenuItem` - Menu items

---

### 25. Analytics Domain
**Purpose:** Business intelligence, reporting

**Key Entities:**
- `Report` - Report definitions
- `ReportSchedule` - Scheduled reports
- `Dashboard` - Dashboard configurations
- `DashboardWidget` - Widget definitions
- `Metric` - KPI definitions
- `DataExport` - Export jobs

**Key Metrics:**
- GMV (Gross Merchandise Value)
- Revenue
- Gross Profit
- Net Profit
- Orders count
- Customers count
- Sellers count
- Products count
- Inventory value
- Low stock alerts
- Settlement payable
- Country performance
- Category performance
- Seller performance
- Conversion rate
- Refund rate
- Support SLA
- Risk alerts

---

### 26. Supply Chain Risk Domain
**Purpose:** Risk intelligence, supplier scoring, alerts

**Key Entities:**
- `RiskFactor` - Risk factor definitions
- `RiskScore` - Calculated risk scores
- `RiskAssessment` - Assessment records
- `RiskAlert` - Active alerts
- `SupplierRisk` - Supplier-specific risks
- `CountryRisk` - Country-level risks
- `ObsolescenceRisk` - Product obsolescence
- `LeadTimeRisk` - Lead time volatility
- `StockoutRisk` - Stockout probability
- `SingleSourceRisk` - Single-source dependency
- `ComplianceRisk` - Compliance issues
- `CounterfeitRisk` - Counterfeit indicators
- `AlternateSupplier` - Alternative suggestions

**Risk Categories:**
- Supplier risk score
- Country risk
- Lead-time risk
- Stockout risk
- Single-source risk
- Obsolescence risk
- Price volatility
- Shipping disruption
- Compliance risk
- Counterfeit risk
- Manufacturer concentration

---

### 27. AI Commerce Domain
**Purpose:** AI-powered recommendations, BOM matching, insights

**Key Entities:**
- `AiRecommendation` - Product recommendations
- `AiBomMatch` - BOM matching results
- `AiSubstitution` - Component substitution suggestions
- `AiInsight` - Generated insights
- `AiConversation` - Chat conversations
- `AiPrompt` - Prompt templates
- `AiModelConfig` - Model configurations

**AI Features:**
- Product recommendations
- BOM component matching
- Alternate part suggestions
- Price optimization
- Demand forecasting
- Risk predictions
- Natural language search
- Automated descriptions
- Chat assistance

**Substitution Transparency:**
Every AI substitution must show:
- Original MPN
- Suggested MPN
- Matching specifications
- Different specifications
- Risk level
- Reason for suggestion

---

### 28. BOM Domain
**Purpose:** Bill of Materials tools, CSV upload, analysis

**Key Entities:**
- `Bom` - BOM header
- `BomItem` - BOM line items
- `BomUpload` - Upload job tracking
- `BomColumnMapping` - Column mappings
- `BomMatchResult` - Matching results
- `BomAnalysis` - Analysis results
- `BomQuote` - Consolidated quotations
- `BomExport` - Export records

**Features:**
- CSV/XLSX upload
- Column mapping
- MPN recognition
- Manufacturer normalization
- Quantity parsing
- Alternate part suggestions
- Equivalent product suggestions
- Lifecycle warnings
- Stock availability check
- Multi-seller comparison
- Multi-country sourcing
- Lead-time comparison
- Price-break optimization
- Supplier risk assessment
- Compliance filters
- Consolidated quotation
- Partial-match workflow
- Exportable results

---

### 29. Import/Export Domain
**Purpose:** Data import/export, integrations

**Key Entities:**
- `ImportJob` - Import job tracking
- `ExportJob` - Export job tracking
- `ImportMapping` - Field mappings
- `ImportError` - Error logs
- `ApiIntegration` - External API configs
- `WebhookSubscription` - Outgoing webhooks
- `DataSync` - Sync job tracking

---

### 30. Audit Log Domain
**Purpose:** Comprehensive audit trail

**Key Entities:**
- `AuditLog` - Audit log entries
- `AuditTrail` - Entity change trails
- `ImpersonationLog` - Admin impersonation
- `DataAccessLog` - Sensitive data access
- `SecurityEvent` - Security events

**Audited Actions:**
- All create/update/delete operations
- Login/logout events
- Permission changes
- Role assignments
- Password changes
- 2FA changes
- Sensitive data access
- Impersonation sessions
- API key usage
- Export operations
- Settlement actions
- Payout approvals

---

## Domain Communication Patterns

### Events
Each domain publishes events for cross-domain communication:

- `ProductCreated`, `ProductUpdated`, `ProductApproved`
- `OrderPlaced`, `OrderShipped`, `OrderDelivered`, `OrderCancelled`
- `PaymentReceived`, `PaymentFailed`
- `StockUpdated`, `StockLow`, `StockReserved`
- `SellerVerified`, `SellerSuspended`
- `SettlementGenerated`, `PayoutProcessed`
- `TicketCreated`, `TicketResolved`
- `WorkflowApproved`, `WorkflowRejected`

### Jobs
Async processing via queues:

- `ProcessProductImport`
- `GenerateSettlement`
- `SendNotification`
- `CalculateLandedCost`
- `UpdateRiskScores`
- `ProcessBomUpload`
- `GenerateReports`

---

## Module Structure

Each domain follows this structure:

```
app/Domains/{DomainName}/
├── Models/
├── Enums/
├── ValueObjects/
├── Repositories/
├── Services/
├── Policies/
├── Rules/ (Validation)
├── Events/
├── Listeners/
├── Jobs/
├── Notifications/
├── Mail/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Observers/
└── Tests/
```

---

## Cross-Cutting Concerns

### Tenant Isolation
All queries scoped by organization/country where applicable.

### Localization
All user-facing strings translatable, country-specific overrides supported.

### Caching Strategy
- Query caching for static data
- Fragment caching for views
- Cache tags for invalidation
- Country-specific cache namespaces

### Search Integration
- Product search via Elasticsearch/Meilisearch
- Faceted search by category, attributes
- Full-text search on descriptions
- Synonym handling for MPNs

### Rate Limiting
- API rate limits per token
- Form submission limits
- Search query limits
- Country-specific limits

---

## Version History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-01-XX | NeoGiga Team | Initial architecture |
