# Attribute Schema Gap Report

**Project:** NeoGiga Enterprise Catalog Import Center  
**Date:** 2026-07-10  
**Phase:** 1 — Attribute Schema Audit  

---

## Executive Summary

This report analyzes the current attribute/specification system and identifies gaps compared to enterprise-grade parametric search systems like DigiKey and Mouser. The current implementation provides basic templated specifications but lacks the normalization, unit conversion, and controlled vocabulary features required for professional electronics component catalog management.

---

## 1. Current Attribute System Analysis

### 1.1 Existing Tables

#### `category_spec_templates`
```sql
CREATE TABLE category_spec_templates (
    id BIGINT PRIMARY KEY,
    category_id BIGINT NOT NULL,      -- FK to product_categories
    name VARCHAR(255),                -- e.g., "Battery Specifications"
    description TEXT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    metadata JSON NULL,
    timestamps
);
```

#### `spec_template_fields`
```sql
CREATE TABLE spec_template_fields (
    id BIGINT PRIMARY KEY,
    template_id BIGINT NOT NULL,      -- FK to category_spec_templates
    field_name VARCHAR(255),          -- e.g., "voltage", "capacity"
    field_label VARCHAR(255),         -- e.g., "Voltage", "Battery Capacity"
    field_type ENUM('text','number','select','boolean','range'),
    unit VARCHAR(50) NULL,            -- e.g., "V", "mAh", "W"
    options JSON NULL,                -- For select type
    validation_rules VARCHAR(255) NULL,
    help_text TEXT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    timestamps
);
```

#### `product_specifications`
```sql
CREATE TABLE product_specifications (
    id BIGINT PRIMARY KEY,
    product_id BIGINT NOT NULL,       -- FK to products
    template_field_id BIGINT NOT NULL,-- FK to spec_template_fields
    value TEXT,                       -- The actual value
    unit_override VARCHAR(50) NULL,   -- Override template unit
    is_visible BOOLEAN DEFAULT TRUE,
    timestamps,
    UNIQUE(product_id, template_field_id)
);
```

#### `specification_groups`
```sql
CREATE TABLE specification_groups (
    id BIGINT PRIMARY KEY,
    category_id BIGINT NOT NULL,
    name VARCHAR(255),                -- e.g., "General", "Technical"
    description TEXT NULL,
    sort_order INT DEFAULT 0,
    is_expanded BOOLEAN DEFAULT TRUE,
    timestamps
);
```

#### `specification_group_fields`
```sql
CREATE TABLE specification_group_fields (
    id BIGINT PRIMARY KEY,
    group_id BIGINT NOT NULL,
    template_field_id BIGINT NOT NULL,
    sort_order INT DEFAULT 0,
    timestamps,
    UNIQUE(group_id, template_field_id)
);
```

### 1.2 Current Capabilities

**Strengths:**
- ✅ Category-specific templates
- ✅ Field grouping for display organization
- ✅ Basic data types (text, number, select, boolean, range)
- ✅ Unit support per field
- ✅ Required field enforcement
- ✅ Custom validation rules
- ✅ Help text for guidance

**Weaknesses:**
- ❌ No master attribute table (fields are tied to templates)
- ❌ No attribute groups independent of categories
- ❌ No unit families or conversions
- ❌ No controlled vocabulary for attribute names
- ❌ No attribute aliasing/normalization
- ❌ Original value not preserved when converted
- ❌ No filterable/searchable/comparable flags
- ❌ No external attribute mappings
- ❌ Limited data types (missing option, multi_option, date)
- ❌ No validation rule structure (just string)

---

## 2. Enterprise Requirements Analysis

### 2.1 Electronics Component Attributes

Typical attributes needed for electronics components:

#### Electrical Characteristics
| Attribute | Data Type | Unit Family | Typical Units |
|-----------|-----------|-------------|---------------|
| Supply Voltage Min | decimal | voltage | V, mV, kV |
| Supply Voltage Max | decimal | voltage | V, mV, kV |
| Operating Current | decimal | current | A, mA, µA |
| Output Current | decimal | current | A, mA, µA |
| Power Rating | decimal | power | W, mW, kW |
| Resistance | decimal | resistance | Ω, kΩ, MΩ |
| Capacitance | decimal | capacitance | F, µF, nF, pF |
| Inductance | decimal | inductance | H, mH, µH |
| Frequency | decimal | frequency | Hz, kHz, MHz, GHz |

