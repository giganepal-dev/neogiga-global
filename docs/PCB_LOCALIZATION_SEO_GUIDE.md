# PCB Localization & SEO Guide

## 1. Executive Summary

This guide defines the **NeoGiga PCB Localization and SEO Strategy**, ensuring pcb.neogiga.com serves global markets with appropriate language, currency, pricing, and search visibility.

**Core Principle:** URL prefix is authoritative for marketplace context. Public pages are SEO-optimized; private pages are strictly noindex.

## 2. Localization Architecture

### 2.1 URL Structure
```
Global English:
pcb.neogiga.com/en          → Canonical global storefront

Localized Marketplaces:
pcb.neogiga.com/np          → Nepal (NPR, Nepali/English)
pcb.neogiga.com/in          → India (INR, Hindi/English)
pcb.neogiga.com/bd          → Bangladesh (BDT, Bengali/English)
pcb.neogiga.com/mm          → Myanmar (MMK, Burmese/English)
pcb.neogiga.com/au          → Australia (AUD, English)
```

### 2.2 Alternative: Global App with Context
If subdirectory complexity is too high:
```
pcb.neogiga.com             → Detects locale from user profile/cookie
pcb.neogiga.com/projects    → Global app, marketplace context from neogiga.com session
```

**Recommendation:** Use URL prefix for public marketing pages, carry marketplace context from neogiga.com for authenticated app routes.

### 2.3 Localized Elements
| Element | Strategy |
|---------|----------|
| Language | Content translated; UI labels from i18n JSON |
| Currency | Display in local currency; settle in USD or local |
| Pricing | Regional supplier prices + freight + duty |
| Tax | VAT/GST calculated per destination country |
| Freight | Local logistics partners, ETA in local timezone |
| Payment | Local methods (eSewa Nepal, UPI India, etc.) |
| Support | Local phone/email, timezone-adjusted hours |
| Warranty | Country-specific terms |
| SEO | Localized keywords, hreflang tags |

## 3. Implementation Details

### 3.1 Middleware for Locale Detection
```php
// app/Http/Middleware/PcbLocaleMiddleware.php
public function handle(Request $request, Closure $next)
{
    // 1. Check URL prefix (/np, /in, etc.)
    $locale = $request->segment(1);
    
    if (!in_array($locale, ['en', 'np', 'in', 'bd', 'mm', 'au'])) {
        $locale = 'en'; // Default
    }
    
    // 2. Set application locale
    App::setLocale($locale);
    
    // 3. Set marketplace context
    $marketplace = Marketplace::where('code', $locale)->first();
    session(['pcb.marketplace' => $marketplace]);
    
    // 4. Set currency
    $currency = $marketplace->default_currency ?? 'USD';
    session(['pcb.currency' => $currency);
    
    return $next($request);
}
```

### 3.2 Price Calculation by Region
```php
class PcbRegionalPricingService
{
    public function calculate(PcbQuote $quote, string $countryCode): RegionalPrice
    {
        // Base price from supplier
        $basePrice = $quote->total_price;
        
        // Get regional supplier offers
        $regionalOffers = SellerOffer::forCountry($countryCode)
            ->forProducts($quote->componentProductIds)
            ->get();
        
        // Recalculate component cost with local suppliers
        $localComponentCost = $this->sumLocalPrices($regionalOffers);
        
        // Add freight based on destination
        $freight = FreightService::estimate(
            $quote->supplierLocation,
            $countryCode,
            $quote->weight
        );
        
        // Calculate duty/tax
        $duty = DutyService::calculate($basePrice, $countryCode, 'PCB');
        $tax = TaxService::calculate($basePrice + $freight + $duty, $countryCode);
        
        // Convert to local currency
        $exchangeRate = ExchangeRateService::getRate($quote->currency, $localCurrency);
        
        return new RegionalPrice(
            subtotal: $basePrice * $exchangeRate,
            freight: $freight * $exchangeRate,
            duty: $duty * $exchangeRate,
            tax: $tax * $exchangeRate,
            total: ($basePrice + $freight + $duty + $tax) * $exchangeRate,
            currency: $localCurrency
        );
    }
}
```

