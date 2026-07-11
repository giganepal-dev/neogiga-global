# PCB Database Model Guide

## Overview

This guide documents the complete database schema for the PCB platform, including:
- New PCB-specific tables
- Extensions to existing NeoGiga tables
- Relationships and foreign keys
- Indexes for performance
- Data integrity constraints

## Design Principles

1. **Additive Only**: All migrations are additive, reversible, and non-destructive
2. **UUID Primary Keys**: All PCB entities use UUID for security and distribution
3. **Soft Deletes**: Critical business entities support soft deletes
4. **Audit Trail**: All changes logged with user, timestamp, and context
5. **Marketplace Context**: All entities scoped to marketplace for localization
6. **Organization Isolation**: Multi-tenant design prevents cross-org data leaks

## Core PCB Tables

### 1. pcb_projects

Main project workspace table.

```php
// database/migrations/2024_01_01_000001_create_pcb_projects_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pcb_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->onDelete('set null');
            $table->string('marketplace', 2)->default('en');
            
            // Project identification
            $table->string('name');
            $table->string('code')->unique(); // Auto-generated: PCB-YYYYMMDD-XXXX
            $table->text('description')->nullable();
            $table->string('application')->nullable(); // IoT, Automotive, Industrial, etc.
            
            // Confidentiality
            $table->enum('confidentiality', ['public', 'internal', 'confidential', 'nda_required'])->default('internal');
            
            // Project type
            $table->enum('type', ['prototype', 'production'])->default('prototype');
            $table->unsignedInteger('target_quantity')->default(1);
            $table->decimal('target_budget', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            // Timeline
            $table->date('required_date')->nullable();
            $table->timestamp('estimated_completion')->nullable();
            
            // Shipping
            $table->string('destination_country', 2);
            $table->string('shipping_postal_code', 20)->nullable();
            $table->string('preferred_region', 50)->nullable();
            
            // Preferences
            $table->foreignUuid('preferred_manufacturer_id')->nullable()->constrained('manufacturers')->onDelete('set null');
            $table->foreignUuid('preferred_warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
            $table->foreignUuid('assigned_engineer_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Status tracking
            $table->enum('status', [
                'draft',
                'requirements_pending',
                'design_requested',
                'design_in_progress',
                'design_review',
                'design_approved',
                'files_ready',
                'quote_pending',
                'quoted',
                'awaiting_approval',
                'ordered',
                'manufacturing',
                'inspection',
                'shipped',
                'completed',
                'on_hold',
                'cancelled'
            ])->default('draft');
            
            $table->string('current_version')->default('1.0.0');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['owner_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index('marketplace');
            $table->index('code');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pcb_projects');
    }
};
```

### 2. pcb_project_members

Project team membership.

```php
Schema::create('pcb_project_members', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
    $table->enum('role', ['owner', 'admin', 'editor', 'viewer', 'supplier', 'engineer']);
    $table->timestamp('joined_at')->useCurrent();
    $table->timestamp('expires_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    $table->unique(['project_id', 'user_id']);
    $table->index('expires_at');
});
```

### 3. pcb_project_versions

Version history for projects.

```php
Schema::create('pcb_project_versions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->string('version');
    $table->text('change_summary')->nullable();
    $table->json('changed_fields')->nullable();
    $table->foreignUuid('user_id')->constrained('users')->onDelete('set null');
    $table->timestamp('created_at')->useCurrent();
    
    $table->index(['project_id', 'created_at']);
});
```

### 4. pcb_files

Master file registry for all PCB files.

```php
Schema::create('pcb_files', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->foreignUuid('owner_id')->constrained('users')->onDelete('cascade');
    $table->string('name');
    $table->string('original_filename');
    $table->string('mime_type');
    $table->unsignedBigInteger('size_bytes');
    $table->string('storage_disk');
    $table->string('storage_path');
    $table->string('file_hash', 64);
    $table->enum('file_type', ['schematic', 'pcb_source', 'gerber', 'drill', 'bom', 'cpl', 'assembly_drawing', 'fabrication_drawing', 'step', 'dxf', 'ipc356', 'test_file', 'firmware', 'certificate', 'quality_report', 'other']);
    $table->json('metadata')->nullable();
    $table->boolean('is_encrypted')->default(true);
    $table->boolean('malware_scanned')->default(false);
    $table->boolean('malware_clean')->default(true);
    $table->timestamp('scanned_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['project_id', 'file_type']);
    $table->index('file_hash');
});
```