#### Mechanical Characteristics
| Attribute | Data Type | Unit Family | Typical Units |
|-----------|-----------|-------------|---------------|
| Length | decimal | length | mm, cm, in |
| Width | decimal | length | mm, cm, in |
| Height | decimal | length | mm, cm, in |
| Weight | decimal | mass | g, kg, oz |
| Pin Count | integer | count | (unitless) |
| Package/Case | option | - | SOIC, DIP, QFP, BGA |
| Mounting Type | option | - | SMD, Through Hole |

#### Environmental Characteristics
| Attribute | Data Type | Unit Family | Typical Units |
|-----------|-----------|-------------|---------------|
| Operating Temp Min | decimal | temperature | °C, °F, K |
| Operating Temp Max | decimal | temperature | °C, °F, K |
| Storage Temperature | decimal | temperature | °C, °F, K |
| Ingress Protection | option | - | IP65, IP67, IP68 |

#### Communication Characteristics
| Attribute | Data Type | Unit Family |
|-----------|-----------|-------------|
| Interface | multi_option | - |
| Protocol | multi_option | - |
| Data Rate | decimal | data_rate |
| Wireless Standard | multi_option | - |

#### Battery Characteristics
| Attribute | Data Type | Unit Family |
|-----------|-----------|-------------|
| Chemistry | option | - |
| Nominal Voltage | decimal | voltage |
| Capacity | decimal | charge |
| Cycle Life | integer | count |

### 2.2 Key Requirements

1. **Unit Conversion**: Store values in canonical units while preserving original
2. **Attribute Normalization**: Map "Vcc", "Supply Voltage", "V+" to same attribute
3. **Controlled Vocabularies**: Standardized options for package types, interfaces
4. **Range Support**: Store min/max as structured ranges
5. **Faceted Search**: Enable filtering by attribute values
6. **Comparison**: Allow side-by-side product comparison on attributes
7. **External Mapping**: Map supplier attribute names to canonical names

---

## 3. Recommended Schema

### 3.1 Master Attribute Tables

#### `attribute_groups`
```sql
CREATE TABLE attribute_groups (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(100) UNIQUE NOT NULL,     -- e.g., 'electrical', 'mechanical'
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    icon_path VARCHAR(500) NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_sort (sort_order)
);
```

#### `attributes`
```sql
CREATE TABLE attributes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    attribute_group_id BIGINT NOT NULL,
    code VARCHAR(100) UNIQUE NOT NULL,     -- e.g., 'supply_voltage_min'
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    data_type ENUM('string','integer','decimal','boolean','option','multi_option','range','date') NOT NULL,
    unit_family VARCHAR(100) NULL,         -- e.g., 'voltage', 'current', 'temperature'
    default_unit VARCHAR(50) NULL,         -- e.g., 'V', 'A', '°C'
    filterable BOOLEAN DEFAULT FALSE,
    comparable BOOLEAN DEFAULT FALSE,
    searchable BOOLEAN DEFAULT FALSE,
    required_for_category BOOLEAN DEFAULT FALSE,
    visible_on_product_page BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    validation_rules JSON NULL,            -- Structured validation config
    help_text TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (attribute_group_id) REFERENCES attribute_groups(id),
    INDEX idx_group (attribute_group_id),
    INDEX idx_code (code),
    INDEX idx_filterable (filterable),
    INDEX idx_data_type (data_type)
);
```

#### `attribute_units`
```sql
CREATE TABLE attribute_units (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    unit_family VARCHAR(100) NOT NULL,     -- e.g., 'voltage'
    code VARCHAR(50) NOT NULL,             -- e.g., 'V', 'mV', 'kV'
    name VARCHAR(100) NOT NULL,            -- e.g., 'Volts', 'Millivolts'
    symbol VARCHAR(20) NOT NULL,           -- e.g., 'V', 'mV'
    conversion_factor DECIMAL(20,10) NOT NULL, -- Factor to convert to base unit
    conversion_offset DECIMAL(20,10) DEFAULT 0, -- For temperature (offset from base)
    is_base_unit BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    
    UNIQUE KEY unique_family_code (unit_family, code),
    INDEX idx_family (unit_family)
);
```

#### `attribute_unit_conversions`
```sql
CREATE TABLE attribute_unit_conversions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    from_unit_id BIGINT NOT NULL,
    to_unit_id BIGINT NOT NULL,
    formula VARCHAR(255) NULL,             -- Custom formula if not simple multiply
    is_bidirectional BOOLEAN DEFAULT TRUE,
    notes TEXT NULL,
    
    FOREIGN KEY (from_unit_id) REFERENCES attribute_units(id),
    FOREIGN KEY (to_unit_id) REFERENCES attribute_units(id),
    UNIQUE KEY unique_conversion (from_unit_id, to_unit_id)
);
```

