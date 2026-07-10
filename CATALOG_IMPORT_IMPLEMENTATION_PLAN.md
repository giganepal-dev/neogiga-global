# Catalog Import Implementation Plan

**Project:** NeoGiga Enterprise Catalog Import Center  
**Date:** 2026-07-10  
**Phase:** 1 — Implementation Planning  
**Status:** Ready for stakeholder review  

---

## Executive Summary

This document outlines the phased implementation plan for the NeoGiga Catalog Import Center based on comprehensive audits of the current catalog infrastructure. The implementation will enable enterprise-grade ingestion from Mouser API, manufacturer feeds, distributor feeds, and admin-uploaded files while maintaining legal compliance and data quality.

---

## Phase Overview

| Phase | Name | Duration | Dependencies |
|-------|------|----------|--------------|
| 1 | Audit & Planning | Complete | None |
| 2 | Source Management Foundation | 2 sprints | Phase 1 |
| 3 | Manufacturer & Category Master | 2 sprints | Phase 2 |
| 4 | Attribute Engine | 3 sprints | Phase 2 |
| 5 | Product Master Extension | 2 sprints | Phase 3, 4 |
| 6 | Staging & ETL Pipeline | 3 sprints | Phase 4, 5 |
| 7 | CSV Import | 2 sprints | Phase 6 |
| 8 | XML Import | 2 sprints | Phase 6 |
| 9 | Mouser API Connector | 3 sprints | Phase 2, 6 |
| 10 | Admin Import Center UI | 3 sprints | Phase 7, 8, 9 |
| 11 | Review & Conflict Resolution | 2 sprints | Phase 10 |
| 12 | Regional Pricing & Inventory | 2 sprints | Phase 5 |
| 13 | Search Indexing | 2 sprints | Phase 4, 5 |
| 14 | Data Quality Scoring | 1 sprint | Phase 6 |
| 15 | Security Hardening | 1 sprint | Phase 9 |
| 16 | Performance Optimization | 2 sprints | Phase 6 |
| 17 | Documentation & Training | 1 sprint | All phases |
| 18 | Validation & Testing | 2 sprints | All phases |

**Total Estimated Duration:** 32 sprints (~8 months with parallel tracks)

---

## Phase 2: Source Management Foundation

### Objectives
Create the foundational tables and models for managing import sources, credentials, licenses, and field mappings.

### Deliverables

#### 2.1 Database Migrations

**File:** `database/migrations/catalog_import/2026_07_xx_create_catalog_source_tables.php`