### 5. pcb_file_access_logs

Access audit trail.

```php
Schema::create('pcb_file_access_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('file_id')->constrained('pcb_files')->onDelete('cascade');
    $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
    $table->enum('action', ['view', 'download', 'share', 'delete']);
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamp('accessed_at')->useCurrent();
    
    $table->index(['file_id', 'accessed_at']);
});
```

### 6. pcb_file_shares

Controlled file sharing.

```php
Schema::create('pcb_file_shares', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('file_id')->constrained('pcb_files')->onDelete('cascade');
    $table->foreignUuid('shared_by_id')->constrained('users')->onDelete('cascade');
    $table->foreignUuid('shared_with_user_id')->nullable()->constrained('users')->onDelete('cascade');
    $table->enum('access_level', ['view', 'download']);
    $table->timestamp('expires_at');
    $table->boolean('requires_nda')->default(false);
    $table->boolean('nda_accepted')->default(false);
    $table->string('access_token', 64)->unique();
    $table->integer('download_count')->default(0);
    $table->integer('max_downloads')->nullable();
    $table->timestamps();
    
    $table->index('access_token');
});
```

## Design Service Tables

### 7. pcb_design_requests

Design service intake.

```php
Schema::create('pcb_design_requests', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->foreignUuid('requester_id')->constrained('users')->onDelete('cascade');
    $table->foreignUuid('assigned_designer_id')->nullable()->constrained('users')->onDelete('set null');
    $table->json('services');
    $table->text('project_brief');
    $table->string('preferred_eda')->nullable();
    $table->decimal('board_length_mm', 8, 2)->nullable();
    $table->decimal('board_width_mm', 8, 2)->nullable();
    $table->unsignedTinyInteger('layer_target')->nullable();
    $table->json('interfaces')->nullable();
    $table->enum('status', ['pending', 'assigned', 'in_progress', 'review', 'revision_required', 'approved', 'rejected', 'cancelled'])->default('pending');
    $table->decimal('estimated_cost', 15, 2)->nullable();
    $table->string('currency', 3)->default('USD');
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['project_id', 'status']);
});
```

### 8. pcb_design_milestones

Design milestone tracking.

```php
Schema::create('pcb_design_milestones', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('design_request_id')->constrained('pcb_design_requests')->onDelete('cascade');
    $table->enum('milestone', [
        'requirements_approval',
        'library_component_review',
        'schematic_review',
        'placement_review',
        'routing_review',
        'preliminary_dfm',
        'final_review',
        'manufacturing_files',
        'customer_acceptance'
    ]);
    $table->enum('status', ['pending', 'submitted', 'in_review', 'approved', 'rejected', 'revision_requested'])->default('pending');
    $table->timestamp('submitted_at')->nullable();
    $table->foreignUuid('submitted_by_id')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('reviewed_at')->nullable();
    $table->foreignUuid('reviewed_by_id')->nullable()->constrained('users')->onDelete('set null');
    $table->text('review_comments')->nullable();
    $table->timestamps();
    
    $table->unique(['design_request_id', 'milestone']);
});
```

## Gerber Analysis Tables

### 9. pcb_file_analysis_runs

Gerber analysis job tracking.

```php
Schema::create('pcb_file_analysis_runs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->foreignUuid('gerber_file_id')->constrained('pcb_files')->onDelete('cascade');
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
    $table->string('parser_version');
    $table->json('configuration')->nullable();
    $table->text('error_message')->nullable();
    $table->unsignedInteger('processing_duration_ms')->nullable();
    $table->timestamps();
    
    $table->index(['project_id', 'status']);
});
```

### 10. pcb_detected_layers

Detected Gerber layers.

