# NeoGiga Multi-Country Marketplace Implementation

## Overview

This document describes the complete multi-country marketplace architecture implemented for NeoGiga, supporting 35+ countries with localized pricing, payments, taxes, and SEO.

## Architecture Summary

### Subdomain-Based Routing
- **Pattern**: `{country}.neogiga.com` (e.g., `np.neogiga.com`, `in.neogiga.com`)
- **Global Default**: `en.neogiga.com` or `neogiga.com/en`
- **Auto-Redirect**: GeoIP detection redirects users to their country subdomain

### Database Design (Single Global PostgreSQL)

#### Core Tables Created:
1. **countries** - ISO country codes, regions, languages, VAT settings
2. **currencies** - Exchange rates, symbols, precision
3. **marketplaces** - Country-specific configuration, subdomains
4. **marketplace_settings** - Key-value settings per marketplace
5. **warehouses** - Inventory locations with geo-coordinates
6. **tax_rules** - VAT, GST, sales tax by country
7. **pricing_rules** - Markup rules by category/brand/country
8. **payment_gateways** - Local payment methods per country
9. **shipping_rules** - Carrier rates by warehouse/destination
10. **localized_pages** - CMS content per marketplace
11. **localized_seo** - SEO metadata per entity/marketplace
12. **product_localizations** - Translated product content
13. **category_localizations** - Translated categories
14. **brand_localizations** - Translated brands
15. **manufacturer_localizations** - Translated manufacturers
16. **product_marketplace_prices** - Calculated prices per marketplace
17. **inventory_warehouse** - Stock levels per warehouse

### Key Services Implemented

#### 1. MarketplaceResolver Service
```php
App\Services\Marketplace\MarketplaceResolver
```
- Resolves current marketplace from subdomain, GeoIP, cookies, or user preference
- Handles auto-redirect logic
- Caches marketplace lookups

#### 2. PricingEngineService
```php
App\Services\Marketplace\PricingEngineService
```
- Calculates final price with:
  - Base cost (USD)
  - Exchange rate conversion
  - Country/category/brand markups
  - Taxes (VAT, GST, sales tax)
  - Import duty (HS code based)
  - Quantity breaks
  - Price rounding rules

### Middleware

#### MarketplaceRoutingMiddleware
- Intercepts all requests
- Resolves marketplace context
- Auto-redirects from root domain to country subdomain
- Sets locale and shares marketplace with views
- Skips redirect for API, admin, and explicit cookie choices

### Configuration

#### config/marketplaces.php
- 35+ country definitions
- Payment gateway mappings per country
- GeoIP settings
- Cache TTL settings
- Price rounding rules
- SEO defaults

## Supported Countries

| Region | Countries |
|--------|-----------|
| South Asia | NP, IN, BD, LK, PK, BT, MV |
| Middle East | AE, QA, SA |
| Oceania | AU |
| North America | CA, US, MX |
| Europe | UK, DE, FR, IT, ES |
| Asia Pacific | SG, MY, ID, TH, VN, PH, JP, KR |
| South America | BR |
| Africa | ZA, KE, NG, EG |

## Payment Gateway Integration

| Country | Gateways |
|---------|----------|
| Nepal | eSewa, Khalti, FonePay, IME Pay, ConnectIPS, COD |
| India | Razorpay, PayU, Cashfree, PhonePe, UPI, COD |
| Bangladesh | bKash, Nagad, Rocket, COD |
| Sri Lanka | Genie, Frimi, COD |
| Australia | Stripe, PayPal |
| USA | Stripe, PayPal, Authorize.net |
| Global | Stripe, PayPal, Bank Transfer |

## Pricing Formula

```
Final Price = (((Base USD × Exchange Rate) + Markups) + Duty) + Taxes
```

### Markup Priority:
1. Country markup (highest priority)
2. Category markup
3. Brand markup
4. Seller margin

### Tax Calculation:
- Supports compound taxes
- Category exemptions
- Time-based rules (effective dates)

## SEO Strategy

### Per-Marketplace SEO:
- Unique meta titles/descriptions
- Localized keywords
- hreflang tags
- Schema.org markup
- OpenGraph tags
- Twitter cards

### URL Structure:
- Public pages: `https://{country}.neogiga.com/{slug}`
- Product pages: `https://{country}.neogiga.com/product/{slug}`
- Canonical URLs point to marketplace-specific version

## Warehouse & Inventory

### Features:
- Multi-warehouse support
- Real-time stock per warehouse
- Nearest warehouse calculation
- ETA estimation based on distance
- Stock reservation
- Incoming stock tracking

## Implementation Files

### Migrations:
- `database/migrations/marketplace/2024_01_01_000001_create_marketplace_core_tables.php`
- `database/migrations/marketplace/2024_01_01_000002_create_product_localization_tables.php`

### Models:
- `app/Models/Marketplace/Country.php`
- `app/Models/Marketplace/Marketplace.php`
- `app/Models/Marketplace/Currency.php`
- `app/Models/Marketplace/Warehouse.php` (to be created)
- `app/Models/Marketplace/TaxRule.php` (to be created)
- `app/Models/Marketplace/PricingRule.php` (to be created)
- `app/Models/Marketplace/PaymentGateway.php` (to be created)
- `app/Models/Marketplace/LocalizedPage.php` (to be created)
- `app/Models/Marketplace/LocalizedSeo.php` (to be created)
- `app/Models/Marketplace/ProductLocalization.php` (to be created)
- `app/Models/Marketplace/ProductMarketplacePrice.php` (to be created)

### Services:
- `app/Services/Marketplace/MarketplaceResolver.php`
- `app/Services/Marketplace/PricingEngineService.php`

### Middleware:
- `app/Http/Middleware/MarketplaceRoutingMiddleware.php`

### Config:
- `config/marketplaces.php`

## Next Steps

1. **Create remaining models** for all marketplace tables
2. **Seed data** for 35+ countries
3. **Register middleware** in kernel.php
4. **Add GeoIP database** (GeoLite2-Country.mmdb)
5. **Create admin UI** for marketplace management
6. **Build frontend components** for country selector
7. **Implement exchange rate sync** job
8. **Add warehouse inventory** synchronization
9. **Create localized content** management UI
10. **Test geo-routing** with various IP addresses

## Security Considerations

- Cross-marketplace data isolation
- Price manipulation prevention
- Secure payment gateway configuration storage
- GDPR compliance for EU marketplaces
- Data residency requirements

## Performance Optimization

- Redis caching for marketplace resolution
- Price cache with 5-minute TTL
- Edge caching for static content
- CDN for images/assets
- Lazy loading for marketplace switcher

## Testing Checklist

- [ ] Subdomain routing works for all 35 countries
- [ ] GeoIP redirect functions correctly
- [ ] Currency conversion is accurate
- [ ] Tax calculations match local regulations
- [ ] Payment gateways load per country
- [ ] SEO metadata is unique per marketplace
- [ ] Inventory shows nearest warehouse
- [ ] User can manually override country
- [ ] Cookie persistence works across sessions
