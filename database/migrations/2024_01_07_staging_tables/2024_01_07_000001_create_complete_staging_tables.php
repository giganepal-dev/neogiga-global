<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 7: Complete Staging Tables for ETL Pipeline
     */
    public function up(): void
    {
        // Continue staging tables from Phase 2
        Schema::create('staged_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->string('external_category_id');
            $table->string('category_name');
            $table->string('parent_category_name')->nullable();
            $table->string('category_path')->nullable();
            $table->foreignId('mapped_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->enum('match_type', ['exact', 'fuzzy', 'new', 'unmatched'])->default('unmatched');
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->json('raw_data');
            $table->json('normalized_data')->nullable();
            $table->enum('status', ['pending', 'mapped', 'approved', 'rejected', 'imported'])->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['import_batch_id', 'status']);
            $table->index('external_category_id');
        });

        Schema::create('staged_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->string('external_product_id')->nullable();
            
            // Core identification
            $table->string('manufacturer_name')->nullable();
            $table->foreignId('mapped_manufacturer_id')->nullable()->constrained('manufacturers')->nullOnDelete();
            $table->string('brand_name')->nullable();
            $table->foreignId('mapped_brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('manufacturer_part_number');
            $table->string('product_name');
            $table->string('category_name')->nullable();
            $table->foreignId('mapped_category_id')->nullable()->constrained('categories')->nullOnDelete();
            
            // Descriptions
            $table->text('short_description')->nullable();
            $table->longText('full_description')->nullable();
            
            // Lifecycle and compliance
            $table->string('lifecycle_status')->nullable();
            $table->string('package_case')->nullable();
            $table->string('mounting_type')->nullable();
            $table->string('country_of_origin', 2)->nullable();
            $table->string('hs_code')->nullable();
            $table->string('eccn')->nullable();
            $table->boolean('lead_free')->nullable();
            $table->string('rohs_status')->nullable();
            $table->string('reach_status')->nullable();
            $table->string('moisture_sensitivity_level')->nullable();
            
            // Links
            $table->string('datasheet_url')->nullable();
            $table->string('manufacturer_product_url')->nullable();
            
            // Matching results
            $table->foreignId('existing_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->enum('match_type', ['exact', 'fuzzy', 'new', 'duplicate'])->default('new');
            $table->decimal('match_confidence', 5, 4)->nullable();
            $table->boolean('requires_review')->default(false);
            $table->json('review_flags')->nullable(); // ['unknown_manufacturer', 'duplicate_mpn', etc.]
            
            // Quality
            $table->decimal('data_quality_score', 5, 2)->default(0);
            
            // Raw and normalized data
            $table->json('raw_data');
            $table->json('normalized_data')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'validated', 'approved', 'rejected', 'imported', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['import_batch_id', 'status']);
            $table->index(['mapped_manufacturer_id', 'manufacturer_part_number']);
            $table->index('existing_product_id');
            $table->index('requires_review');
        });

        Schema::create('staged_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staged_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->string('attribute_name'); // From source
            $table->foreignId('mapped_attribute_id')->nullable()->constrained('attributes')->nullOnDelete();
            $table->string('attribute_value');
            $table->string('attribute_unit')->nullable();
            $table->foreignId('mapped_unit_id')->nullable()->constrained('attribute_units')->nullOnDelete();
            $table->enum('match_type', ['exact', 'fuzzy', 'new', 'unmatched'])->default('unmatched');
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->boolean('requires_unit_conversion')->default(false);
            $table->string('converted_value')->nullable();
            $table->string('converted_unit')->nullable();
            $table->boolean('requires_review')->default(false);
            $table->enum('status', ['pending', 'mapped', 'approved', 'rejected', 'imported'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['staged_product_id', 'status']);
            $table->index('attribute_name');
        });

        Schema::create('staged_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staged_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('currency', 3);
            $table->decimal('unit_price', 20, 6);
            $table->integer('quantity_break')->default(1);
            $table->integer('max_quantity')->nullable();
            $table->string('price_type')->default('unit'); // 'unit', 'bulk', 'contract'
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->foreignId('mapped_regional_sku_id')->nullable();
            $table->enum('status', ['pending', 'validated', 'approved', 'rejected', 'imported'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['staged_product_id', 'status']);
            $table->index(['currency', 'quantity_break']);
        });

        Schema::create('staged_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staged_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->string('warehouse_code')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->bigInteger('quantity_available')->default(0);
            $table->bigInteger('quantity_on_order')->nullable();
            $table->string('stock_status')->nullable(); // 'in_stock', 'low_stock', 'out_of_stock', 'backorder'
            $table->string('lead_time')->nullable();
            $table->date('last_updated')->nullable();
            $table->date('next_restock_date')->nullable();
            $table->foreignId('mapped_warehouse_id')->nullable();
            $table->enum('status', ['pending', 'validated', 'approved', 'rejected', 'imported'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['staged_product_id', 'status']);
            $table->index('warehouse_code');
        });

        Schema::create('staged_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staged_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->enum('media_type', ['image', 'datasheet', 'cad_model', '3d_model', 'compliance_doc', 'application_note']);
            $table->string('url');
            $table->string('title')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->string('license_status')->nullable();
            $table->boolean('requires_license_review')->default(false);
            $table->enum('status', ['pending', 'validated', 'approved', 'rejected', 'imported'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['staged_product_id', 'status']);
        });

        Schema::create('import_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->string('conflict_type'); // 'duplicate_mpn', 'unknown_manufacturer', 'category_mismatch', 'spec_conflict', 'price_mismatch'
            $table->string('severity')->default('warning'); // 'info', 'warning', 'error', 'critical'
            $table->foreignId('staged_record_id')->nullable(); // Polymorphic reference
            $table->string('staged_record_type')->nullable();
            $table->foreignId('existing_record_id')->nullable();
            $table->string('existing_record_type')->nullable();
            $table->json('conflict_details');
            $table->json('suggested_resolution')->nullable();
            $table->enum('status', ['open', 'resolved', 'ignored', 'escalated'])->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['import_batch_id', 'status']);
            $table->index('conflict_type');
            $table->index('severity');
        });

        Schema::create('import_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('approval_type', ['batch', 'manufacturer', 'category', 'product', 'attribute', 'price', 'media']);
            $table->foreignId('record_id')->nullable();
            $table->string('record_type')->nullable();
            $table->enum('action', ['approve', 'reject', 'request_changes', 'skip']);
            $table->text('comments')->nullable();
            $table->json('changes_requested')->nullable();
            $table->timestamps();

            $table->index(['import_batch_id', 'approval_type']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_approvals');
        Schema::dropIfExists('import_conflicts');
        Schema::dropIfExists('staged_media');
        Schema::dropIfExists('staged_inventory');
        Schema::dropIfExists('staged_prices');
        Schema::dropIfExists('staged_attributes');
        Schema::dropIfExists('staged_products');
        Schema::dropIfExists('staged_categories');
    }
};
