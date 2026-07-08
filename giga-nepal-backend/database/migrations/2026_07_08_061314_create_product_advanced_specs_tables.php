<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Category-specific specification templates
        Schema::create('category_spec_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "Battery Specifications", "Solar Panel Specs"
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable(); // Additional template config
            $table->timestamps();
            
            $table->index(['category_id', 'sort_order']);
        });

        // Individual spec fields within templates
        Schema::create('spec_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('category_spec_templates')->cascadeOnDelete();
            $table->string('field_name'); // e.g., "voltage", "capacity", "wattage"
            $table->string('field_label'); // e.g., "Voltage", "Battery Capacity", "Power Output"
            $table->string('field_type')->default('text'); // text, number, select, boolean, range
            $table->string('unit')->nullable(); // e.g., "V", "mAh", "W", "kg"
            $table->json('options')->nullable(); // For select type: ["Option1", "Option2"]
            $table->string('validation_rules')->nullable(); // e.g., "required|min:1|max:100"
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['template_id', 'sort_order']);
        });

        // Product specifications (actual values stored per product)
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_field_id')->constrained('spec_template_fields')->cascadeOnDelete();
            $table->text('value'); // The actual spec value
            $table->string('unit_override')->nullable(); // Override template unit if needed
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            
            $table->unique(['product_id', 'template_field_id']);
            $table->index(['product_id']);
        });

        // Specification groups for organizing specs on PDP
        Schema::create('specification_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "General", "Technical", "Physical", "Warranty"
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_expanded')->default(true); // Show expanded by default?
            $table->timestamps();
            
            $table->index(['category_id', 'sort_order']);
        });

        // Link spec template fields to groups
        Schema::create('specification_group_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('specification_groups')->cascadeOnDelete();
            $table->foreignId('template_field_id')->constrained('spec_template_fields')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['group_id', 'template_field_id']);
        });

        // Product datasheets and documents
        Schema::create('product_datasheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type'); // pdf, doc, xls, etc.
            $table->integer('file_size'); // in KB
            $table->string('document_type')->default('datasheet'); // datasheet, manual, certificate, warranty_card
            $table->text('description')->nullable();
            $table->string('language')->default('en');
            $table->boolean('is_public')->default(true);
            $table->integer('download_count')->default(0);
            $table->timestamps();
            
            $table->index(['product_id', 'document_type']);
        });

        // Product certificates and compliance
        Schema::create('product_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_name'); // e.g., "CE", "FCC", "RoHS", "ISO 9001"
            $table->string('certificate_number')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            
            $table->index(['product_id', 'certificate_name']);
        });

        // Country of origin tracking
        Schema::create('product_countries_of_origin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('origin_type')->default('manufactured'); // manufactured, assembled, designed
            $table->text('manufacturer_details')->nullable();
            $table->string('manufacturer_name')->nullable();
            $table->string('manufacturer_address')->nullable();
            $table->string('importer_name')->nullable();
            $table->string('importer_address')->nullable();
            $table->string('hs_code')->nullable(); // Harmonized System code
            $table->timestamps();
            
            $table->index(['product_id']);
        });

        // Warranty information
        Schema::create('product_warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('warranty_type')->default('manufacturer'); // manufacturer, seller, none
            $table->integer('warranty_period_months')->default(0);
            $table->string('warranty_coverage')->nullable(); // What's covered
            $table->text('warranty_terms')->nullable(); // Detailed terms
            $table->text('warranty_exclusions')->nullable(); // What's not covered
            $table->string('warranty_contact')->nullable(); // Contact for warranty claims
            $table->string('warranty_email')->nullable();
            $table->string('warranty_phone')->nullable();
            $table->boolean('is_international')->default(false);
            $table->text('additional_info')->nullable();
            $table->timestamps();
            
            $table->index(['product_id']);
        });

        // Generic suggestions for products (AI/manual suggestions)
        Schema::create('product_generic_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('suggestion_type'); // alternative, upgrade, accessory, compatible
            $table->foreignId('suggested_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('reason')->nullable(); // Why this suggestion
            $table->decimal('confidence_score', 5, 4)->default(0.5000); // 0.0000 to 1.0000
            $table->json('metadata')->nullable(); // Additional context
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            $table->index(['product_id', 'suggestion_type']);
            $table->index(['suggested_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_generic_suggestions');
        Schema::dropIfExists('product_warranties');
        Schema::dropIfExists('product_countries_of_origin');
        Schema::dropIfExists('product_certificates');
        Schema::dropIfExists('product_datasheets');
        Schema::dropIfExists('specification_group_fields');
        Schema::dropIfExists('specification_groups');
        Schema::dropIfExists('product_specifications');
        Schema::dropIfExists('spec_template_fields');
        Schema::dropIfExists('category_spec_templates');
    }
};