```php
Schema::create('pcb_detected_layers', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('analysis_run_id')->constrained('pcb_file_analysis_runs')->onDelete('cascade');
    $table->string('filename');
    $table->enum('detected_type', [
        'top_copper', 'bottom_copper', 'inner_copper',
        'top_solder_mask', 'bottom_solder_mask',
        'top_silkscreen', 'bottom_silkscreen',
        'top_paste', 'bottom_paste',
        'board_outline', 'drill', 'slots', 'mechanical'
    ]);
    $table->enum('confidence', ['high', 'medium', 'low']);
    $table->boolean('is_mapped')->default(false);
    $table->string('mapped_standard_name')->nullable();
    $table->timestamps();
    
    $table->index('analysis_run_id');
});
```

### 11. pcb_detected_dimensions

Detected board dimensions.

```php
Schema::create('pcb_detected_dimensions', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('analysis_run_id')->constrained('pcb_file_analysis_runs')->onDelete('cascade');
    $table->decimal('length_mm', 8, 2)->nullable();
    $table->decimal('width_mm', 8, 2)->nullable();
    $table->decimal('area_cm2', 8, 2)->nullable();
    $table->unsignedTinyInteger('layer_count')->nullable();
    $table->enum('unit', ['mm', 'inch'])->default('mm');
    $table->enum('confidence', ['high', 'medium', 'low']);
    $table->boolean('requires_verification')->default(true);
    $table->timestamps();
    
    $table->index('analysis_run_id');
});
```

### 12. pcb_analysis_warnings

Analysis warnings and issues.

```php
Schema::create('pcb_analysis_warnings', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('analysis_run_id')->constrained('pcb_file_analysis_runs')->onDelete('cascade');
    $table->enum('severity', ['information', 'warning', 'blocking', 'engineering_review_required']);
    $table->string('warning_code');
    $table->text('message');
    $table->json('context')->nullable();
    $table->boolean('is_resolved')->default(false);
    $table->foreignUuid('resolved_by_id')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();
    
    $table->index(['analysis_run_id', 'severity']);
});
```

## Quote Configuration Tables

### 13. pcb_quote_configurations

Quote configuration snapshots.

```php
Schema::create('pcb_quote_configurations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->foreignUuid('created_by_id')->constrained('users')->onDelete('cascade');
    
    // Board specifications
    $table->enum('board_type', ['rigid', 'flex', 'rigid_flex', 'aluminum', 'ceramic'])->default('rigid');
    $table->unsignedTinyInteger('designs_per_panel')->default(1);
    $table->unsignedInteger('quantity')->default(1);
    $table->decimal('length_mm', 8, 2);
    $table->decimal('width_mm', 8, 2);
    $table->unsignedTinyInteger('layers')->default(2);
    $table->string('substrate')->default('FR-4');
    $table->unsignedSmallInteger('tg_value')->default(130);
    $table->decimal('thickness_mm', 6, 2)->default(1.6);
    $table->string('outer_copper_oz')->default('1');
    $table->string('inner_copper_oz')->default('0.5');
    $table->string('solder_mask_color')->default('green');
    $table->string('silkscreen_color')->default('white');
    $table->string('surface_finish')->default('hasl_lead_free');
    $table->enum('via_covering', ['tented', 'untented', 'plugged', 'filled'])->default('tented');
    $table->boolean('blind_buried_vias')->default(false);
    $table->boolean('hdi')->default(false);
    $table->decimal('min_hole_mm', 6, 3)->default(0.3);
    $table->boolean('edge_plating')->default(false);
    $table->boolean('gold_fingers')->default(false);
    $table->boolean('castellated_holes')->default(false);
    $table->enum('panelization_type', ['none', 'v_score', 'routing', 'tab_route'])->default('none');
    $table->boolean('aoi')->default(true);
    $table->boolean('electrical_test')->default(true);
    $table->enum('production_speed', ['standard', 'fast', 'express'])->default('standard');
    $table->unsignedSmallInteger('lead_time_days')->default(7);
    $table->boolean('assembly_required')->default(false);
    $table->boolean('stencil_required')->default(false);
    
    $table->enum('status', ['draft', 'submitted', 'quoted', 'approved', 'expired', 'converted_to_order'])->default('draft');
    $table->timestamps();
    
    $table->index(['project_id', 'status']);
});
```

