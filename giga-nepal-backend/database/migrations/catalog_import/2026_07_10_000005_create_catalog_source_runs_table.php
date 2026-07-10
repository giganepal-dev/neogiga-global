<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 2: Source Management - catalog_source_runs table
     * Tracks each import execution
     */
    public function up(): void
    {
        Schema::create('catalog_source_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('run_type'); // full, incremental, refresh, on_demand
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled', 'partial'])->default('pending');
            $table->string('triggered_by')->nullable(); // manual, scheduled, webhook, api
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('total_records')->default(0);
            $table->unsignedBigInteger('processed_records')->default(0);
            $table->unsignedBigInteger('success_records')->default(0);
            $table->unsignedBigInteger('error_records')->default(0);
            $table->unsignedBigInteger('skipped_records')->default(0);
            $table->unsignedBigInteger('staged_records')->default(0);
            $table->unsignedBigInteger('published_records')->default(0);
            $table->json('filters_applied')->nullable(); // filters used for this run
            $table->text('error_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->float('duration_seconds', 10, 2)->nullable();
            $table->timestamps();
            
            $table->index(['catalog_source_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_source_runs');
    }
};
