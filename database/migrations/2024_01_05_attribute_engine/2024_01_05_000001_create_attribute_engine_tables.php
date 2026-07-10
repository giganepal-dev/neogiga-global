<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 5: Attribute and Specification Engine
     */
    public function up(): void
    {
        Schema::create('attribute_units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 'V', 'A', 'Ω', '°C', etc.
            $table->string('name'); // 'Volt', 'Ampere', 'Ohm', etc.
            $table->string('symbol')->nullable(); // 'V', 'A', 'Ω', etc.
            $table->string('unit_family')->nullable(); // 'voltage', 'current', 'resistance', 'temperature'
            $table->boolean('is_base_unit')->default(false);
            $table->decimal('conversion_factor', 20, 10)->default(1); // Factor to convert to base unit
            $table->decimal('conversion_offset', 20, 10)->default(0); // For temperature conversions
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('unit_family');
        });

        Schema::create('attribute_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_unit_id')->constrained('attribute_units')->cascadeOnDelete();
            $table->foreignId('to_unit_id')->constrained('attribute_units')->cascadeOnDelete();
            $table->decimal('factor', 20, 10);
            $table->decimal('offset', 20, 10)->default(0);
            $table->string('formula')->nullable(); // Human-readable formula
            $table->boolean('is_exact')->default(true);
            $table->timestamps();

            $table->unique(['from_unit_id', 'to_unit_id']);
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // snake_case: 'supply_voltage_min'
            $table->string('name'); // 'Supply Voltage (Min)'
            $table->string('description')->nullable();
            $table->enum('data_type', [
                'string',
                'integer',
                'decimal',
                'boolean',
                'option',
                'multi_option',
                'range',
                'date',
                'datetime'
            ])->default('string');
            
            // Unit handling
            $table->foreignId('default_unit_id')->nullable()->constrained('attribute_units')->nullOnDelete();
            $table->string('unit_family')->nullable(); // For grouping similar units
            
            // Behavior flags
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_comparable')->default(true);
            $table->boolean('is_searchable')->default(true);
            $table->boolean('is_required')->default(false);
            $table->boolean('visible_on_product_page')->default(true);
            $table->boolean('allow_range_values')->default(false); // e.g., "5-12V"
            
            // Display
            $table->integer('sort_order')->default(0);
            $table->string('display_group')->nullable(); // Group on product page
            $table->json('validation_rules')->nullable(); // Laravel validation rules
            $table->json('extra_config')->nullable(); // Additional configuration
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('data_type');
            $table->index('unit_family');
            $table->index('is_filterable');
        });

        Schema::create('attribute_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 'Electrical', 'Mechanical', 'Environmental'
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('attribute_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['attribute_group_id', 'attribute_id']);
        });

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value'); // The actual option value
            $table->string('label')->nullable(); // Display label if different
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['attribute_id', 'value']);
            $table->index('attribute_id');
        });

        Schema::create('external_attribute_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->string('external_attribute_name'); // Name from source
            $table->string('external_attribute_code')->nullable(); // Code from source
            $table->string('source_unit_code')->nullable(); // Unit used by source
            $table->json('mapping_metadata')->nullable();
            $table->boolean('requires_unit_conversion')->default(false);
            $table->timestamps();

            $table->unique(['catalog_source_id', 'external_attribute_name']);
            $table->index('external_attribute_name');
        });

        Schema::create('attribute_mapping_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_attribute_name');
            $table->string('external_attribute_sample_value')->nullable();
            $table->array('suggested_attribute_ids')->nullable(); // Array of potential matches
            $table->decimal('best_match_confidence', 5, 4)->nullable();
            $table->enum('status', ['pending', 'mapped', 'created_new', 'ignored', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('imported_at');
            $table->timestamps();

            $table->unique(['catalog_source_id', 'external_attribute_name']);
            $table->index('status');
        });

        Schema::create('specification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('template_schema'); // JSON schema defining required/optional attributes
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specification_templates');
        Schema::dropIfExists('attribute_mapping_candidates');
        Schema::dropIfExists('external_attribute_mappings');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attribute_group_members');
        Schema::dropIfExists('attribute_groups');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('attribute_unit_conversions');
        Schema::dropIfExists('attribute_units');
    }
};