#### `attribute_options`
```sql
CREATE TABLE attribute_options (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    attribute_id BIGINT NOT NULL,
    code VARCHAR(100) NOT NULL,            -- e.g., 'soic_8', 'through_hole'
    value VARCHAR(255) NOT NULL,           -- Display value
    synonyms JSON NULL,                    -- Alternative names
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attr_code (attribute_id, code),
    INDEX idx_attribute (attribute_id)
);
```

### 3.2 Category-Attribute Relationships

#### `category_attributes`
```sql
CREATE TABLE category_attributes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    category_id BIGINT NOT NULL,
    attribute_id BIGINT NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    is_filterable BOOLEAN DEFAULT TRUE,
    is_comparable BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    group_id BIGINT NULL,                  -- Optional override group
    
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES attributes(id),
    UNIQUE KEY unique_category_attribute (category_id, attribute_id),
    INDEX idx_category (category_id),
    INDEX idx_display (category_id, display_order)
);
```

### 3.3 Product Attribute Values

#### `product_attribute_values`
```sql
CREATE TABLE product_attribute_values (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    attribute_id BIGINT NOT NULL,
    value_string VARCHAR(255) NULL,
    value_integer INT NULL,
    value_decimal DECIMAL(20,10) NULL,
    value_boolean BOOLEAN NULL,
    value_option_id BIGINT NULL,
    value_min DECIMAL(20,10) NULL,         -- For range type
    value_max DECIMAL(20,10) NULL,         -- For range type
    value_date DATE NULL,
    original_value VARCHAR(255) NULL,      -- Original imported value
    original_unit VARCHAR(50) NULL,        -- Original imported unit
    canonical_value DECIMAL(20,10) NULL,   -- Converted to canonical unit
    canonical_unit VARCHAR(50) NULL,       -- Canonical unit code
    source_id BIGINT NULL,                 -- Which import source
    confidence_score DECIMAL(3,2) DEFAULT 1.00,
    is_validated BOOLEAN DEFAULT FALSE,
    validation_errors JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES attributes(id),
    FOREIGN KEY (value_option_id) REFERENCES attribute_options(id),
    FOREIGN KEY (source_id) REFERENCES catalog_sources(id),
    UNIQUE KEY unique_product_attribute (product_id, attribute_id),
    INDEX idx_product (product_id),
    INDEX idx_attribute (attribute_id),
    INDEX idx_canonical (attribute_id, canonical_value),
    INDEX idx_validated (is_validated)
);
```

### 3.4 External Attribute Mappings

#### `external_attribute_mappings`
```sql
CREATE TABLE external_attribute_mappings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    source_id BIGINT NOT NULL,
    external_attribute_name VARCHAR(255) NOT NULL,
    neo_attribute_id BIGINT NOT NULL,
    unit_mapping_json JSON NULL,           -- Map external units to internal
    confidence_score DECIMAL(3,2) DEFAULT 1.00,
    is_approved BOOLEAN DEFAULT FALSE,
    approved_by BIGINT NULL,
    approved_at TIMESTAMP NULL,
    usage_count INT DEFAULT 0,
    last_used_at TIMESTAMP NULL,
    
    FOREIGN KEY (source_id) REFERENCES catalog_sources(id),
    FOREIGN KEY (neo_attribute_id) REFERENCES attributes(id),
    UNIQUE KEY unique_source_external (source_id, external_attribute_name),
    INDEX idx_neo_attribute (neo_attribute_id),
    INDEX idx_approved (is_approved)
);
```

#### `attribute_mapping_candidates`
```sql
CREATE TABLE attribute_mapping_candidates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    source_id BIGINT NOT NULL,
    external_attribute_name VARCHAR(255) NOT NULL,
    suggested_attribute_id BIGINT NULL,
    similarity_score DECIMAL(5,4) NOT NULL,
    mapping_type ENUM('auto_suggested','manual_review','rejected') DEFAULT 'manual_review',
    review_notes TEXT NULL,
    reviewed_by BIGINT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (source_id) REFERENCES catalog_sources(id),
    FOREIGN KEY (suggested_attribute_id) REFERENCES attributes(id),
    INDEX idx_source (source_id),
    INDEX idx_similarity (similarity_score DESC),
    INDEX idx_type (mapping_type)
);
```

---

## 4. Unit Conversion Implementation

### 4.1 Sample Data: Voltage Units

