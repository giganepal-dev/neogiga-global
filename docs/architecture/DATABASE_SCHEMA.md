# NeoGiga Database Schema

## Overview

This document defines the complete database schema for NeoGiga multi-vendor marketplace. All tables use InnoDB engine with UTF-8mb4 encoding. Foreign key constraints are enforced.

## Naming Conventions

- Table names: `snake_case` plural (e.g., `product_variants`)
- Primary keys: `id` (BIGINT UNSIGNED, auto-increment)
- Foreign keys: `{singular_table_name}_id` (BIGINT UNSIGNED)
- Timestamps: `created_at`, `updated_at` (TIMESTAMP)
- Soft deletes: `deleted_at` (TIMESTAMP, nullable)
- Indexes: Explicit indexes on all foreign keys and frequently queried columns

---

## Identity Tables

### users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50) NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    avatar_path VARCHAR(255) NULL,
    timezone VARCHAR(50) DEFAULT 'UTC',
    locale VARCHAR(10) DEFAULT 'en',
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255) NULL,
    two_factor_recovery_codes TEXT NULL,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_users_email (email),
    INDEX idx_users_phone (phone),
    INDEX idx_users_active (is_active),
    INDEX idx_users_deleted (deleted_at)
);
```

### organizations
```sql
CREATE TABLE organizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    type ENUM('manufacturer', 'distributor', 'reseller', 'seller', 'local_shop', 'corporate', 'individual') NOT NULL,
    registration_number VARCHAR(100) NULL,
    tax_id VARCHAR(100) NULL,
    vat_number VARCHAR(100) NULL,
    pan_number VARCHAR(100) NULL,
    gst_number VARCHAR(100) NULL,
    country_id BIGINT UNSIGNED NOT NULL,
    region_id BIGINT UNSIGNED NULL,
    city VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    logo_path VARCHAR(255) NULL,
    banner_path VARCHAR(255) NULL,
    description TEXT NULL,
    business_hours JSON NULL,
    verification_status ENUM('pending', 'verified', 'rejected', 'suspended') DEFAULT 'pending',
    verified_at TIMESTAMP NULL,
    verified_by BIGINT UNSIGNED NULL,
    rejection_reason TEXT NULL,
    suspension_reason TEXT NULL,
    suspended_at TIMESTAMP NULL,
    parent_organization_id BIGINT UNSIGNED NULL,
    settings JSON NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (region_id) REFERENCES regions(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    FOREIGN KEY (parent_organization_id) REFERENCES organizations(id),
    INDEX idx_org_type (type),
    INDEX idx_org_country (country_id),
    INDEX idx_org_verification (verification_status),
    INDEX idx_org_parent (parent_organization_id),
    INDEX idx_org_deleted (deleted_at)
);
```

### organization_members
```sql
CREATE TABLE organization_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(100) NULL,
    department VARCHAR(100) NULL,
    is_owner BOOLEAN DEFAULT FALSE,
    is_primary_contact BOOLEAN DEFAULT FALSE,
    joined_at TIMESTAMP NOT NULL,
    invited_at TIMESTAMP NULL,
    invited_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (invited_by) REFERENCES users(id),
    UNIQUE KEY unique_member (organization_id, user_id),
    INDEX idx_member_org (organization_id),
    INDEX idx_member_user (user_id)
);
```

### roles
```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    guard_name VARCHAR(50) DEFAULT 'web',
    is_system BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_roles_name (name)
);
```

### permissions
```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    group_name VARCHAR(100) NULL,
    guard_name VARCHAR(50) DEFAULT 'web',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_permissions_name (name),
    INDEX idx_permissions_group (group_name)
);
```

### role_has_permissions
```sql
CREATE TABLE role_has_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);
```

### personal_access_tokens
```sql
CREATE TABLE personal_access_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    last_accessed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (tokenable_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_tokenable (tokenable_type, tokenable_id),
    INDEX idx_token (token)
);
```

### login_sessions
```sql
CREATE TABLE login_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    device_fingerprint VARCHAR(255) NULL,
    device_name VARCHAR(100) NULL,
    device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown',
    os VARCHAR(100) NULL,
    browser VARCHAR(100) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_activity_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_user (user_id),
    INDEX idx_session_active (is_active),
    INDEX idx_session_last_activity (last_activity_at)
);
```

### impersonation_logs
```sql
CREATE TABLE impersonation_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    impersonated_user_id BIGINT UNSIGNED NOT NULL,
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    reason TEXT NULL,
    
    FOREIGN KEY (admin_user_id) REFERENCES users(id),
    FOREIGN KEY (impersonated_user_id) REFERENCES users(id),
    INDEX idx_impersonation_admin (admin_user_id),
    INDEX idx_impersonation_user (impersonated_user_id)
);
```

---

## Marketplace Tables

### countries
```sql
CREATE TABLE countries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code CHAR(2) NOT NULL UNIQUE,
    code_iso3 CHAR(3) NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    name_local VARCHAR(100) NULL,
    phone_code VARCHAR(10) NULL,
    currency_code CHAR(3) NOT NULL,
    currency_symbol VARCHAR(10) NULL,
    has_storefront BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_countries_code (code),
    INDEX idx_countries_active (is_active)
);
```

### regions
```sql
CREATE TABLE regions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NULL,
    type ENUM('state', 'province', 'territory', 'region') NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (country_id) REFERENCES countries(id),
    INDEX idx_region_country (country_id)
);
```

### country_storefronts
```sql
CREATE TABLE country_storefronts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_id BIGINT UNSIGNED NOT NULL UNIQUE,
    domain VARCHAR(255) NULL,
    subdomain VARCHAR(100) NULL,
    path_prefix VARCHAR(50) NULL,
    default_language VARCHAR(10) DEFAULT 'en',
    enabled_languages JSON NULL,
    seo_title VARCHAR(255) NULL,
    seo_description TEXT NULL,
    seo_keywords JSON NULL,
    header_script TEXT NULL,
    footer_script TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    launch_date DATE NULL,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    INDEX idx_storefront_active (is_active)
);
```

### currencies
```sql
CREATE TABLE currencies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code CHAR(3) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    decimal_places TINYINT DEFAULT 2,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_currency_code (code)
);
```

### exchange_rates
```sql
CREATE TABLE exchange_rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_currency CHAR(3) NOT NULL,
    to_currency CHAR(3) NOT NULL,
    rate DECIMAL(20,10) NOT NULL,
    source VARCHAR(100) NULL,
    effective_date DATE NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_rate (from_currency, to_currency, effective_date),
    INDEX idx_from_currency (from_currency),
    INDEX idx_to_currency (to_currency),
    INDEX idx_effective_date (effective_date)
);
```

### tax_rates
```sql
CREATE TABLE tax_rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_id BIGINT UNSIGNED NOT NULL,
    region_id BIGINT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('vat', 'gst', 'sales_tax', 'import_duty', 'excise', 'other') NOT NULL,
    rate DECIMAL(5,2) NOT NULL,
    is_compound BOOLEAN DEFAULT FALSE,
    priority INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (region_id) REFERENCES regions(id),
    INDEX idx_tax_country (country_id),
    INDEX idx_tax_type (type)
);
```

### import_duty_rules
```sql
CREATE TABLE import_duty_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_id BIGINT UNSIGNED NOT NULL,
    hs_code_pattern VARCHAR(50) NULL,
    category_id BIGINT UNSIGNED NULL,
    duty_rate DECIMAL(5,2) NOT NULL,
    calculation_base ENUM('value', 'quantity', 'weight') DEFAULT 'value',
    minimum_value DECIMAL(15,4) NULL,
    exemptions JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_duty_country (country_id),
    INDEX idx_duty_hs_code (hs_code_pattern)
);
```

---

## Catalogue Tables

### categories
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT NULL,
    image_path VARCHAR(255) NULL,
    icon_class VARCHAR(100) NULL,
    level INT DEFAULT 0,
    path VARCHAR(500) NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    attribute_template_id BIGINT UNSIGNED NULL,
    seo_title VARCHAR(255) NULL,
    seo_description TEXT NULL,
    seo_keywords JSON NULL,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (attribute_template_id) REFERENCES attribute_templates(id),
    INDEX idx_category_parent (parent_id),
    INDEX idx_category_slug (slug),
    INDEX idx_category_level (level),
    INDEX idx_category_path (path(255)),
    INDEX idx_category_active (is_active),
    INDEX idx_category_deleted (deleted_at)
);
```