```php
Schema::create('catalog_sources', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->enum('source_type', ['api', 'csv', 'xml', 'json', 'sftp', 'manual']);
    $table->string('provider_name')->nullable();
    $table->string('base_url')->nullable();
    $table->string('documentation_url')->nullable();
    $table->string('authentication_type')->nullable(); // api_key, oauth, basic, none
    $table->char('country_code', 2)->nullable();
    $table->char('default_currency', 3)->default('USD');
    $table->boolean('active')->default(true);
    $table->integer('priority')->default(0);
    $table->integer('rate_limit_per_minute')->default(60);
    $table->json('allowed_data_types')->nullable(); // [products, prices, inventory]
    $table->text('license_notes')->nullable();
    $table->boolean('attribution_required')->default(false);
    $table->string('attribution_text')->nullable();
    $table->timestamp('last_success_at')->nullable();
    $table->timestamp('last_failure_at')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->timestamps();
});

Schema::create('catalog_source_credentials', function (Blueprint $table) {
    $table->id();
    $table->foreignId('source_id')->constrained('catalog_sources')->cascadeOnDelete();
    $table->string('credential_type'); // api_key, username, token, etc.
    $table->text('encrypted_value');
    $table->string('key_version')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamp('last_rotated_at')->nullable();
    $table->timestamps();
});

Schema::create('catalog_source_licenses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('source_id')->constrained('catalog_sources')->cascadeOnDelete();
    $table->string('license_type');
    $table->text('license_text')->nullable();
    $table->date('effective_date');
    $table->date('expiration_date')->nullable();
    $table->boolean('auto_renew')->default(false);
    $table->boolean('attribution_required')->default(true);
    $table->string('attribution_text')->nullable();
    $table->boolean('redistribution_allowed')->default(false);
    $table->boolean('commercial_use_allowed')->default(true);
    $table->json('geographic_restrictions')->nullable();
    $table->string('contact_name')->nullable();
    $table->string('contact_email')->nullable();
    $table->string('signed_document_path')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('catalog_source_field_maps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('source_id')->constrained('catalog_sources')->cascadeOnDelete();
    $table->string('external_field_name');
    $table->string('neo_field_name');
    $table->string('data_type')->nullable();
    $table->json('transformation_rules')->nullable();
    $table->boolean('is_required')->default(false);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    
    $table->unique(['source_id', 'external_field_name']);
});

Schema::create('catalog_source_runs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('source_id')->constrained('catalog_sources');
    $table->string('run_type'); // full, incremental, manual
    $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled']);
    $table->integer('total_records')->default(0);
    $table->integer('processed_records')->default(0);
    $table->integer('success_records')->default(0);
    $table->integer('error_records')->default(0);
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->text('error_message')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
});

Schema::create('catalog_source_run_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('run_id')->constrained('catalog_source_runs')->cascadeOnDelete();
    $table->string('level'); // info, warning, error
    $table->text('message');
    $table->json('context')->nullable();
    $table->timestamps();
});

Schema::create('catalog_source_rate_limits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('source_id')->constrained('catalog_sources')->cascadeOnDelete();
    $table->date('date');
    $table->integer('minute_bucket'); // 0-59
    $table->integer('request_count')->default(0);
    $table->timestamps();
    
    $table->unique(['source_id', 'date', 'minute_bucket']);
});
```

#### 2.2 Models

**Files to Create:**
- `app/Models/CatalogSource.php`
- `app/Models/CatalogSourceCredential.php`
- `app/Models/CatalogSourceLicense.php`
- `app/Models/CatalogSourceFieldMap.php`
- `app/Models/CatalogSourceRun.php`
- `app/Models/CatalogSourceRunLog.php`
- `app/Models/CatalogSourceRateLimit.php`

#### 2.3 Services

**Files to Create:**
- `app/Services/Catalog/CredentialEncryptionService.php`
- `app/Services/Catalog/RateLimitService.php`
- `app/Services/Catalog/SourceValidationService.php`

### Acceptance Criteria
- [ ] All migrations run successfully
- [ ] Credentials are encrypted on save
- [ ] Rate limiting prevents exceeding configured limits
- [ ] Source runs are tracked with status
- [ ] Field maps can be created and retrieved

---

## Phase 3: Manufacturer & Category Master

### Objectives
Build manufacturer master with alias resolution and category taxonomy with external mappings.

### Deliverables

#### 3.1 Database Migrations

