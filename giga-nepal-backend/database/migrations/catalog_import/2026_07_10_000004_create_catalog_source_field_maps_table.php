<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 2: Source Management - catalog_source_field_maps table
     * Maps external source fields to NeoGiga canonical fields
     */
    public function up(): void
    {
        Schema::create('catalog_source_field_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('data_type'); // manufacturer, product, category, attribute, price, inventory
            $table->string('external_field_name'); // field name from source
            $table->string('canonical_field_name'); // NeoGiga standard field name
            $table->enum('mapping_type', ['direct', 'transform', 'lookup', 'constant', 'concatenate', 'split'])->default('direct');
            $table->text('transform_expression')->nullable(); // PHP expression or JSON transformation rules
            $table->json('lookup_map')->nullable(); // {"External Value": "Canonical Value"}
            $table->boolean('required')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedTinyInteger('priority')->default(10);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['catalog_source_id', 'data_type', 'external_field_name']);
            $table->index(['catalog_source_id', 'data_type', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_source_field_maps');
    }
};