### 3.3 Translation Management
- **Framework:** Laravel's built-in i18n + Nuxt i18n module.
- **Files:** `resources/lang/{locale}/pcb.php`.
- **Process:** 
  1. Extract keys from codebase.
  2. Send to professional translators (technical expertise required).
  3. Review with native-speaking engineers.
  4. Deploy via CI/CD.

Example translation file (`resources/lang/np/pcb.php`):
```php
return [
    'quote_configurator' => 'कोट कन्फिगरेटर',
    'upload_gerber' => 'गर्बर अपलोड गर्नुहोस्',
    'layers' => 'तहहरू',
    'material' => 'सामग्री',
    'thickness' => 'मोटाई',
    'get_quote' => 'कोट प्राप्त गर्नुहोस्',
];
```

## 4. SEO Strategy

### 4.1 Target Keywords (by Page)

| Page | Primary Keywords | Secondary Keywords |
|------|------------------|-------------------|
| Homepage | PCB manufacturing, PCB assembly service | prototype PCB, turnkey PCBA, electronics manufacturing |
| /pcb-quote | instant PCB quote, PCB price calculator | online PCB ordering, custom PCB fabrication |
| /pcb-design | PCB design service, schematic capture | PCB layout designer, Altium designer services |
| /pcb-assembly | PCBA assembly, SMT assembly service | through-hole assembly, box build assembly |
| /component-sourcing | electronic component sourcing, BOM sourcing | hard-to-find components, LCSC alternative |
| /capabilities | PCB capabilities, multilayer PCB | HDI PCB, aluminum PCB, flex PCB |
| /resources | PCB tutorials, KiCad guide | DFM guide, Gerber export tutorial |

### 4.2 On-Page SEO Elements

#### Title Tags (60 chars max)
```
Homepage: "PCB Manufacturing & Assembly Service | NeoGiga PCB"
Quote: "Instant PCB Quote Calculator | Upload Gerber | NeoGiga"
Design: "Professional PCB Design Service | Schematic to Layout | NeoGiga"
Assembly: "PCBA Assembly Service | SMT & Through-Hole | NeoGiga"
```

#### Meta Descriptions (155 chars max)
```
Homepage: "Build, source, and manufacture your electronics with NeoGiga PCB. Instant quotes, global component sourcing, and turnkey assembly. Get started today."
Quote: "Upload your Gerber files and get an instant PCB fabrication quote. Configure layers, material, finish, and quantity. Fast turnaround, competitive pricing."
```

#### H1 Headers
- One H1 per page, keyword-rich but natural.
- Example: `<h1>Professional PCB Fabrication & Assembly Services</h1>`

### 4.3 Structured Data (Schema.org)

#### Service Schema (Homepage)
```json
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "NeoGiga PCB Manufacturing",
  "provider": {
    "@type": "Organization",
    "name": "NeoGiga",
    "url": "https://pcb.neogiga.com"
  },
  "serviceType": "PCB Fabrication and Assembly",
  "areaServed": "Global",
  "hasOfferCatalog": {
    "@type": "OfferCatalog",
    "name": "PCB Services",
    "itemListElement": [
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "PCB Fabrication",
          "description": "2-16 layer FR-4 PCBs, quick turnaround"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "PCBA Assembly",
          "description": "SMT and through-hole assembly service"
        }
      }
    ]
  }
}
```

#### FAQ Schema (Capabilities Page)
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is your minimum order quantity?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "We support prototypes starting from 1 board up to mass production quantities."
      }
    },
    {
      "@type": "Question",
      "name": "What file formats do you accept?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Gerber RS-274X, Excellon drill files, IPC-356 netlist, and native EDA files (KiCad, Altium, Eagle)."
      }
    }
  ]
}
```

### 4.4 Technical SEO

#### Sitemap Configuration
```xml
<!-- sitemap-pcb.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
  
  <!-- Public pages only -->
  <url>
    <loc>https://pcb.neogiga.com/en</loc>
    <lastmod>2024-11-01</lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://pcb.neogiga.com/en/pcb-quote</loc>
    <priority>0.9</priority>
  </url>
  
  <!-- Hreflang for localized versions -->
  <url>
    <loc>https://pcb.neogiga.com/en</loc>
    <xhtml:link rel="alternate" hreflang="ne" href="https://pcb.neogiga.com/np" />
    <xhtml:link rel="alternate" hreflang="hi" href="https://pcb.neogiga.com/in" />
    <xhtml:link rel="alternate" hreflang="bn" href="https://pcb.neogiga.com/bd" />
  </url>
  