**Manufacturers:**
```php
Schema::create('manufacturers', function (Blueprint $table) {
    $table->id();
    $table->string('legal_name');
    $table->string('display_name');
    $table->string('slug')->unique();
    $table->json('aliases')->nullable();
    $table->string('official_website')->nullable();
    $table->string('logo_path')->nullable();
    $table->char('country_code', 2)->nullable();
    $table->enum('status', ['active', 'inactive', 'acquired', 'merged'])->default('active');
    $table->foreignId('successor_manufacturer_id')->nullable()->constrained('manufacturers');
    $table->enum('authorization_status', ['authorized', 'unauthorized', 'unknown'])->default('unknown');
    $table->integer('data_quality_score')->default(0);
    $table->string('external_source_id')->nullable();
    $table->string('source_url')->nullable();
    $table->timestamp('reviewed_at')->nullable();
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});

Schema::create('manufacturer_aliases', function (Blueprint $table) {
    $table->id();
    $table->foreignId('manufacturer_id')->constrained()->cascadeOnDelete();
    $table->string('alias_name');
    $table->enum('alias_type', ['abbreviation', 'former_name', 'dba', 'misspelling'])->nullable();
    $table->decimal('confidence_score', 3, 2)->default(1.00);
    $table->timestamps();
    
    $table->unique('alias_name');
});

Schema::create('manufacturer_external_ids', function (Blueprint $table) {
    $table->id();
    $table->foreignId('manufacturer_id')->constrained()->cascadeOnDelete();
    $table->foreignId('source_id')->constrained('catalog_sources');
    $table->string('external_id');
    $table->string('source_url')->nullable();
    $table->timestamp('last_verified_at')->nullable();
    $table->timestamps();
    
    $table->unique(['source_id', 'external_id']);
});

Schema::create('manufacturer_merge_candidates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('manufacturer_id')->constrained();
    $table->foreignId('candidate_id')->constrained('manufacturers');
    $table->decimal('similarity_score', 5, 4);
    $table->string('match_reason');
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->foreignId('reviewed_by')->nullable()->constrained('users');
    $table->timestamp('reviewed_at')->nullable();
    $table->timestamps();
});
```

**Category Extensions:**
```php
Schema::create('category_aliases', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();
    $table->string('alias_name');
    $table->string('source')->nullable();
    $table->timestamps();
});

Schema::create('category_external_mappings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();
    $table->foreignId('source_id')->constrained('catalog_sources');
    $table->string('external_category_name');
    $table->string('external_category_path')->nullable();
    $table->string('external_category_id')->nullable();
    $table->decimal('confidence_score', 3, 2)->default(1.00);
    $table->timestamps();
    
    $table->unique(['source_id', 'external_category_name']);
});

Schema::alter('product_categories', function (Blueprint $table) {
    $table->string('category_path')->nullable()->after('slug');
    $table->string('seo_title')->nullable()->after('seo_meta');
    $table->string('seo_description')->nullable()->after('seo_title');
    $table->foreignId('lms_topic_id')->nullable()->after('seo_description');
});
```

### Acceptance Criteria
- [ ] Manufacturers can be created with aliases
- [ ] Duplicate detection suggests merges
- [ ] Categories support external mappings
- [ ] Category path is auto-generated

---

## Phase 4: Attribute Engine

### Objectives
Implement world-class parametric attribute system with unit conversion and normalization.

### Key Deliverables
- `attribute_groups` table
- `attributes` master table
- `attribute_units` with conversion factors
- `attribute_options` for controlled vocabularies
- `product_attribute_values` with canonical storage
- `external_attribute_mappings`

See ATTRIBUTE_SCHEMA_GAP_REPORT.md for detailed schema.

### Acceptance Criteria
- [ ] Unit conversion works correctly
- [ ] Attributes can be normalized from external names
- [ ] Controlled vocabularies enforced
- [ ] Faceted search ready

---

## Phase 5: Product Master Extension

### Objectives
Extend product schema with manufacturer linkage, compliance fields, and source tracking.

### Key Changes
- Add `manufacturer_id` to products
- Add `normalized_mpn` for deduplication
- Add lifecycle, compliance, and package fields
- Add source tracking columns
- Create regional SKU separation tables

See PRODUCT_SCHEMA_GAP_REPORT.md for detailed schema.

### Acceptance Criteria
- [ ] Products linked to manufacturers
- [ ] MPN deduplication works
- [ ] Compliance fields populated
- [ ] Regional pricing separated

---

## Phase 6: Staging & ETL Pipeline

### Objectives
Create staging tables and ETL pipeline for safe, auditable imports.