### brands
```sql
CREATE TABLE brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    logo_path VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    country_of_origin BIGINT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    seo_title VARCHAR(255) NULL,
    seo_description TEXT NULL,
    seo_keywords JSON NULL,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (country_of_origin) REFERENCES countries(id),
    INDEX idx_brand_slug (slug),
    INDEX idx_brand_active (is_active),
    INDEX idx_brand_deleted (deleted_at)
);
```

### manufacturers
```sql
CREATE TABLE manufacturers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    logo_path VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    headquarters_country BIGINT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_documents JSON NULL,
    seo_title VARCHAR(255) NULL,
    seo_description TEXT NULL,
    seo_keywords JSON NULL,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (headquarters_country) REFERENCES countries(id),
    INDEX idx_manufacturer_slug (slug),
    INDEX idx_manufacturer_active (is_active),
    INDEX idx_manufacturer_deleted (deleted_at)
);
```

### attributes
```sql
CREATE TABLE attributes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('text', 'textarea', 'number', 'select', 'multiselect', 'boolean', 'date', 'url', 'file') NOT NULL,
    unit VARCHAR(50) NULL,
    description TEXT NULL,
    is_filterable BOOLEAN DEFAULT TRUE,
    is_searchable BOOLEAN DEFAULT FALSE,
    is_variant BOOLEAN DEFAULT FALSE,
    is_required BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    validation_rules JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_attribute_slug (slug),
    INDEX idx_attribute_type (type),
    INDEX idx_attribute_filterable (is_filterable)
);
```