</urlset>
```

#### Robots.txt
```txt
User-agent: *
Allow: /en/
Allow: /np/
Allow: /in/
Allow: /bd/
Allow: /mm/
Allow: /au/

# Block all private routes
Disallow: /projects
Disallow: /quote
Disallow: /bom
Disallow: /orders
Disallow: /admin

# Block file uploads
Disallow: /api/v1/pcb/files

Sitemap: https://pcb.neogiga.com/sitemap-pcb.xml
```

#### Meta Robots for Private Pages
```html
<!-- All authenticated pages must include -->
<meta name="robots" content="noindex, nofollow, noarchive" />
```

### 4.5 International SEO (hreflang)

```html
<head>
  <!-- Self-referential canonical -->
  <link rel="canonical" href="https://pcb.neogiga.com/en" />
  
  <!-- Hreflang alternatives -->
  <link rel="alternate" hreflang="en" href="https://pcb.neogiga.com/en" />
  <link rel="alternate" hreflang="ne" href="https://pcb.neogiga.com/np" />
  <link rel="alternate" hreflang="hi" href="https://pcb.neogiga.com/in" />
  <link rel="alternate" hreflang="bn" href="https://pcb.neogiga.com/bd" />
  <link rel="alternate" hreflang="my" href="https://pcb.neogiga.com/mm" />
  <link rel="alternate" hreflang="en-au" href="https://pcb.neogiga.com/au" />
  
  <!-- x-default for unmatched locales -->
  <link rel="alternate" hreflang="x-default" href="https://pcb.neogiga.com/en" />
</head>
```

## 5. Content Strategy

### 5.1 Resource Hub Topics
- **PCB Design Tutorials:** KiCad, Altium, EasyEDA guides.
- **DFM Guides:** How to design for manufacturability.
- **Component Selection:** Reading datasheets, avoiding obsolescence.
- **Assembly Tips:** SMT vs. THT, panelization best practices.
- **Case Studies:** Real customer projects (with permission).

### 5.2 Localization Priority
| Phase | Markets | Languages | Effort |
|-------|---------|-----------|--------|
| 1 | Global | English | Baseline |
| 2 | Nepal | Nepali + English | High |
| 3 | India | Hindi + English | High |
| 4 | Bangladesh | Bengali + English | Medium |
| 5 | Australia | English (AU variants) | Low |

## 6. Testing & Validation

### 6.1 Localization Tests
- **Currency Display:** Verify correct symbol and formatting.
- **Date/Time:** Local timezone, DD/MM/YYYY vs. MM/DD/YYYY.
- **Number Formatting:** Decimal separators (1.234,56 vs 1,234.56).
- **RTL Support:** If expanding to Arabic markets later.
- **Translation Completeness:** No missing keys fallback to English.

### 6.2 SEO Tests
- **Crawl Test:** Screaming Frog to verify indexable pages.
- **Meta Audit:** Check title/description length and uniqueness.
- **Structured Data:** Google Rich Results Test.
- **Mobile-Friendly:** Google Mobile-Friendly Test.
- **Core Web Vitals:** Lighthouse performance scoring.

## 7. Deployment Checklist

- [ ] Configure locale middleware and routes.
- [ ] Create translation files for target languages.
- [ ] Set up currency conversion service integration.
- [ ] Configure regional tax/duty calculation.
- [ ] Generate XML sitemaps per locale.
- [ ] Implement hreflang tags on all public pages.
- [ ] Add noindex meta to all private routes.
- [ ] Submit sitemaps to Google Search Console.
- [ ] Verify geo-targeting in GSC for each subdirectory.
- [ ] Test localized checkout flows end-to-end.

## 8. Monitoring & Optimization

- **Analytics:** Track traffic by locale, conversion rates per market.
- **Search Console:** Monitor impressions/clicks by country.
- **Rank Tracking:** SERP positions for local keywords.
- **A/B Testing:** Test CTAs, pricing displays by region.
- **Feedback Loop:** Collect user feedback on translations.