### Staging Tables
- `import_batches`
- `import_files`
- `import_rows`
- `import_row_errors`
- `staged_manufacturers`
- `staged_categories`
- `staged_products`
- `staged_attributes`
- `staged_product_specs`
- `staged_prices`
- `staged_inventory`
- `import_conflicts`
- `import_approvals`

### ETL Flow
```
Upload/API Fetch
    ↓
Validate Source
    ↓
Parse (CSV/XML/JSON)
    ↓
Store Raw Payload
    ↓
Stage Records
    ↓
Normalize (manufacturer, category, attributes)
    ↓
Convert Units
    ↓
Detect Duplicates
    ↓
Calculate Quality Score
    ↓
Preview
    ↓
Approve/Reject
    ↓
Publish
    ↓
Index Search
    ↓
Record Audit
```

### Acceptance Criteria
- [ ] Imports are resumable
- [ ] Imports are idempotent
- [ ] Errors are captured and downloadable
- [ ] Progress is trackable
- [ ] Dry-run mode works

---

## Phase 7-18 Summary

Due to document length constraints, Phases 7-18 are summarized here with full details available in separate implementation documents:

| Phase | Key Deliverables | Sprint Estimate |
|-------|------------------|-----------------|
| 7: CSV Import | Upload UI, column mapping, preview, batch processing | 2 |
| 8: XML Import | XPath mapping, namespace handling, XSD validation | 2 |
| 9: Mouser API | ApiClient, mappers, rate limiting, caching | 3 |
| 10: Admin UI | Import center dashboard, wizard, review queues | 3 |
| 11: Review/Conflict | Conflict resolution UI, merge workflows | 2 |
| 12: Regional | Regional SKUs, prices, warehouses, inventory | 2 |
| 13: Search | Meilisearch/OpenSearch integration, facets | 2 |
| 14: Data Quality | Scoring algorithm, quality flags | 1 |
| 15: Security | XXE protection, SSRF prevention, encryption | 1 |
| 16: Performance | Streaming, bulk inserts, indexing | 2 |
| 17: Documentation | User guides, API docs, templates | 1 |
| 18: Validation | Test suite, dry-runs, security tests | 2 |

---

## Resource Requirements

### Development Team
- 1 Principal Architect (oversight)
- 2 Senior Backend Engineers (core implementation)
- 1 Frontend Engineer (admin UI)
- 1 DevOps Engineer (infrastructure, search)
- 1 QA Engineer (testing, validation)

### Infrastructure
- Queue workers (Redis/database)
- File storage (S3-compatible)
- Search engine (Meilisearch or OpenSearch)
- Monitoring (logs, metrics, alerts)

### External Dependencies
- Mouser API license
- Legal review for source agreements
- Security audit before production

---

## Risk Management

### Technical Risks
| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Data corruption | Medium | Critical | Staging tables, rollback capability |
| Performance degradation | Medium | High | Load testing, gradual rollout |
| API rate limit exceeded | Low | Medium | Automated throttling |
| Search index failures | Low | High | Fallback to database queries |

### Business Risks
| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| License violations | Low | Critical | Compliance checks, legal review |
| Duplicate products | Medium | Medium | Deduplication algorithms |
| Poor data quality | Medium | Medium | Quality scoring, review queues |

---

## Success Metrics

### Functional Metrics
- Number of supported import sources
- Import success rate (>95% target)
- Average import processing time
- Duplicate detection accuracy

### Quality Metrics
- Data quality score distribution
- Review queue resolution time
- Customer-reported data errors

### Performance Metrics
- Products imported per hour
- API requests within rate limits
- Search query response time

---

## Next Steps

1. **Review audit documents** with stakeholders
2. **Prioritize phases** based on business needs
3. **Allocate resources** for Phase 2-4
4. **Set up development environment**
5. **Begin Phase 2 implementation**

---

**Document Version:** 1.0  
**Author:** Principal Product Data Architect  
**Approval Status:** Pending stakeholder review  
**Target Start Date:** Upon approval