### attribute_groups
```sql
CREATE TABLE attribute_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    category_id BIGINT UNSIGNED NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_attr_group_category (category_id)
);
```

### attribute_group_items
```sql
CREATE TABLE attribute_group_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribute_group_id BIGINT UNSIGNED NOT NULL,
    attribute_id BIGINT UNSIGNED NOT NULL,
    sort_order INT DEFAULT 0,
    is_required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    
    FOREIGN KEY (attribute_group_id) REFERENCES attribute_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_attribute (attribute_group_id, attribute_id)
);
```

### attribute_values
```sql
CREATE TABLE attribute_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribute_id BIGINT UNSIGNED NOT NULL,
    value VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    
    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE,
    INDEX idx_attr_value_attribute (attribute_id),
    INDEX idx_attr_value (value(191))
);
```

---

## Product Information Management Tables

### products
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    manufacturer_id BIGINT UNSIGNED NULL,
    brand_id BIGINT UNSIGNED NULL,
    mpn VARCHAR(255) NOT NULL,
    mpn_normalized VARCHAR(255) NULL,
    name VARCHAR(500) NOT NULL,
    slug VARCHAR(500) NOT NULL,
    short_description TEXT NULL,
    long_description LONGTEXT NULL,
    package_type VARCHAR(100) NULL,
    mounting_type VARCHAR(100) NULL,
    country_of_origin CHAR(2) NULL,
    hs_code VARCHAR(50) NULL,
    eccn VARCHAR(50) NULL,
    weight_grams DECIMAL(10,4) NULL,
    length_mm DECIMAL(10,2) NULL,
    width_mm DECIMAL(10,2) NULL,
    height_mm DECIMAL(10,2) NULL,
    min_order_quantity INT DEFAULT 1,
    standard_package_qty INT NULL,
    lead_time_days INT NULL,
    lifecycle_status ENUM('active', 'new', 'recommended', 'nrnd', 'ltb', 'obsolete', 'discontinued', 'pre_release') DEFAULT 'active',
    lifecycle_changed_at TIMESTAMP NULL,
    rohs_compliant BOOLEAN NULL,
    reach_compliant BOOLEAN NULL,
    ce_certified BOOLEAN NULL,
    fcc_certified BOOLEAN NULL,
    ul_certified BOOLEAN NULL,
    datasheet_url VARCHAR(500) NULL,
    manufacturer_url VARCHAR(500) NULL,
    is_master BOOLEAN DEFAULT TRUE,
    status ENUM('draft', 'pending_approval', 'approved', 'rejected', 'archived') DEFAULT 'draft',
    approved_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    rejection_reason TEXT NULL,
    view_count BIGINT DEFAULT 0,
    favorite_count INT DEFAULT 0,
    settings JSON NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    UNIQUE KEY unique_mpn_manufacturer (mpn, manufacturer_id),
    INDEX idx_product_mpn (mpn),
    INDEX idx_product_mpn_normalized (mpn_normalized),
    INDEX idx_product_slug (slug),
    INDEX idx_product_manufacturer (manufacturer_id),
    INDEX idx_product_brand (brand_id),
    INDEX idx_product_lifecycle (lifecycle_status),
    INDEX idx_product_status (status),
    INDEX idx_product_deleted (deleted_at),
    FULLTEXT INDEX ft_product_name_description (name, short_description)
);
```

### product_categories
```sql
CREATE TABLE product_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_category (product_id, category_id),
    INDEX idx_pc_product (product_id),
    INDEX idx_pc_category (category_id)
);
```

### product_variants
```sql
CREATE TABLE product_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    sku VARCHAR(255) NOT NULL,
    name VARCHAR(500) NULL,
    packaging_type VARCHAR(100) NULL,
    package_quantity INT NULL,
    reel_size INT NULL,
    tube_size INT NULL,
    tray_size INT NULL,
    price_usd DECIMAL(15,6) NULL,
    weight_grams DECIMAL(10,4) NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_variant_sku (sku),
    INDEX idx_variant_product (product_id),
    INDEX idx_variant_sku (sku),
    INDEX idx_variant_active (is_active),
    INDEX idx_variant_deleted (deleted_at)
);
```

### product_specifications
```sql
CREATE TABLE product_specifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    attribute_id BIGINT UNSIGNED NOT NULL,
    attribute_value_id BIGINT UNSIGNED NULL,
    value_text TEXT NULL,
    value_number DECIMAL(20,10) NULL,
    value_boolean BOOLEAN NULL,
    value_date DATE NULL,
    value_file_path VARCHAR(255) NULL,
    unit VARCHAR(50) NULL,
    source ENUM('manufacturer', 'seller', 'generated') DEFAULT 'manufacturer',
    is_visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_value_id) REFERENCES attribute_values(id) ON DELETE SET NULL,
    INDEX idx_spec_product (product_id),
    INDEX idx_spec_attribute (attribute_id),
    INDEX idx_spec_visible (is_visible)
);
```

### product_images
```sql
CREATE TABLE product_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    mime_type VARCHAR(50) NULL,
    size_bytes BIGINT NULL,
    width_px INT NULL,
    height_px INT NULL,
    sort_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    source ENUM('manufacturer', 'seller', 'generated') DEFAULT 'manufacturer',
    created_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_img_product (product_id),
    INDEX idx_img_primary (is_primary)
);
```

### product_documents
```sql
CREATE TABLE product_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    type ENUM('datasheet', 'manual', 'cad', 'design_resource', 'certificate', 'other') NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(50) NULL,
    size_bytes BIGINT NULL,
    language VARCHAR(10) DEFAULT 'en',
    version VARCHAR(50) NULL,
    download_count INT DEFAULT 0,
    is_public BOOLEAN DEFAULT TRUE,
    requires_authentication BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_doc_product (product_id),
    INDEX idx_doc_type (type),
    INDEX idx_doc_public (is_public)
);
```

### product_lifecycles
```sql
CREATE TABLE product_lifecycles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL UNIQUE,
    status ENUM('active', 'new', 'recommended', 'nrnd', 'ltb', 'obsolete', 'discontinued', 'pre_release') NOT NULL,
    status_changed_at TIMESTAMP NOT NULL,
    previous_status VARCHAR(50) NULL,
    change_reason TEXT NULL,
    ltb_notification_sent BOOLEAN DEFAULT FALSE,
    ltb_deadline DATE NULL,
    obsolescence_date DATE NULL,
    replacement_product_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (replacement_product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_lifecycle_status (status),
    INDEX idx_lifecycle_changed (status_changed_at)
);
```

### product_relations
```sql
CREATE TABLE product_relations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    related_product_id BIGINT UNSIGNED NOT NULL,
    relation_type ENUM('alternate', 'equivalent', 'complementary', 'accessory', 'upgrade', 'downgrade', 'similar') NOT NULL,
    confidence_score DECIMAL(5,4) NULL,
    match_details JSON NULL,
    is_ai_suggested BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    approved_by BIGINT UNSIGNED NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_relation_product (product_id),
    INDEX idx_relation_type (relation_type)
);
```

### product_seo
```sql
CREATE TABLE product_seo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    country_id BIGINT UNSIGNED NULL,
    language VARCHAR(10) DEFAULT 'en',
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    meta_keywords JSON NULL,
    canonical_url VARCHAR(500) NULL,
    og_title VARCHAR(255) NULL,
    og_description TEXT NULL,
    og_image_path VARCHAR(255) NULL,
    twitter_card VARCHAR(50) NULL,
    schema_markup JSON NULL,
    hreflang_tags JSON NULL,
    indexing_allowed BOOLEAN DEFAULT TRUE,
    noindex_reason VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL,
    UNIQUE KEY unique_product_country_lang (product_id, country_id, language),
    INDEX idx_seo_product (product_id),
    INDEX idx_seo_country (country_id)
);
```

---

## Seller & Marketplace Offer Tables

### sellers
```sql
CREATE TABLE sellers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL UNIQUE,
    seller_type ENUM('marketplace', 'authorized_distributor', 'reseller', 'local_shop', 'manufacturer_direct') NOT NULL,
    status ENUM('application', 'pending', 'approved', 'rejected', 'suspended', 'terminated') DEFAULT 'application',
    performance_score DECIMAL(5,2) NULL,
    total_sales BIGINT DEFAULT 0,
    total_orders BIGINT DEFAULT 0,
    average_rating DECIMAL(3,2) NULL,
    total_reviews BIGINT DEFAULT 0,
    response_time_hours DECIMAL(5,2) NULL,
    fulfillment_rate DECIMAL(5,2) NULL,
    defect_rate DECIMAL(5,2) NULL,
    cancellation_rate DECIMAL(5,2) NULL,
    approved_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    suspended_at TIMESTAMP NULL,
    suspension_reason TEXT NULL,
    commission_tier_id BIGINT UNSIGNED NULL,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (commission_tier_id) REFERENCES commission_tiers(id),
    INDEX idx_seller_status (status),
    INDEX idx_seller_type (seller_type),
    INDEX idx_seller_performance (performance_score),
    INDEX idx_seller_deleted (deleted_at)
);
```

### seller_applications
```sql
CREATE TABLE seller_applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    applicant_user_id BIGINT UNSIGNED NOT NULL,
    seller_type ENUM('marketplace', 'authorized_distributor', 'reseller', 'local_shop', 'manufacturer_direct') NOT NULL,
    business_type VARCHAR(100) NULL,
    years_in_business INT NULL,
    annual_revenue_range VARCHAR(50) NULL,
    primary_categories JSON NULL,
    warehouse_locations JSON NULL,
    shipping_countries JSON NULL,
    return_policy TEXT NULL,
    shipping_policy TEXT NULL,
    payment_terms TEXT NULL,
    documents JSON NULL,
    status ENUM('submitted', 'under_review', 'additional_info_requested', 'approved', 'rejected') DEFAULT 'submitted',
    reviewer_id BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (applicant_user_id) REFERENCES users(id),
    FOREIGN KEY (reviewer_id) REFERENCES users(id),
    INDEX idx_application_status (status),
    INDEX idx_application_org (organization_id)
);
```

### seller_offers
```sql
CREATE TABLE seller_offers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    seller_id BIGINT UNSIGNED NOT NULL,
    sku VARCHAR(255) NOT NULL,
    condition ENUM('new', 'new_factory_sealed', 'new_open_box', 'used', 'refurbished', 'recycled') DEFAULT 'new',
    authenticity_declaration ENUM('genuine', 'original', 'aftermarket', 'compatible', 'unknown') DEFAULT 'unknown',
    is_authorized_distributor BOOLEAN DEFAULT FALSE,
    price DECIMAL(15,6) NOT NULL,
    currency CHAR(3) DEFAULT 'USD',
    moq INT DEFAULT 1,
    quantity_increment INT DEFAULT 1,
    max_order_quantity INT NULL,
    available_stock INT DEFAULT 0,
    stock_accuracy ENUM('exact', 'approximate', 'on_request') DEFAULT 'exact',
    lead_time_days INT NULL,
    dispatch_location_country CHAR(2) NULL,
    dispatch_location_city VARCHAR(100) NULL,
    warranty_period_months INT NULL,
    warranty_type VARCHAR(100) NULL,
    return_policy_days INT DEFAULT 30,
    batch_number VARCHAR(100) NULL,
    date_code VARCHAR(50) NULL,
    packaging_type VARCHAR(100) NULL,
    shipping_restrictions JSON NULL,
    volume_pricing JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'out_of_stock', 'discontinued', 'pending_approval') DEFAULT 'pending_approval',
    approved_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    last_synced_at TIMESTAMP NULL,
    sync_source VARCHAR(50) NULL,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    UNIQUE KEY unique_seller_product_condition (seller_id, product_id, condition),
    INDEX idx_offer_product (product_id),
    INDEX idx_offer_seller (seller_id),
    INDEX idx_offer_sku (sku),
    INDEX idx_offer_price (price),
    INDEX idx_offer_active (is_active),
    INDEX idx_offer_status (status),
    INDEX idx_offer_deleted (deleted_at)
);
```

### country_offers
```sql
CREATE TABLE country_offers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_offer_id BIGINT UNSIGNED NOT NULL,
    country_id BIGINT UNSIGNED NOT NULL,
    price_local DECIMAL(15,6) NULL,
    currency_local CHAR(3) NULL,
    import_duty_rate DECIMAL(5,2) NULL,
    tax_rate DECIMAL(5,2) NULL,
    estimated_delivery_days INT NULL,
    shipping_cost DECIMAL(15,4) NULL,
    is_available BOOLEAN DEFAULT TRUE,
    availability_note TEXT NULL,
    restrictions JSON NULL,
    customs_hs_code VARCHAR(50) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (seller_offer_id) REFERENCES seller_offers(id) ON DELETE CASCADE,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    UNIQUE KEY unique_offer_country (seller_offer_id, country_id),
    INDEX idx_country_offer (seller_offer_id),
    INDEX idx_country_available (is_available)
);
```

### seller_staff
```sql
CREATE TABLE seller_staff (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    permissions JSON NULL,
    can_manage_products BOOLEAN DEFAULT FALSE,
    can_manage_orders BOOLEAN DEFAULT FALSE,
    can_manage_inventory BOOLEAN DEFAULT FALSE,
    can_view_financials BOOLEAN DEFAULT FALSE,
    can_manage_settlements BOOLEAN DEFAULT FALSE,
    can_manage_rfqs BOOLEAN DEFAULT FALSE,
    can_manage_support BOOLEAN DEFAULT FALSE,
    added_at TIMESTAMP NOT NULL,
    added_by BIGINT UNSIGNED NOT NULL,
    last_access_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id),
    UNIQUE KEY unique_seller_staff (seller_id, user_id),
    INDEX idx_staff_seller (seller_id),
    INDEX idx_staff_user (user_id)
);
```

---

## Inventory & Warehouse Tables

### warehouses
```sql
CREATE TABLE warehouses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('neoGiga', 'seller', 'consignment', 'third_party') NOT NULL,
    owner_id BIGINT UNSIGNED NULL,
    country_id BIGINT UNSIGNED NOT NULL,
    region_id BIGINT UNSIGNED NULL,
    city VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    manager_user_id BIGINT UNSIGNED NULL,
    capacity_cubic_meters DECIMAL(10,2) NULL,
    temperature_controlled BOOLEAN DEFAULT FALSE,
    humidity_controlled BOOLEAN DEFAULT FALSE,
    esd_protected BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (owner_id) REFERENCES organizations(id) ON DELETE SET NULL,
    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (region_id) REFERENCES regions(id),
    FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_warehouse_country (country_id),
    INDEX idx_warehouse_type (type),
    INDEX idx_warehouse_active (is_active),
    INDEX idx_warehouse_deleted (deleted_at)
);
```

### warehouse_locations
```sql
CREATE TABLE warehouse_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    parent_location_id BIGINT UNSIGNED NULL,
    zone VARCHAR(50) NULL,
    rack VARCHAR(50) NULL,
    shelf VARCHAR(50) NULL,
    bin VARCHAR(50) NULL,
    full_path VARCHAR(255) NULL,
    location_type ENUM('zone', 'rack', 'shelf', 'bin', 'bulk', 'quarantine', 'receiving', 'shipping') NOT NULL,
    capacity_units INT NULL,
    current_units INT DEFAULT 0,
    temperature_min DECIMAL(5,2) NULL,
    temperature_max DECIMAL(5,2) NULL,
    humidity_min DECIMAL(5,2) NULL,
    humidity_max DECIMAL(5,2) NULL,
    is_esd_safe BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,
    INDEX idx_location_warehouse (warehouse_id),
    INDEX idx_location_parent (parent_location_id),
    INDEX idx_location_type (location_type),
    INDEX idx_location_path (full_path)
);
```

### stock
```sql
CREATE TABLE stock (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED NULL,
    seller_offer_id BIGINT UNSIGNED NULL,
    owner_type ENUM('neoGiga', 'seller', 'consignment') NOT NULL,
    owner_id BIGINT UNSIGNED NULL,
    quantity_on_hand INT NOT NULL DEFAULT 0,
    quantity_available INT NOT NULL DEFAULT 0,
    quantity_reserved INT NOT NULL DEFAULT 0,
    quantity_allocated INT NOT NULL DEFAULT 0,
    quantity_inspection INT NOT NULL DEFAULT 0,
    quantity_damaged INT NOT NULL DEFAULT 0,
    quantity_quarantined INT NOT NULL DEFAULT 0,
    quantity_expired INT NOT NULL DEFAULT 0,
    reorder_point INT NULL,
    safety_stock INT NULL,
    cost_per_unit DECIMAL(15,6) NULL,
    currency CHAR(3) DEFAULT 'USD',
    last_counted_at TIMESTAMP NULL,
    last_counted_by BIGINT UNSIGNED NULL,
    last_received_at TIMESTAMP NULL,
    last_sold_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    FOREIGN KEY (seller_offer_id) REFERENCES seller_offers(id) ON DELETE SET NULL,
    FOREIGN KEY (owner_id) REFERENCES organizations(id) ON DELETE SET NULL,
    FOREIGN KEY (last_counted_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_stock_item (warehouse_id, product_id, variant_id, seller_offer_id, owner_type, owner_id),
    INDEX idx_stock_warehouse (warehouse_id),
    INDEX idx_stock_product (product_id),
    INDEX idx_stock_variant (variant_id),
    INDEX idx_stock_seller (seller_offer_id),
    INDEX idx_stock_available (quantity_available),
    INDEX idx_stock_owner (owner_type, owner_id)
);
```

### stock_movements
```sql
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    stock_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM('receipt', 'sale', 'return', 'adjustment', 'transfer_in', 'transfer_out', 'reservation', 'release', 'damage', 'scrap', 'inspection', 'quarantine', 'count') NOT NULL,
    quantity INT NOT NULL,
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL,
    batch_number VARCHAR(100) NULL,
    date_code VARCHAR(50) NULL,
    serial_numbers JSON NULL,
    expiry_date DATE NULL,
    cost_per_unit DECIMAL(15,6) NULL,
    total_value DECIMAL(15,2) NULL,
    reason VARCHAR(255) NULL,
    notes TEXT NULL,
    performed_by BIGINT UNSIGNED NOT NULL,
    performed_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    
    FOREIGN KEY (stock_id) REFERENCES stock(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (location_id) REFERENCES warehouse_locations(id),
    FOREIGN KEY (performed_by) REFERENCES users(id),
    INDEX idx_movement_stock (stock_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_movement_reference (reference_type, reference_id),
    INDEX idx_movement_warehouse (warehouse_id),
    INDEX idx_movement_date (performed_at),
    INDEX idx_movement_uuid (uuid)
);
```

### stock_reservations
```sql
CREATE TABLE stock_reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    order_item_id BIGINT UNSIGNED NULL,
    rfq_id BIGINT UNSIGNED NULL,
    quotation_id BIGINT UNSIGNED NULL,
    quantity INT NOT NULL,
    quantity_fulfilled INT DEFAULT 0,
    status ENUM('active', 'fulfilled', 'cancelled', 'expired') DEFAULT 'active',
    expires_at TIMESTAMP NULL,
    fulfilled_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancelled_by BIGINT UNSIGNED NULL,
    cancellation_reason VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (stock_id) REFERENCES stock(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE SET NULL,
    FOREIGN KEY (rfq_id) REFERENCES rfqs(id) ON DELETE SET NULL,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE SET NULL,
    FOREIGN KEY (cancelled_by) REFERENCES users(id),
    INDEX idx_reservation_stock (stock_id),
    INDEX idx_reservation_order (order_id),
    INDEX idx_reservation_status (status),
    INDEX idx_reservation_expires (expires_at)
);
```

### stock_transfers
```sql
CREATE TABLE stock_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_number VARCHAR(50) NOT NULL UNIQUE,
    from_warehouse_id BIGINT UNSIGNED NOT NULL,
    to_warehouse_id BIGINT UNSIGNED NOT NULL,
    status ENUM('draft', 'in_transit', 'received', 'cancelled') DEFAULT 'draft',
    shipped_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_transfer_from (from_warehouse_id),
    INDEX idx_transfer_to (to_warehouse_id),
    INDEX idx_transfer_status (status)
);
```

### stock_transfer_items
```sql
CREATE TABLE stock_transfer_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_id BIGINT UNSIGNED NOT NULL,
    stock_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    quantity_shipped INT DEFAULT 0,
    quantity_received INT DEFAULT 0,
    quantity_lost INT DEFAULT 0,
    batch_number VARCHAR(100) NULL,
    date_code VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (transfer_id) REFERENCES stock_transfers(id) ON DELETE CASCADE,
    FOREIGN KEY (stock_id) REFERENCES stock(id),
    INDEX idx_transfer_item_transfer (transfer_id),
    INDEX idx_transfer_item_stock (stock_id)
);
```

### batches
```sql
CREATE TABLE batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_id BIGINT UNSIGNED NOT NULL,
    batch_number VARCHAR(100) NOT NULL,
    date_code VARCHAR(50) NULL,
    manufacturing_date DATE NULL,
    expiry_date DATE NULL,
    received_date DATE NOT NULL,
    supplier_id BIGINT UNSIGNED NULL,
    purchase_order_id BIGINT UNSIGNED NULL,
    quantity_received INT NOT NULL,
    quantity_remaining INT NOT NULL,
    cost_per_unit DECIMAL(15,6) NOT NULL,
    certificate_path VARCHAR(255) NULL,
    quality_status ENUM('pending', 'passed', 'failed', 'quarantined') DEFAULT 'pending',
    inspected_by BIGINT UNSIGNED NULL,
    inspected_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (stock_id) REFERENCES stock(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (inspected_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_batch_stock (stock_id),
    INDEX idx_batch_number (batch_number),
    INDEX idx_batch_date_code (date_code),
    INDEX idx_batch_expiry (expiry_date)
);
```

---

[Note: Due to length constraints, this schema continues with additional tables for Orders, Procurement, RFQ, Accounting, Settlement, Support, Workflow, Notifications, SEO, Analytics, Risk, BOM, Audit Logs, etc. The complete schema would be approximately 2000+ lines.]

---

## Index Strategy

### Critical Indexes for Performance

1. **Product Search:**
   - `products(mpn_normalized)`
   - `products(slug)`
   - Full-text index on `(name, short_description, long_description)`

2. **Seller Offers:**
   - `seller_offers(product_id, is_active, status)`
   - `seller_offers(seller_id, is_active)`
   - `seller_offers(price)`

3. **Stock Queries:**
   - `stock(warehouse_id, product_id, variant_id)`
   - `stock(quantity_available)`
   - `stock_movements(performed_at)`

4. **Order Processing:**
   - `orders(customer_id, status)`
   - `orders(created_at)`
   - `order_items(order_id)`

5. **Settlement:**
   - `settlements(seller_id, status)`
   - `settlement_items(settlement_id)`

---

## Version History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-01-XX | NeoGiga Team | Initial schema design |