### 14. pcb_quotes

Generated quotes from suppliers.

```php
Schema::create('pcb_quotes', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('configuration_id')->constrained('pcb_quote_configurations')->onDelete('cascade');
    $table->foreignUuid('supplier_id')->constrained('manufacturers')->onDelete('cascade');
    $table->string('quote_reference')->nullable();
    
    // Pricing breakdown
    $table->decimal('pcb_setup_charge', 15, 2)->default(0);
    $table->decimal('engineering_charge', 15, 2)->default(0);
    $table->decimal('pcb_fabrication_cost', 15, 2)->default(0);
    $table->decimal('tooling_cost', 15, 2)->default(0);
    $table->decimal('testing_cost', 15, 2)->default(0);
    $table->decimal('assembly_setup_cost', 15, 2)->default(0);
    $table->decimal('assembly_cost', 15, 2)->default(0);
    $table->decimal('stencil_cost', 15, 2)->default(0);
    $table->decimal('component_cost', 15, 2)->default(0);
    $table->decimal('freight_cost', 15, 2)->default(0);
    $table->decimal('duty_cost', 15, 2)->default(0);
    $table->decimal('tax_cost', 15, 2)->default(0);
    $table->decimal('neogiga_margin', 15, 2)->default(0);
    $table->decimal('total_cost', 15, 2);
    $table->decimal('total_price', 15, 2);
    $table->string('currency', 3)->default('USD');
    
    // Lead time
    $table->unsignedSmallInteger('production_days');
    $table->unsignedSmallInteger('shipping_days')->nullable();
    $table->date('estimated_delivery')->nullable();
    $table->date('valid_until');
    
    $table->enum('status', ['pending', 'submitted', 'accepted', 'rejected', 'expired', 'superseded'])->default('pending');
    $table->text('supplier_notes')->nullable();
    $table->timestamps();
    
    $table->index(['configuration_id', 'status']);
    $table->index('valid_until');
});
```

## BOM/CPL Integration Tables

### 15. pcb_bom_imports

BOM import tracking.

```php
Schema::create('pcb_bom_imports', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->foreignUuid('imported_by_id')->constrained('users')->onDelete('cascade');
    $table->string('source_file')->nullable();
    $table->enum('source_type', ['csv', 'xls', 'xlsx', 'copy_paste', 'api'])->default('csv');
    $table->unsignedInteger('total_lines');
    $table->unsignedInteger('valid_lines')->default(0);
    $table->unsignedInteger('matched_lines')->default(0);
    $table->unsignedInteger('unmatched_lines')->default(0);
    $table->json('validation_errors')->nullable();
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
    $table->timestamps();
    
    $table->index(['project_id', 'status']);
});
```

### 16. pcb_bom_lines

BOM line items.

```php
Schema::create('pcb_bom_lines', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('bom_import_id')->constrained('pcb_bom_imports')->onDelete('cascade');
    $table->unsignedInteger('line_number');
    $table->string('reference_designator');
    $table->json('reference_designators')->nullable();
    $table->unsignedInteger('quantity')->default(1);
    $table->string('manufacturer')->nullable();
    $table->string('manufacturer_part_number')->nullable();
    $table->string('mpn_normalized')->nullable();
    $table->string('description')->nullable();
    $table->string('value')->nullable();
    $table->string('package')->nullable();
    $table->string('footprint')->nullable();
    
    // Matching results
    $table->foreignUuid('matched_product_id')->nullable()->constrained('products')->onDelete('set null');
    $table->enum('match_confidence', ['exact', 'high', 'medium', 'low', 'no_match'])->default('no_match');
    $table->foreignUuid('matched_supplier_id')->nullable()->constrained('manufacturers')->onDelete('set null');
    $table->decimal('matched_price', 15, 4)->nullable();
    $table->unsignedInteger('matched_moq')->nullable();
    $table->boolean('is_approved')->default(false);
    
    $table->timestamps();
    
    $table->index(['bom_import_id', 'line_number']);
    $table->index('mpn_normalized');
    $table->index('matched_product_id');
});
```

