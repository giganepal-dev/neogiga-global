<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 3: Manufacturer Master - staged_manufacturers table
     */
    public function up(): void
    {
        Schema::create('staged_manufacturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->string('external_source_id')->nullable();
            $table->string('source_url')->nullable();
            
            // Core fields
            $table->string('legal_name')->nullable();
            $table->string('display_name');
            $table->string('slug')->nullable();
            $table->json('aliases')->nullable();
            $table->string('official_website')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('country', 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'merged', 'unknown'])->default('unknown');
            
            // Matching and merge
            $table->foreignId('existing_manufacturer_id')->nullable()->comment('matched existing manufacturer');
            $table->foreignId('successor_manufacturer_id')->nullable()->comment('if merged, points to successor');
            $table->float('match_confidence', 5, 4)->nullable(); // 0.0000 to 1.0000
            
            // Data quality
            $table->enum('authorization_status', ['authorized', 'unauthorized', 'unknown'])->default('unknown');
            $table->unsignedTinyInteger('data_quality_score')->default(0);
            
            // Review workflow
            $table->enum('review_status', ['pending', 'approved', 'rejected', 'needs_review'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            // Publishing
            $table->boolean('ready_to_publish')->default(false);
            $table->timestamp('published_at')->nullable();
            
            $table->json('original_payload')->nullable(); // full source record
            $table->timestamps();
            
            $table->index(['import_batch_id', 'review_status']);
            $table->index('external_source_id');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staged_manufacturers');
    }
};
