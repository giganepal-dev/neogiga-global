# Product & Category SEO Audit

**Date:** 2026-07-10  
**Scope:** Product and Category SEO metadata generation

## Current State Analysis

### Product SEO

#### Database Schema (`product_seo_meta`)
```sql
-- From migration 2026_07_10_000800_extend_product_admin_shell_tables.php
- id
- product_id (FK)
- meta_title (string, nullable)
- meta_description (text, nullable)
- canonical_url (string, nullable)
- robots (string, default: 'index,follow')
- schema_type (string, default: 'Product')
- confidence_level (string, default: 'manual')
- metadata (JSON, nullable)
- timestamps
```

#### Current Generation Logic
Location: `app/Services/Seo/GlobalSeoI18nService.php::productSeo()`

```php
return [
    'locale' => $marketplace['locale'] ?? 'en',
    'currency' => $marketplace['currency'] ?? 'USD',
    'title' => Str::limit(strtr(config('neogiga_global.seo_templates.product_title'), $replacements), 90, ''),
    'description' => Str::limit(strtr(config('neogiga_global.seo_templates.product_description'), $replacements), 158, ''),
    'canonical' => 'https://neogiga.com/' . $prefix . '/products/' . Str::slug($replacements['{name}']),
    'robots' => 'noindex,nofollow', // HARDCODED - ISSUE #1
    'structured_data_type' => 'Product',
];
```

#### Issues Identified

1. **Robots Hardcoded to noindex,nofollow**
   - Line 56 returns `'robots' => 'noindex,nofollow'` for ALL products
   - No logic for publication status, content completeness
   - Should be deterministic based on multiple factors

2. **Canonical URL Broken**
   - Uses `Str::slug($replacements['{name}'])` instead of actual product slug
   - Does not use regional domain configuration
   - Results in incomplete URLs like `https://neogiga.com/en/products/`

3. **Title Pattern Wrong**
   - Current: `{mpn} in {country} | {brand} | Local Stock & RFQ Sourcing`
   - Required: `Buy {Product Name} on NeoGiga {Marketplace} | NeoGiga Engineering Marketplace`
   - MPN-first is bad for branding
   - No warehouse fulfilment phrase

4. **Description Pattern Wrong**
   - Current: `Source {name} through {brand}. Availability, tax, warranty...`
   - Required: `Buy {Product Name} on NeoGiga {Marketplace} Engineering Marketplace. Low MOQ, Quality Products, B2B Sourcing from {Country} Warehouse.`

5. **No Manual Override Support**
   - No fields for manual vs generated values
   - No preservation of admin edits
   - No version tracking

6. **No Marketplace Differentiation**
   - Same pattern for all regions
   - No Nepal-specific patterns
   - No warehouse availability awareness

### Category SEO

#### Database Schema (`product_categories`)
```sql
-- From migration 2026_07_06_014808_create_product_categories_table.php
- id
- parent_id (FK, nullable)
- name (string)
- slug (string, unique)
- description (text, nullable)
- icon_path (string, nullable)
- image_path (string, nullable)
- sort_order (integer)
- is_active (boolean)
- is_featured (boolean)
- marketplace_visibility (JSON, nullable)
- seo_meta (JSON, nullable) -- single JSON column for all SEO data
- timestamps
```

#### Current State
- No dedicated category SEO meta table
- SEO stored in `seo_meta` JSON column
- No structured generation logic found
- No regional differentiation

#### Required Fields (Missing)
- `generated_seo_title`
- `generated_seo_description`
- `manual_seo_title`
- `manual_seo_description`
- `use_manual_override` (boolean)
- `canonical_url`
- `robots`
- `last_generated_at`
- `last_manually_edited_at`
- `template_version`
- `marketplace_id`
- `locale`

## Required Patterns

### Global Product Pattern

**Title:**
```
Buy {Product Name} on NeoGiga Global | NeoGiga Engineering Marketplace
```

**Description:**
```
Buy {Product Name} on NeoGiga Engineering Marketplace. Low MOQ, Quality Products, B2B Sourcing from Regional Warehouse.
```

**Example:**
```
Title: Buy 0.56" Red Light 3 Digits Common Anode LED on NeoGiga Global | NeoGiga Engineering Marketplace
Description: Buy 0.56" Red Light 3 Digits Common Anode LED on NeoGiga Engineering Marketplace. Low MOQ, Quality Products, B2B Sourcing from Regional Warehouse.
```

### Nepal Product Pattern