### 17. pcb_cpl_imports

CPL import tracking.

```php
Schema::create('pcb_cpl_imports', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->foreignUuid('imported_by_id')->constrained('users')->onDelete('cascade');
    $table->string('source_file')->nullable();
    $table->enum('source_type', ['csv', 'xls', 'xlsx', 'txt', 'api'])->default('csv');
    $table->unsignedInteger('total_lines');
    $table->unsignedInteger('valid_lines')->default(0);
    $table->json('validation_errors')->nullable();
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
    $table->timestamps();
    
    $table->index(['project_id', 'status']);
});
```

### 18. pcb_cpl_lines

CPL line items.

```php
Schema::create('pcb_cpl_lines', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('cpl_import_id')->constrained('pcb_cpl_imports')->onDelete('cascade');
    $table->unsignedInteger('line_number');
    $table->string('reference_designator');
    $table->decimal('x_coord', 10, 4);
    $table->decimal('y_coord', 10, 4);
    $table->decimal('rotation', 6, 2);
    $table->enum('side', ['top', 'bottom'])->default('top');
    $table->string('package')->nullable();
    $table->string('footprint')->nullable();
    $table->boolean('is_dnp')->default(false);
    
    // Validation
    $table->boolean('has_bom_match')->default(false);
    $table->foreignUuid('bom_line_id')->nullable()->constrained('pcb_bom_lines')->onDelete('set null');
    $table->boolean('coordinates_valid')->default(true);
    
    $table->timestamps();
    
    $table->index(['cpl_import_id', 'reference_designator']);
    $table->index('bom_line_id');
});
```

### 19. pcb_component_matches

Component matching results.

```php
Schema::create('pcb_component_matches', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('bom_line_id')->constrained('pcb_bom_lines')->onDelete('cascade');
    $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
    $table->enum('match_type', ['exact_mpn', 'distributor_sku', 'manufacturer_alias', 'generic_product', 'alternative', 'manual']);
    $table->enum('confidence', ['exact', 'high', 'medium', 'low']);
    $table->decimal('price', 15, 4)->nullable();
    $table->unsignedInteger('moq')->nullable();
    $table->unsignedSmallInteger('lead_time_days')->nullable();
    $table->string('warehouse')->nullable();
    $table->unsignedInteger('stock_available')->default(0);
    $table->boolean('is_primary')->default(false);
    $table->boolean('is_approved')->default(false);
    $table->timestamps();
    
    $table->index(['bom_line_id', 'is_primary']);
});
```

## DFM Tables

### 20. pcb_dfm_checks

DFM check definitions.

```php
Schema::create('pcb_dfm_checks', function (Blueprint $table) {
    $table->id();
    $table->string('check_code')->unique();
    $table->string('name');
    $table->text('description');
    $table->enum('category', ['geometry', 'drill', 'copper', 'mask', 'silkscreen', 'assembly', 'testability']);
    $table->enum('severity', ['information', 'warning', 'blocking', 'engineering_review_required']);
    $table->json('parameters')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    $table->index('category');
});
```

### 21. pcb_dfm_runs

DFM analysis runs.

```php
Schema::create('pcb_dfm_runs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->foreignUuid('quote_config_id')->constrained('pcb_quote_configurations')->onDelete('cascade');
    $table->foreignUuid('triggered_by_id')->constrained('users')->onDelete('cascade');
    $table->enum('trigger_type', ['manual', 'automatic', 'on_upload', 'on_quote']);
    $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
    $table->json('checks_performed');
    $table->unsignedInteger('issues_found')->default(0);
    $table->unsignedInteger('blocking_issues')->default(0);
    $table->timestamps();
    
    $table->index(['project_id', 'status']);
});
```

### 22. pcb_dfm_issues

DFM issues found.

