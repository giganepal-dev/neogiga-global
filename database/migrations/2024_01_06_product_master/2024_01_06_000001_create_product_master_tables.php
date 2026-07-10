<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 6: Product Master
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id('global_product_id');
            
            // Core identification
            $table->foreignId('manufacturer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('manufacturer_part_number');
            $table->string('normalized_mpn')->index(); // Normalized for search/matching
            $table->string('product_name');
            $table->string('generic_name')->nullable(); // Generic name for grouping
            
            // Categorization
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            
            // Descriptions
            $table->text('short_description')->nullable();
            $table->longText('full_description')->nullable();
            
            // Lifecycle and status
            $table->enum('lifecycle_status', [
                'active',
                'nrnd', // Not Recommended for New Design
                'obsolete',
                'end_of_life',
                'preview',
                'discontinued',
                'unknown'
            ])->default('unknown');
            
            // Package and mechanical
            $table->string('package_case')->nullable();
            $table->string('mounting_type')->nullable();
            
            // Compliance and regulatory
            $table->string('country_of_origin', 2)->nullable();
            $table->string('hs_code')->nullable(); // Harmonized System code
            $table->string('eccn')->nullable(); // Export Control Classification Number
            $table->boolean('lead_free')->nullable();
            $table->enum('rohs_status', ['compliant', 'non_compliant', 'exempt', 'unknown'])->default('unknown');
            $table->enum('reach_status', ['compliant', 'non_compliant', 'unknown'])->default('unknown');
            $table->string('moisture_sensitivity_level')->nullable();
            
            // Links
            $table->string('datasheet_url')->nullable();
            $table->string('manufacturer_product_url')->nullable();
            $table->string('source_url')->nullable();
            
            // Source tracking
            $table->foreignId('source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
            $table->string('external_source_id')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            
            // Quality and workflow
            $table->decimal('data_quality_score', 5, 2)->default(0);
            $table->enum('status', [
                'imported',
                'needs_review',
                'approved',
                'published',
                'rejected',
                'archived'
            ])->default('imported');
            
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->json('source_payload')->nullable(); // Original source data
            
            $table->timestamps();
            $table->softDeletes();

            // Critical indexes
            $table->unique(['manufacturer_id', 'manufacturer_part_number']);
            $table->index('normalized_mpn');
            $table->index('slug');
            $table->index('category_id');
            $table->index('status');
            $table->index('lifecycle_status');
            $table->index('manufacturer_id');
        });

        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('product_name');
            $table->text('short_description')->nullable();
            $table->longText('full_description')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->unique(['global_product_id', 'locale']);
            $table->index('locale');
        });

        Schema::create('product_external_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('external_mpn')->nullable(); // MPN from this source
            $table->string('source_url')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();

            $table->unique(['catalog_source_id', 'external_id']);
            $table->index('external_mpn');
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_unit_id')->nullable()->constrained('attribute_units')->nullOnDelete();
            
            // Value storage - multiple formats
            $table->string('value_string')->nullable();
            $table->bigInteger('value_integer')->nullable();
            $table->decimal('value_decimal', 30, 10)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_multi')->nullable(); // For multi_option
            $table->string('value_min')->nullable(); // For ranges
            $table->string('value_max')->nullable(); // For ranges
            
            // Original value preservation
            $table->string('original_value')->nullable();
            $table->string('original_unit_code')->nullable();
            
            // Source and quality
            $table->foreignId('source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
            $table->decimal('confidence_score', 5, 4)->default(1.0);
            $table->boolean('is_normalized')->default(false);
            $table->boolean('requires_review')->default(false);
            $table->text('review_notes')->nullable();
            
            $table->timestamps();

            $table->unique(['global_product_id', 'attribute_id']);
            $table->index('global_product_id');
            $table->index('attribute_id');
            $table->index('requires_review');
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('media_type', ['image', 'datasheet', 'cad_model', '3d_model', 'compliance_doc', 'application_note', 'video']);
            $table->string('url');
            $table->string('file_path')->nullable(); // If downloaded locally
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum')->nullable(); // SHA256
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->foreignId('source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
            $table->string('license_status')->nullable(); // 'licensed', 'unlicensed', 'unknown'
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('global_product_id');
            $table->index('media_type');
        });

        Schema::create('product_lifecycle_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('lifecycle_status', [
                'active', 'nrnd', 'obsolete', 'end_of_life', 'preview', 'discontinued', 'unknown'
            ]);
            $table->foreignId('source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index('global_product_id');
            $table->index('effective_date');
        });

        Schema::create('product_version_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // 'created', 'updated', 'spec_changed', 'price_changed', etc.
            $table->json('changes')->nullable(); // Diff of changes
            $table->json('previous_state')->nullable();
            $table->json('new_state')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
            $table->string('change_reason')->nullable();
            $table->timestamps();

            $table->index('global_product_id');
            $table->index('action');
            $table->index('created_at');
        });

        Schema::create('product_duplicate_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id_1')->constrained('products');
            $table->foreignId('product_id_2')->constrained('products');
            $table->decimal('confidence_score', 5, 4);
            $table->string('reason'); // 'same_mpn_different_manufacturer', 'similar_name', etc.
            $table->json('evidence')->nullable();
            $table->enum('status', ['pending', 'merged', 'rejected', 'ignored'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id_1', 'product_id_2']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_duplicate_candidates');
        Schema::dropIfExists('product_version_history');
        Schema::dropIfExists('product_lifecycle_history');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_external_ids');
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('products');
    }
};
