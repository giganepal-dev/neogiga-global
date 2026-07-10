<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 2: Source Management - catalog_source_rate_limits table
     * Tracks API rate limit usage per source
     */
    public function up(): void
    {
        Schema::create('catalog_source_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('endpoint_pattern')->nullable(); // e.g., "/api/search/*" or null for global
            $table->unsignedInteger('limit_per_minute')->nullable();
            $table->unsignedInteger('limit_per_hour')->nullable();
            $table->unsignedInteger('limit_per_day')->nullable();
            $table->unsignedInteger('current_minute_count')->default(0);
            $table->unsignedInteger('current_hour_count')->default(0);
            $table->unsignedInteger('current_day_count')->default(0);
            $table->timestamp('minute_reset_at')->nullable();
            $table->timestamp('hour_reset_at')->nullable();
            $table->timestamp('day_reset_at')->nullable();
            $table->boolean('is_throttled')->default(false);
            $table->timestamp('throttle_until')->nullable();
            $table->timestamps();
            
            $table->unique(['catalog_source_id', 'endpoint_pattern']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_source_rate_limits');
    }
};