**Title:**
```
Buy {Product Name} on NeoGiga Nepal | NeoGiga Engineering Marketplace
```

**Description:**
```
Buy {Product Name} on NeoGiga Nepal Engineering Marketplace. Low MOQ, Quality Products, B2B Sourcing from Nepal Warehouse.
```

### Regional Product Pattern (India, Bangladesh, etc.)

**Title:**
```
Buy {Product Name} on NeoGiga {Country Name} | NeoGiga Engineering Marketplace
```

**Description:**
```
Buy {Product Name} on NeoGiga {Country Name} Engineering Marketplace. Low MOQ, Quality Products, B2B Sourcing from {Country Name} Warehouse.
```

### Warehouse Fulfilment Phrase Configuration

When no local warehouse exists, use configured alternatives:
- `B2B Sourcing with Regional Fulfilment.`
- `B2B Sourcing from Approved Regional Suppliers.`
- `B2B Sourcing with Cross-Border Delivery.`
- `B2B Sourcing through NeoGiga Regional Network.`

**Marketplace Configuration Fields Needed:**
```php
'seo_marketplace_name' => 'NeoGiga Nepal',
'seo_site_suffix' => 'NeoGiga Engineering Marketplace',
'seo_fulfilment_phrase' => 'B2B Sourcing from Nepal Warehouse.',
'has_local_warehouse' => true,
'warehouse_display_name' => 'Nepal Warehouse',
```

### Global Category Pattern

**Title:**
```
Buy {Category Name} on NeoGiga Global | NeoGiga Engineering Marketplace
```

**Description:**
```
Buy {Category Name} on NeoGiga Engineering Marketplace. Explore Quality Products, Low MOQ and B2B Sourcing from Regional Warehouse.
```

### Regional Category Pattern

**Title:**
```
Buy {Category Name} on NeoGiga {Country Name} | NeoGiga Engineering Marketplace
```

**Description:**
```
Buy {Category Name} on NeoGiga {Country Name} Engineering Marketplace. Explore Quality Products, Low MOQ and B2B Sourcing from {Country Name} Warehouse.
```

### Subcategory Handling

- Apply same country-aware structure
- Do not append parent category names when repetitive
- Use `seo_short_name` if available for long names

### Brand Handling

**Deduplication Rule:**
- Bad: `Buy Waveshare Waveshare 7 Inch HDMI Display...`
- Good: `Buy Waveshare 7 Inch HDMI Display...`

**Configurable Title Modes:**
- `product_name` (default)
- `brand_product_name`
- `product_name_mpn`
- `brand_product_name_mpn`

### MPN Handling

- Do not force MPN into every title
- For technical semiconductors, allow: `Buy {Brand} {MPN} {Product Type}...`
- Never append empty/unknown MPN values

## Character Length Management

### SEO Title Thresholds
- Preferred: up to 60 characters
- Warning: over 65 characters
- Critical: over 75 characters

### SEO Description Thresholds
- Preferred: 140-160 characters
- Warning: over 165 characters
- Critical: over 180 characters

### Fallback Strategy (when exceeding max)
1. Remove "Buy" only if configured
2. Replace "NeoGiga Engineering Marketplace" with "NeoGiga"
3. Remove nonessential product modifiers (if safe short name exists)
4. Use admin-defined `seo_short_name`
5. Keep full title with advisory warning (never cut MPN mid-string)

## Character Normalization Rules

- Convert repeated spaces to single space
- Normalize straight and curly quotation marks
- Preserve meaningful inch symbols (`"`)
- Preserve model numbers
- Preserve decimal values
- Preserve hyphens in MPNs
- Remove accidental duplicate punctuation
- Remove HTML entities from generated text
- Escape HTML safely during rendering

## Manual Override System

### Required Fields per Entity
```php
// Generated values
'generated_seo_title' => string|null,
'generated_seo_description' => string|null,

// Manual overrides
'manual_seo_title' => string|null,
'manual_seo_description' => string|null,

// Control flags
'use_manual_override' => boolean,

// Tracking
'last_generated_at' => timestamp|null,
'last_manually_edited_at' => timestamp|null,
'edited_by' => user_id|null,
'template_version' => string,

// Context
'marketplace_id' => FK,
'locale' => string,
'approval_status' => string, // draft|approved|rejected

// Additional
'seo_short_name' => string|null,
'source_notes' => text|null,
'generation_source' => string,
```

### Override Rules
1. Manual override always takes priority
2. Template regeneration must not overwrite approved manual metadata
3. Admin can reset manual override to generated mode
4. Store version history
5. Support rollback
6. Show generated vs active values in UI

