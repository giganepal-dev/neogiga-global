<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 3: Manufacturer and Brand Master
     */
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('display_name');
            $table->string('slug')->unique();
            $table->json('aliases')->nullable(); // ['TI', 'Texas Instruments Inc.', ...]
            $table->string('official_website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'merged', 'pending_review'])->default('pending_review');
            $table->foreignId('successor_manufacturer_id')->nullable()->constrained('manufacturers')->nullOnDelete();
            $table->string('source')->nullable(); // 'mouser', 'digikey', 'admin', etc.
            $table->string('external_source_id')->nullable(); // Original ID from source
            $table->string('source_url')->nullable();
            $table->enum('authorization_status', ['authorized', 'unauthorized', 'unknown', 'restricted'])->default('unknown');
            $table->decimal('data_quality_score', 5, 2)->default(0);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->json('metadata')->nullable(); // Additional source-specific data
            $table->timestamps();
            $table->softDeletes();

            // Indexes for matching
            $table->index('slug');
            $table->index('status');
            $table->index(['legal_name', 'country_code']);
        });

        Schema::create('manufacturer_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained()->cascadeOnDelete();
            $table->string('alias');
            $table->string('source')->nullable();
            $table->decimal('confidence_score', 5, 4)->default(1.0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['manufacturer_id', 'alias']);
            $table->index('alias');
        });

        Schema::create('manufacturer_external_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained()->cascadeOnDelete();
            $table->string('source_name'); // 'mouser', 'digikey', 'arrow', etc.
            $table->string('external_id');
            $table->string('source_url')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();

            $table->unique(['source_name', 'external_id']);
            $table->index('source_name');
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('official_website')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending_review'])->default('pending_review');
            $table->decimal('data_quality_score', 5, 2)->default(0);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('manufacturer_id');
            $table->index('status');
        });

        Schema::create('brand_external_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('source_name');
            $table->string('external_id');
            $table->string('source_url')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();

            $table->unique(['source_name', 'external_id']);
        });

        Schema::create('manufacturer_source_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id');
            $table->string('record_type')->default('manufacturer');
            $table->json('raw_payload'); // Original data from source
            $table->json('parsed_data'); // Normalized data
            $table->decimal('match_confidence', 5, 4)->nullable();
            $table->enum('match_status', ['matched', 'candidate', 'unmatched', 'rejected'])->default('unmatched');
            $table->timestamp('imported_at');
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['catalog_source_id', 'external_id', 'record_type']);
            $table->index('match_status');
            $table->index('imported_at');
        });

        Schema::create('manufacturer_merge_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id_1')->constrained('manufacturers');
            $table->foreignId('manufacturer_id_2')->constrained('manufacturers');
            $table->decimal('confidence_score', 5, 4);
            $table->string('reason'); // 'same_name', 'same_domain', 'same_external_id', etc.
            $table->json('evidence')->nullable(); // Details about why they might be the same
            $table->enum('status', ['pending', 'approved', 'rejected', 'ignored'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['manufacturer_id_1', 'manufacturer_id_2']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturer_merge_candidates');
        Schema::dropIfExists('manufacturer_source_records');
        Schema::dropIfExists('brand_external_ids');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('manufacturer_external_ids');
        Schema::dropIfExists('manufacturer_aliases');
        Schema::dropIfExists('manufacturers');
    }
};