```php
Schema::create('pcb_dfm_issues', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('dfm_run_id')->constrained('pcb_dfm_runs')->onDelete('cascade');
    $table->foreignId('dfm_check_id')->constrained('pcb_dfm_checks')->onDelete('cascade');
    $table->enum('severity', ['information', 'warning', 'blocking', 'engineering_review_required']);
    $table->string('issue_code');
    $table->text('description');
    $table->json('location')->nullable();
    $table->text('recommendation')->nullable();
    $table->boolean('is_acknowledged')->default(false);
    $table->boolean('is_waived')->default(false);
    $table->boolean('is_resolved')->default(false);
    $table->timestamps();
    
    $table->index(['dfm_run_id', 'severity']);
});
```

## Manufacturer Capability Tables

### 23. pcb_manufacturers

Extended manufacturer capabilities.

```php
Schema::create('pcb_manufacturers', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('manufacturer_id')->constrained('manufacturers')->onDelete('cascade');
    
    // Capabilities
    $table->json('supported_board_types');
    $table->unsignedTinyInteger('max_layers')->default(2);
    $table->decimal('min_board_size_mm', 6, 2)->default(5);
    $table->decimal('max_board_size_mm', 6, 2)->default(500);
    $table->decimal('min_thickness_mm', 6, 2)->default(0.4);
    $table->decimal('max_thickness_mm', 6, 2)->default(6.0);
    $table->decimal('min_trace_spacing_mm', 6, 3)->default(0.1);
    $table->decimal('min_hole_size_mm', 6, 3)->default(0.2);
    
    // Materials and finishes
    $table->json('supported_substrates');
    $table->json('supported_finishes');
    
    // Special processes
    $table->boolean('supports_blind_buried_vias')->default(false);
    $table->boolean('supports_hdi')->default(false);
    $table->boolean('supports_edge_plating')->default(false);
    $table->boolean('supports_impedance_control')->default(false);
    
    // Assembly capabilities
    $table->boolean('supports_smt_assembly')->default(false);
    $table->boolean('supports_tht_assembly')->default(false);
    $table->boolean('supports_conformal_coating')->default(false);
    
    // Certifications
    $table->json('certifications');
    $table->json('service_regions');
    
    // Lead times
    $table->unsignedSmallInteger('standard_lead_time_days')->default(7);
    $table->unsignedSmallInteger('fast_lead_time_days')->default(3);
    
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    $table->index('manufacturer_id');
});
```

## Production Tracking Tables

### 24. pcb_order_events

Order production events.

```php
Schema::create('pcb_order_events', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->enum('stage', [
        'order_confirmed', 'engineering_review', 'files_approved',
        'material_preparation', 'fabrication', 'drilling', 'plating',
        'solder_mask', 'silkscreen', 'finish', 'routing',
        'electrical_test', 'assembly', 'aoi', 'xray',
        'programming', 'functional_test', 'quality_inspection',
        'packaging', 'shipped', 'delivered'
    ]);
    $table->enum('status', ['started', 'completed', 'delayed', 'blocked'])->default('started');
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->text('notes')->nullable();
    $table->foreignUuid('reported_by_id')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamps();
    
    $table->index(['order_id', 'stage']);
});
```

### 25. pcb_quality_reports

Quality inspection reports.

```php
Schema::create('pcb_quality_reports', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->enum('report_type', ['first_article', 'in_process', 'final_inspection', 'customer_complaint']);
    $table->enum('result', ['pass', 'pass_with_deviation', 'fail', 'pending']);
    $table->json('defects')->nullable();
    $table->json('measurements')->nullable();
    $table->json('attachments')->nullable();
    $table->text('summary')->nullable();
    $table->foreignUuid('inspector_id')->constrained('users')->onDelete('cascade');
    $table->timestamp('inspected_at')->useCurrent();
    $table->timestamps();
    
    $table->index(['order_id', 'report_type']);
});
```

### 26. pcb_complaints

Customer complaints and claims.