## Admin UI Requirements

### Field Labels (Human Readable)
- SEO Title
- Meta Description
- Canonical URL
- Robots
- Schema Type
- Confidence Level
- Source Notes
- Generation Source
- Marketplace
- Country
- Template Version
- Last Updated
- Manual Override

### Badge States
- Generated
- Manual
- Approved
- Advisory
- Needs Review
- Indexable
- Noindex
- Missing Source
- Regional Override

### Interactive Features
- Title character counter (live)
- Description character counter (live)
- Search result preview
- Marketplace selector
- Global/regional comparison view
- Regenerate button
- Reset to default pattern
- Save override button
- Preview canonical URL
- Preview hreflang links
- Preview structured data (JSON-LD)
- Validation warnings display

### Readable Status Conversion
Convert raw codes to readable labels:
- `low_missing_source_detail` → "Low confidence — missing source detail"
- `manual` → "Manual override"
- `generated` → "Auto-generated"
- `approved` → "Approved"

## Indexability (Robots) Rules

### index,follow (when ALL are true)
- Product/category is published
- Has valid slug
- Canonical URL is valid
- Has meaningful name
- Is not a duplicate
- Is not blocked
- Is visible for the marketplace
- Has sufficient verified catalog content
- Product page renders successfully

### noindex,follow (when ANY are true)
- Content is incomplete
- Source confidence is too low
- Product is a duplicate candidate
- Regional page is not yet ready
- Product is unpublished
- Product has no meaningful content beyond title
- Canonical mapping is unresolved

### noindex,nofollow (ONLY for exceptional cases)
- Private pages
- Blocked by administrator
- Staging environment
- Unsafe content

### Admin Explanation Required
Show clear reason for noindex status:
- "Missing verified product source"
- "Duplicate product candidate"
- "Unpublished product"
- "Missing regional page mapping"
- "Insufficient product detail"
- "Product blocked by administrator"

## Canonical URL Requirements

### Format
- Absolute URL
- Correct marketplace domain
- Correct locale prefix
- Correct entity slug
- Self-referencing for valid regional pages
- No query parameters
- No trailing partial route
- No admin URL
- No API URL

### Examples
```
Global: https://neogiga.com/en/products/0-56-red-light-3-digits-common-anode-led
Nepal: https://np.neogiga.com/en/products/0-56-red-light-3-digits-common-anode-led
India: https://in.neogiga.com/en/products/0-56-red-light-3-digits-common-anode-led
```

### Domain Resolution
Use marketplace configuration as source of truth:
- If configured domain is `np.neogiga.com`, use it
- If live uses `giganepal.com`, respect that configuration
- Record any domain inconsistency in audit

## Hreflang Requirements

### Reciprocal Links
Generate for equivalent marketplace pages:
- `x-default` → global hub
- `en` → English global
- `en-NP` → Nepal
- `en-IN` → India
- `en-BD` → Bangladesh
- `en-LK` → Sri Lanka
- `en-PK` → Pakistan

### Inclusion Rules
Only include regional page when:
- Page exists
- Page is indexable OR intentionally published
- Not a 404
- Not unpublished
- Not unresolved

Never generate hreflang to broken/unpublished pages.

## Sitemap Integration

### Inclusion Rules
Include only:
- Canonical URLs
- Indexable pages
- Published entities
- Valid slugs

Exclude:
- Noindex pages
- Duplicate candidates
- Unpublished entities
- Pages without valid slugs

## Implementation Priority

### Phase 1: Foundation
1. Database migrations for extended fields
2. Centralized SeoTemplateService
3. Marketplace context resolver
4. Basic template patterns

### Phase 2: Product SEO
1. Product title/description generation
2. Regional differentiation
3. Warehouse fulfilment phrases
4. Brand deduplication
5. MPN handling modes

### Phase 3: Category SEO
1. Category title/description generation
2. Subcategory handling
3. Parent chain management

### Phase 4: Admin UI
1. Character counters
2. Preview panels
3. Manual override controls
4. Readable status labels

### Phase 5: Indexability
1. Deterministic robots rules
2. Publication status checks
3. Content completeness validation

### Phase 6: Advanced Features
1. Canonical URL service
2. Hreflang generation
3. Sitemap integration
4. Cache invalidation

### Phase 7: Backfill & Testing
1. Dry-run commands
2. Bulk regeneration
3. Audit reports
4. Comprehensive tests
