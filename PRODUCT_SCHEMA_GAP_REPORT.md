# Product Schema Gap Report

**Project:** NeoGiga Enterprise Catalog Import Center  
**Date:** 2026-07-10  
**Phase:** 1 — Product Schema Audit  

---

## Executive Summary

This report identifies gaps between the current NeoGiga product schema and the requirements for an enterprise-grade catalog import system supporting Mouser API, manufacturer feeds, and multi-source ingestion.

---

## 1. Current Products Table Analysis

### 1.1 Existing Schema

```sql
CREATE TABLE products (
    id BIGINT PRIMARY KEY,
    vendor_id BIGINT NULL,           -- FK to vendors
    brand_id BIGINT NULL,            -- FK to product_brands
    category_id BIGINT NULL,         -- FK to product_categories
    name VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    sku VARCHAR(255) UNIQUE,         -- Global SKU
    mpn VARCHAR(255) NULL,           -- Manufacturer Part Number
    short_description TEXT NULL,
    description LONGTEXT NULL,
    type ENUM('simple','variable','bundle','kit','service','digital'),
    status ENUM('draft','pending','approved','rejected','archived'),
    base_price DECIMAL(12,2),
    cost_price DECIMAL(12,2) NULL,
    sale_price DECIMAL(12,2) NULL,
    sale_start_date DATE NULL,
    sale_end_date DATE NULL,
    tax_class_id INT NULL,
    is_taxable BOOLEAN DEFAULT TRUE,
    track_inventory BOOLEAN DEFAULT TRUE,
    stock_quantity INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 5,
    is_featured BOOLEAN DEFAULT FALSE,
    is_virtual BOOLEAN DEFAULT FALSE,
    is_downloadable BOOLEAN DEFAULT FALSE,
    download_url VARCHAR(255) NULL,
    download_limit INT NULL,
    download_expiry_days INT NULL,
    weight DECIMAL(10,2) NULL,
    length DECIMAL(10,2) NULL,
    width DECIMAL(10,2) NULL,
    height DECIMAL(10,2) NULL,
    weight_unit VARCHAR(50) DEFAULT 'kg',
    dimension_unit VARCHAR(50) DEFAULT 'cm',
    marketplace_visibility JSON NULL,
    attributes JSON NULL,
    metadata JSON NULL,
    seo_meta JSON NULL,
    created_by BIGINT NULL,
    approved_by BIGINT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 1.2 Critical Gaps

| Field | Priority | Reason |
|-------|----------|--------|
| `manufacturer_id` | CRITICAL | No link to manufacturer master table |
| `global_product_id` | CRITICAL | No canonical identity for cross-region products |
| `normalized_mpn` | HIGH | Cannot deduplicate MPNs across sources |
| `lifecycle_status` | HIGH | No end-of-life, obsolete, active status tracking |
| `package_case` | HIGH | Essential for electronics components |
| `mounting_type` | HIGH | Essential for electronics components |
| `eccn` | MEDIUM | Export control classification missing |
| `lead_free` | HIGH | RoHS/REACH compliance flag missing |
| `rohs_status` | HIGH | Regulatory compliance required |
| `reach_status` | MEDIUM | EU chemical compliance missing |
| `moisture_sensitivity_level` | MEDIUM | Critical for component handling |
| `datasheet_url` | HIGH | Currently in separate table only |
| `manufacturer_product_url` | MEDIUM | Link to manufacturer product page |
| `source_id` | CRITICAL | Cannot track which source imported record |
| `source_url` | HIGH | Original source location |
| `source_updated_at` | HIGH | Last update from source |
| `data_quality_score` | HIGH | No quality metric for imports |
| `brand_id` rename | MEDIUM | Should be `manufacturer_id` for clarity |

---

## 2. Recommended Product Schema

### 2.1 Core Identity Fields

```sql
-- Add to products table
ALTER TABLE products ADD COLUMN global_product_id CHAR(36) NULL UNIQUE COMMENT 'UUID for canonical product identity';
ALTER TABLE products ADD COLUMN manufacturer_id BIGINT NULL COMMENT 'FK to manufacturers table';
ALTER TABLE products ADD COLUMN normalized_mpn VARCHAR(255) NULL COMMENT 'Normalized MPN for deduplication';
ALTER TABLE products ADD COLUMN generic_name VARCHAR(255) NULL COMMENT 'Generic product name for search';
```

### 2.2 Lifecycle and Status

```sql
ALTER TABLE products ADD COLUMN lifecycle_status ENUM('active','nrnd','eol','obsolete','preview') NULL;
ALTER TABLE products ADD COLUMN lifecycle_status_changed_at TIMESTAMP NULL;
ALTER TABLE products ADD COLUMN successor_product_id BIGINT NULL COMMENT 'Replacement product if EOL';
```

### 2.3 Physical Characteristics

```sql
ALTER TABLE products ADD COLUMN package_case VARCHAR(100) NULL COMMENT 'Package/case type';
ALTER TABLE products ADD COLUMN mounting_type VARCHAR(100) NULL COMMENT 'Mounting method';
ALTER TABLE products ADD COLUMN pin_count INT NULL;
ALTER TABLE products ADD COLUMN dimensions_json JSON NULL COMMENT 'L×W×H with units';
```

### 2.4 Compliance and Regulatory

```sql
ALTER TABLE products ADD COLUMN country_of_origin_code CHAR(2) NULL;
ALTER TABLE products ADD COLUMN hs_code VARCHAR(20) NULL COMMENT 'Harmonized System code';
ALTER TABLE products ADD COLUMN eccn VARCHAR(20) NULL COMMENT 'Export Control Classification Number';
ALTER TABLE products ADD COLUMN lead_free BOOLEAN DEFAULT NULL;
ALTER TABLE products ADD COLUMN rohs_status ENUM('compliant','non-compliant','exempt','unknown') NULL;
ALTER TABLE products ADD COLUMN reach_status ENUM('compliant','non-compliant','svhc','unknown') NULL;
ALTER TABLE products ADD COLUMN moisture_sensitivity_level VARCHAR(10) NULL COMMENT 'MSL rating';
ALTER TABLE products ADD COLUMN flammability_rating VARCHAR(10) NULL;
```

### 2.5 Source Tracking

```sql
ALTER TABLE products ADD COLUMN source_id BIGINT NULL COMMENT 'FK to catalog_sources';
ALTER TABLE products ADD COLUMN source_url VARCHAR(500) NULL;
ALTER TABLE products ADD COLUMN source_external_id VARCHAR(255) NULL;
ALTER TABLE products ADD COLUMN source_updated_at TIMESTAMP NULL;
ALTER TABLE products ADD COLUMN source_payload JSON NULL COMMENT 'Original imported data';
ALTER TABLE products ADD COLUMN import_batch_id BIGINT NULL COMMENT 'FK to import_batches';
```

### 2.6 Data Quality

```sql
ALTER TABLE products ADD COLUMN data_quality_score INT NULL DEFAULT 0 COMMENT '0-100 quality score';
ALTER TABLE products ADD COLUMN data_quality_flags JSON NULL COMMENT 'Quality issues found';
ALTER TABLE products ADD COLUMN reviewed_at TIMESTAMP NULL;
ALTER TABLE products ADD COLUMN reviewed_by BIGINT NULL;
```

### 2.7 URLs and Assets

```sql
ALTER TABLE products ADD COLUMN datasheet_url VARCHAR(500) NULL;
ALTER TABLE products ADD COLUMN manufacturer_product_url VARCHAR(500) NULL;
ALTER TABLE products ADD COLUMN primary_image_url VARCHAR(500) NULL;
ALTER TABLE products ADD COLUMN additional_images_json JSON NULL;
```

### 2.8 Version History

```sql
-- Create new table for version tracking
CREATE TABLE product_versions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    version_number INT NOT NULL,
    changed_fields JSON NULL,
    previous_values JSON NULL,
    change_reason VARCHAR(255) NULL,
    changed_by BIGINT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_version (product_id, version_number DESC)
);
```

---

## 3. Manufacturers Table (New)

### 3.1 Proposed Schema

```sql
CREATE TABLE manufacturers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    legal_name VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    aliases JSON NULL COMMENT 'Array of alternative names',
    official_website VARCHAR(500) NULL,
    logo_path VARCHAR(500) NULL,
    country_code CHAR(2) NULL,
    status ENUM('active','inactive','acquired','merged') DEFAULT 'active',
    successor_manufacturer_id BIGINT NULL,
    authorization_status ENUM('authorized','unauthorized','unknown') DEFAULT 'unknown',
    data_quality_score INT NULL DEFAULT 0,
    external_source_id VARCHAR(255) NULL,
    source_url VARCHAR(500) NULL,
    reviewed_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_legal_name (legal_name),
    INDEX idx_display_name (display_name)
);
```

### 3.2 Manufacturer Aliases Table

```sql
CREATE TABLE manufacturer_aliases (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    manufacturer_id BIGINT NOT NULL,
    alias_name VARCHAR(255) NOT NULL,
    alias_type ENUM('abbreviation','former_name','dbas','common_misspelling') NULL,
    confidence_score DECIMAL(3,2) DEFAULT 1.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_alias (alias_name),
    INDEX idx_alias_name (alias_name)
);
```

### 3.3 Manufacturer External IDs

```sql
CREATE TABLE manufacturer_external_ids (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    manufacturer_id BIGINT NOT NULL,
    source_id BIGINT NOT NULL,
    external_id VARCHAR(255) NOT NULL,
    source_url VARCHAR(500) NULL,
    last_verified_at TIMESTAMP NULL,
    
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id) ON DELETE CASCADE,
    FOREIGN KEY (source_id) REFERENCES catalog_sources(id),
    UNIQUE KEY unique_source_external (source_id, external_id)
);
```

---

## 4. Regional SKU Separation

### 4.1 Problem

Current schema mixes global product identity with regional commercial data (prices, stock). This prevents:
- Multi-region pricing
- Currency-specific pricing
- Marketplace-specific SKUs
- Independent inventory per region

### 4.2 Solution: Regional SKU Tables

```sql
CREATE TABLE regional_skus (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    global_product_id BIGINT NOT NULL,
    marketplace_id BIGINT NOT NULL,
    regional_sku VARCHAR(255) NOT NULL,
    country_code CHAR(2) NULL,
    currency_code CHAR(3) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (global_product_id) REFERENCES products(id),
    FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id),
    UNIQUE KEY unique_marketplace_sku (marketplace_id, regional_sku),
    INDEX idx_global_product (global_product_id)
);

