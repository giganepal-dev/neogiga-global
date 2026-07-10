<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 2: Source Management - catalog_source_licenses table
     */
    public function up(): void
    {
        Schema::create('catalog_source_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('license_type'); // commercial, free, trial, partner, oem
            $table->string('license_key')->nullable();
            $table->text('terms_url')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->unsignedBigInteger('max_products')->nullable();
            $table->unsignedBigInteger('max_requests_per_day')->nullable();
            $table->json('allowed_regions')->nullable(); // ["US", "EU", "NP"]
            $table->json('allowed_data_fields')->nullable(); // fields we're licensed to store
            $table->boolean('allows_caching')->default(false);
            $table->boolean('allows_redistribution')->default(false);
            $table->text('attribution_text')->nullable();
            $table->enum('status', ['active', 'expired', 'suspended', 'pending'])->default('pending');
            $table->timestamps();
            
            $table->index(['catalog_source_id', 'status']);
            $table->index('valid_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_source_licenses');
    }
};