```php
Schema::create('pcb_complaints', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->foreignUuid('customer_id')->constrained('users')->onDelete('cascade');
    $table->string('complaint_type'); // defect, missing_boards, wrong_finish, dimensional_issue, assembly_issue, wrong_component, soldering_defect, functional_failure, shipping_damage
    $table->text('description');
    $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
    $table->enum('status', ['open', 'under_review', 'investigation', 'resolution_proposed', 'resolved', 'closed'])->default('open');
    $table->json('evidence_file_ids')->nullable();
    $table->text('root_cause')->nullable();
    $table->text('corrective_action')->nullable();
    $table->enum('resolution', ['remake', 'rework', 'refund', 'credit', 'replacement', 'rejected'])->nullable();
    $table->decimal('compensation_amount', 15, 2)->nullable();
    $table->foreignUuid('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();
    
    $table->index(['order_id', 'status']);
});
```

## Project Activity Tables

### 27. pcb_project_activity_logs

Audit trail for project activities.

```php
Schema::create('pcb_project_activity_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->string('action');
    $table->text('description');
    $table->json('context')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamp('created_at')->useCurrent();
    
    $table->index(['project_id', 'created_at']);
    $table->index('action');
});
```

### 28. pcb_project_comments

Comments and discussions.

```php
Schema::create('pcb_project_comments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
    $table->foreignUuid('parent_id')->nullable()->constrained('pcb_project_comments')->onDelete('cascade');
    $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
    $table->text('content');
    $table->json('mentions')->nullable();
    $table->boolean('is_edited')->default(false);
    $table->timestamp('edited_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['project_id', 'created_at']);
});
```

## Index Summary

| Table | Primary Indexes | Purpose |
|-------|----------------|---------|
| pcb_projects | owner_id+status, org_id+status, code, marketplace | Fast project lookup |
| pcb_files | project_id+file_type, file_hash | File organization, deduplication |
| pcb_bom_lines | bom_import_id+line_number, mpn_normalized, matched_product_id | BOM processing, matching |
| pcb_quotes | configuration_id+status, valid_until | Quote management |
| pcb_dfm_issues | dfm_run_id+severity, dfm_run_id+is_resolved | DFM issue tracking |
| pcb_order_events | order_id+stage | Production tracking |

## Foreign Key Relationships

```
pcb_projects.owner_id → users.id
pcb_projects.organization_id → organizations.id
pcb_projects.preferred_manufacturer_id → manufacturers.id
pcb_projects.preferred_warehouse_id → warehouses.id

pcb_project_members.project_id → pcb_projects.id
pcb_project_members.user_id → users.id

pcb_files.project_id → pcb_projects.id
pcb_files.owner_id → users.id

pcb_design_requests.project_id → pcb_projects.id
pcb_design_requests.requester_id → users.id

pcb_bom_imports.project_id → pcb_projects.id
pcb_bom_lines.bom_import_id → pcb_bom_imports.id
pcb_bom_lines.matched_product_id → products.id

pcb_cpl_imports.project_id → pcb_projects.id
pcb_cpl_lines.cpl_import_id → pcb_cpl_imports.id
pcb_cpl_lines.bom_line_id → pcb_bom_lines.id

pcb_quote_configurations.project_id → pcb_projects.id
pcb_quotes.configuration_id → pcb_quote_configurations.id
pcb_quotes.supplier_id → manufacturers.id

pcb_dfm_runs.project_id → pcb_projects.id
pcb_dfm_issues.dfm_run_id → pcb_dfm_runs.id

pcb_manufacturers.manufacturer_id → manufacturers.id

pcb_order_events.order_id → orders.id
pcb_order_events.project_id → pcb_projects.id

pcb_quality_reports.order_id → orders.id
pcb_complaints.order_id → orders.id
```

## Migration Strategy

1. Run core table migrations first (projects, files, members)
2. Run design service migrations
3. Run Gerber analysis migrations
4. Run quote configuration migrations
5. Run BOM/CPL integration migrations
6. Run DFM migrations
7. Run manufacturer capability migrations
8. Run production tracking migrations

All migrations include `down()` methods for rollback.

## Next Steps

1. Create migration files in `database/migrations/`
2. Create Eloquent models in `app/Models/Pcb/`
3. Create factories for testing in `database/factories/Pcb/`
4. Run migrations in development environment
5. Seed reference data (DFM checks, etc.)