CREATE TABLE regional_prices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    regional_sku_id BIGINT NOT NULL,
    price_type ENUM('base','sale','cost','msrp') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency_code CHAR(3) NOT NULL,
    quantity_break INT NULL DEFAULT 1,
    valid_from DATE NULL,
    valid_until DATE NULL,
    last_synced_at TIMESTAMP NULL,
    
    FOREIGN KEY (regional_sku_id) REFERENCES regional_skus(id),
    INDEX idx_sku_type (regional_sku_id, price_type),
    INDEX idx_validity (valid_from, valid_until)
);

CREATE TABLE warehouses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('own','3pl','dropship','vendor') DEFAULT 'own',
    country_code CHAR(2) NOT NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state_province VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE inventory_records (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    regional_sku_id BIGINT NOT NULL,
    warehouse_id BIGINT NOT NULL,
    quantity_on_hand INT NOT NULL DEFAULT 0,
    quantity_available INT NOT NULL DEFAULT 0,
    quantity_reserved INT NOT NULL DEFAULT 0,
    quantity_incoming INT NOT NULL DEFAULT 0,
    reorder_point INT NULL,
    reorder_quantity INT NULL,
    last_counted_at TIMESTAMP NULL,
    last_synced_at TIMESTAMP NULL,
    freshness_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (regional_sku_id) REFERENCES regional_skus(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    UNIQUE KEY unique_sku_warehouse (regional_sku_id, warehouse_id),
    INDEX idx_freshness (freshness_timestamp)
);

CREATE TABLE seller_offers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    global_product_id BIGINT NOT NULL,
    seller_id BIGINT NOT NULL,
    condition ENUM('new','refurbished','used','open_box') DEFAULT 'new',
    price DECIMAL(12,2) NOT NULL,
    currency_code CHAR(3) NOT NULL,
    quantity_available INT NOT NULL DEFAULT 0,
    moq INT NOT NULL DEFAULT 1,
    order_multiple INT DEFAULT 1,
    lead_time_days INT NULL,
    shipping_cost DECIMAL(10,2) NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    valid_from TIMESTAMP NULL,
    valid_until TIMESTAMP NULL,
    
    FOREIGN KEY (global_product_id) REFERENCES products(id),
    FOREIGN KEY (seller_id) REFERENCES vendors(id),
    INDEX idx_product_seller (global_product_id, seller_id),
    INDEX idx_active (is_active, is_featured)
);
```

---

## 5. Migration Strategy

### 5.1 Phase 1: Non-Breaking Additions

1. Add nullable columns to `products` table
2. Create new `manufacturers` table
3. Create `manufacturer_aliases` table
4. Create `manufacturer_external_ids` table

### 5.2 Phase 2: Data Migration

1. Migrate existing `brand_id` values to new `manufacturer_id`
2. Populate `manufacturers` from `product_brands`
3. Create backward-compatible view for `brand_id`
4. Update application code gradually

### 5.3 Phase 3: Regional Separation

1. Create `regional_skus`, `regional_prices`, `inventory_records`
2. Migrate existing price/stock data
3. Update read queries to use regional tables
4. Deprecate direct price fields on products

### 5.4 Rollback Plan

- All new tables can be dropped without affecting core products
- New columns are nullable with defaults
- Backward compatibility views maintain old API contracts

---

## 6. Impact Analysis

### 6.1 Affected Models

| Model | Change Type | Effort |
|-------|-------------|--------|
| Product | Extend with new fields | Medium |
| ProductBrand | Rename/deprecate | Low |
| New: Manufacturer | Create | Medium |
| New: RegionalSku | Create | Medium |
| New: RegionalPrice | Create | Low |
| New: Warehouse | Extend | Low |
| New: InventoryRecord | Create | Medium |

### 6.2 Affected Controllers

| Controller | Change Required |
|------------|-----------------|
| ProductController | Add manufacturer filter |
| ProductAdminController | Support new fields |
| ImportExportController | Major rewrite |
| New: CatalogImportController | Create |

### 6.3 API Changes

- `/api/v1/products` - Add manufacturer fields to response
- `/api/v1/manufacturers` - New endpoint
- `/api/v1/products/{id}/pricing` - New regional pricing endpoint
- `/api/v1/products/{id}/inventory` - New inventory endpoint

---

## 7. Validation Rules

### 7.1 MPN Validation

```php
// Normalize MPN: remove spaces, special chars, uppercase
public function normalizeMpn(string $mpn): string
{
    return strtoupper(preg_replace('/[^A-Z0-9]/', '', $mpn));
}

// Validate format (alphanumeric, dashes allowed)
public function validateMpn(string $mpn): bool
{
    return preg_match('/^[A-Z0-9\-]+$/', strtoupper($mpn));
}
```

### 7.2 Duplicate Detection

```sql
-- Find duplicate MPNs across manufacturers
SELECT normalized_mpn, COUNT(*) as count
FROM products
WHERE normalized_mpn IS NOT NULL
GROUP BY normalized_mpn
HAVING count > 1;

-- Find potential duplicates by name + manufacturer
SELECT p1.id, p2.id
FROM products p1
JOIN products p2 ON p1.manufacturer_id = p2.manufacturer_id
WHERE p1.normalized_mpn = p2.normalized_mpn
AND p1.id != p2.id;
```

---

## 8. Conclusion

The current product schema requires significant extension to support enterprise catalog imports. Key priorities:

1. **Add manufacturer master** with alias resolution
2. **Separate regional pricing** from global product identity
3. **Add compliance fields** for regulatory requirements
4. **Implement source tracking** for audit trails
5. **Add data quality scoring** for import validation

Estimated effort: 3-4 sprints for full implementation with testing.

---

**Document Version:** 1.0  
**Author:** Database Architect  
**Review Status:** Pending technical review