```sql
INSERT INTO attribute_units (unit_family, code, name, symbol, conversion_factor, is_base_unit) VALUES
('voltage', 'V', 'Volts', 'V', 1.0, TRUE),
('voltage', 'mV', 'Millivolts', 'mV', 0.001, FALSE),
('voltage', 'kV', 'Kilovolts', 'kV', 1000.0, FALSE),
('voltage', 'µV', 'Microvolts', 'µV', 0.000001, FALSE);
```

### 4.2 Sample Data: Temperature Units

```sql
INSERT INTO attribute_units (unit_family, code, name, symbol, conversion_factor, conversion_offset, is_base_unit) VALUES
('temperature', '°C', 'Degrees Celsius', '°C', 1.0, 0, TRUE),
('temperature', '°F', 'Degrees Fahrenheit', '°F', 0.5556, -32 * 0.5556, FALSE),
('temperature', 'K', 'Kelvin', 'K', 1.0, -273.15, FALSE);
```

### 4.3 Conversion Logic

```php
class UnitConverter
{
    public function convertToCanonical(
        float $value,
        string $fromUnitCode,
        string $unitFamily
    ): array {
        // Fetch unit definition
        $unit = DB::table('attribute_units')
            ->where('unit_family', $unitFamily)
            ->where('code', $fromUnitCode)
            ->first();
        
        if (!$unit) {
            throw new UnitNotFoundException($fromUnitCode);
        }
        
        // Apply conversion: (value * factor) + offset
        $canonicalValue = ($value * $unit->conversion_factor) + $unit->conversion_offset;
        
        // Get base unit for this family
        $baseUnit = DB::table('attribute_units')
            ->where('unit_family', $unitFamily)
            ->where('is_base_unit', true)
            ->first();
        
        return [
            'value' => $canonicalValue,
            'unit' => $baseUnit->code,
            'original_value' => $value,
            'original_unit' => $fromUnitCode,
        ];
    }
}
```

---

## 5. Attribute Normalization

### 5.1 Name Matching Strategy

```php
class AttributeNormalizer
{
    // Common aliases for supply voltage
    const VOLTAGE_ALIASES = [
        'supply voltage',
        'supply_voltage',
        'vcc',
        'v+',
        'vdd',
        'operating voltage',
        'input voltage',
        'voltage supply',
    ];
    
    public function findMatchingAttribute(string $externalName, int $sourceId): ?Attribute
    {
        // 1. Check existing mappings
        $mapping = ExternalAttributeMapping::where('source_id', $sourceId)
            ->where('external_attribute_name', $externalName)
            ->first();
        
        if ($mapping && $mapping->is_approved) {
            return $mapping->attribute;
        }
        
        // 2. Normalize name for matching
        $normalized = $this->normalizeAttributeName($externalName);
        
        // 3. Find by exact code match
        $attribute = Attribute::where('code', $normalized)->first();
        if ($attribute) {
            return $attribute;
        }
        
        // 4. Find by name similarity
        $attribute = Attribute::whereRaw('LEVENSHTEIN(name, ?) < 3', [$externalName])
            ->orWhereJsonContains('aliases', strtolower($externalName))
            ->first();
        
        return $attribute;
    }
    
    private function normalizeAttributeName(string $name): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9]/i', '_', $name)));
    }
}
```

### 5.2 Controlled Vocabulary Examples

```sql
-- Package/Case attribute
INSERT INTO attributes (attribute_group_id, code, name, data_type, filterable, comparable) 
VALUES (2, 'package_case', 'Package/Case', 'option', TRUE, TRUE);

SET @pkg_attr = LAST_INSERT_ID();

-- Package options
INSERT INTO attribute_options (attribute_id, code, value, synonyms) VALUES
(@pkg_attr, 'soic_8', 'SOIC-8', '["SOIC8", "SOIC 8", "Small Outline IC 8"]'),
(@pkg_attr, 'dip_8', 'DIP-8', '["DIP8", "DIP 8", "Dual Inline Package 8"]'),
(@pkg_attr, 'qfp_48', 'QFP-48', '["QFP48", "QFP 48", "Quad Flat Package 48"]'),
(@pkg_attr, 'bga_144', 'BGA-144', '["BGA144", "BGA 144", "Ball Grid Array 144"]');

-- Mounting Type attribute
INSERT INTO attributes (attribute_group_id, code, name, data_type, filterable, comparable) 
VALUES (2, 'mounting_type', 'Mounting Type', 'option', TRUE, TRUE);

SET @mnt_attr = LAST_INSERT_ID();

INSERT INTO attribute_options (attribute_id, code, value, synonyms) VALUES
(@mnt_attr, 'smd', 'Surface Mount (SMD)', '["SMD", "SMT", "Surface Mount"]'),
(@mnt_attr, 'through_hole', 'Through Hole', '["Through-Hole", "THT", "Plated Through Hole"]'),
(@mnt_attr, 'smt', 'Surface Mount Technology', '["SMT"]');
```

