<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 4: Category Taxonomy
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('path')->nullable(); // Materialized path: /1/5/23/
            $table->integer('depth')->default(0);
            $table->integer('position')->default(0);
            
            // SEO fields
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            
            // Display
            $table->string('icon_path')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_visible')->default(true);
            
            // Marketplace visibility (JSON: ['us', 'eu', 'asia'])
            $table->json('marketplace_visibility')->nullable();
            
            // LMS topic mapping for AI/RAG
            $table->string('lms_topic_id')->nullable();
            
            $table->enum('status', ['active', 'inactive', 'pending_review', 'merged'])->default('pending_review');
            $table->decimal('data_quality_score', 5, 2)->default(0);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['parent_id', 'position']);
            $table->index('path');
            $table->index('depth');
            $table->index('slug');
            $table->index('status');
            $table->index('is_visible');
        });

        Schema::create('category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->string('slug');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->unique(['category_id', 'locale']);
            $table->index('locale');
        });

        Schema::create('category_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('alias');
            $table->string('source')->nullable(); // 'mouser', 'digikey', etc.
            $table->decimal('confidence_score', 5, 4)->default(1.0);
            $table->timestamps();

            $table->unique(['category_id', 'alias']);
            $table->index('alias');
        });

        Schema::create('category_external_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->string('external_category_id');
            $table->string('external_category_name');
            $table->string('external_category_path')->nullable(); // Full path from source
            $table->json('mapping_metadata')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['catalog_source_id', 'external_category_id']);
            $table->index('external_category_name');
        });

        Schema::create('category_attribute_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->integer('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['category_id', 'slug']);
            $table->index('category_id');
        });

        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_group_id')->nullable()->constrained('category_attribute_groups')->nullOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_comparable')->default(true);
            $table->boolean('is_searchable')->default(true);
            $table->boolean('visible_on_product_page')->default(true);
            $table->integer('display_position')->default(0);
            $table->string('display_label')->nullable(); // Override default attribute name
            $table->json('validation_rules')->nullable(); // Custom validation for this category
            $table->timestamps();

            $table->unique(['category_id', 'attribute_id']);
            $table->index('category_id');
            $table->index('attribute_id');
        });

        Schema::create('category_import_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_category_id');
            $table->string('external_category_name');
            $table->string('external_category_path')->nullable();
            $table->json('suggested_mappings')->nullable(); // Array of potential NeoGiga categories
            $table->decimal('best_match_confidence', 5, 4)->nullable();
            $table->enum('status', ['pending', 'mapped', 'created_new', 'ignored', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('imported_at');
            $table->timestamps();

            $table->unique(['catalog_source_id', 'external_category_id']);
            $table->index('status');
            $table->index('imported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_import_candidates');
        Schema::dropIfExists('category_attributes');
        Schema::dropIfExists('category_attribute_groups');
        Schema::dropIfExists('category_external_mappings');
        Schema::dropIfExists('category_aliases');
        Schema::dropIfExists('category_translations');
        Schema::dropIfExists('categories');
    }
};
