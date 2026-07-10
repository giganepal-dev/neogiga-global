<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 2: Source Management - catalog_source_run_logs table
     * Detailed logs for each import run
     */
    public function up(): void
    {
        Schema::create('catalog_source_run_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_source_run_id')->constrained('catalog_source_runs')->cascadeOnDelete();
            $table->enum('level', ['info', 'warning', 'error', 'critical'])->default('info');
            $table->string('stage')->nullable(); // fetch, parse, validate, stage, transform, publish
            $table->unsignedBigInteger('record_number')->nullable();
            $table->string('external_id')->nullable(); // ID from source system
            $table->text('message');
            $table->json('context')->nullable(); // additional structured data
            $table->text('stack_trace')->nullable();
            $table->timestamps();
            
            $table->index(['catalog_source_run_id', 'level']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_source_run_logs');
    }
};