---

## 6. Faceted Search Integration

### 6.1 Index Structure (Meilisearch/OpenSearch)

```json
{
  "product_id": 12345,
  "mpn": "LM317T/NOPB",
  "manufacturer": "Texas Instruments",
  "category_path": ["Semiconductors", "Power Management", "Voltage Regulators"],
  "attributes": {
    "supply_voltage_max": {"value": 40, "unit": "V"},
    "output_current": {"value": 1.5, "unit": "A"},
    "package_case": "TO-220",
    "mounting_type": "Through Hole",
    "operating_temp_min": {"value": 0, "unit": "°C"},
    "operating_temp_max": {"value": 125, "unit": "°C"}
  },
  "facets": {
    "manufacturer": ["Texas Instruments"],
    "package_case": ["TO-220"],
    "mounting_type": ["Through Hole"],
    "supply_voltage_max_range": ["30-50V"],
    "output_current_range": ["1-2A"]
  }
}
```

### 6.2 Range Bucket Generation

```php
class FacetBucketGenerator
{
    public function generateVoltageBuckets(array $products): array
    {
        $buckets = [
            ['label' => '< 5V', 'min' => 0, 'max' => 5],
            ['label' => '5V - 12V', 'min' => 5, 'max' => 12],
            ['label' => '12V - 24V', 'min' => 12, 'max' => 24],
            ['label' => '24V - 48V', 'min' => 24, 'max' => 48],
            ['label' => '> 48V', 'min' => 48, 'max' => null],
        ];
        
        foreach ($buckets as &$bucket) {
            $bucket['count'] = collect($products)->filter(function ($p) use ($bucket) {
                $v = $p['attributes']['supply_voltage_max']['value'] ?? null;
                if ($v === null) return false;
                if ($bucket['max'] === null) return $v >= $bucket['min'];
                return $v >= $bucket['min'] && $v < $bucket['max'];
            })->count();
        }
        
        return $buckets;
    }
}
```

---

## 7. Migration Strategy

### 7.1 Phase 1: Create New Tables

1. Create `attribute_groups`, `attributes`, `attribute_units`
2. Create `attribute_options`, `category_attributes`
3. Create `product_attribute_values`
4. Create `external_attribute_mappings`

### 7.2 Phase 2: Migrate Existing Data

1. Extract unique field definitions from `spec_template_fields`
2. Create corresponding `attributes` records
3. Map existing `product_specifications` to `product_attribute_values`
4. Preserve original values and units

### 7.3 Phase 3: Deprecate Old Tables

1. Keep `category_spec_templates` for backward compatibility
2. Update application code to use new attribute system
3. Eventually archive old tables after full migration

---

## 8. Validation Rules

### 8.1 Structured Validation Configuration

```json
{
  "supply_voltage_min": {
    "type": "decimal",
    "min": 0,
    "max": 1000,
    "unit_family": "voltage",
    "required_if": {
      "category": ["Voltage Regulators", "Power Supplies"]
    }
  },
  "package_case": {
    "type": "option",
    "options_source": "attribute_options",
    "allow_custom": false
  },
  "operating_temperature_range": {
    "type": "range",
    "min_field": "operating_temp_min",
    "max_field": "operating_temp_max",
    "rule": "min < max"
  }
}
```

### 8.2 Range Validation

```php
public function validateRange(Attribute $attribute, $min, $max): bool
{
    if ($attribute->data_type !== 'range') {
        return true;
    }
    
    if ($min === null || $max === null) {
        return false;
    }
    
    if ($min > $max) {
        $this->addError("Minimum value cannot exceed maximum value");
        return false;
    }
    
    return true;
}
```

---

## 9. Conclusion

The current attribute system requires significant enhancement to support enterprise catalog imports with proper normalization, unit conversion, and faceted search capabilities.

**Priority Actions:**
1. Create master `attributes` table independent of templates
2. Implement `attribute_units` with conversion factors
3. Build `attribute_options` for controlled vocabularies
4. Add `product_attribute_values` with canonical value storage
5. Create `external_attribute_mappings` for source normalization
6. Implement attribute matching algorithm for imports

**Estimated Effort:** 4-5 sprints for full implementation including testing and data migration.

---

**Document Version:** 1.0  
**Author:** Principal Product Data Architect  
**Review Status:** Pending technical review
