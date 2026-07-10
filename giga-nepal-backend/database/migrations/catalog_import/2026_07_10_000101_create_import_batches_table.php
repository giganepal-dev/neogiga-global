<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 7: Data Staging - import_batches table
     */
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
            $table->foreignId('catalog_source_run_id')->nullable()->constrained('catalog_source_runs')->nullOnDelete();
            $table->string('batch_type'); // csv, xml, api, manual
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('source_identifier')->nullable(); // file name, API query ID, etc.
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->text('options')->nullable(); // JSON configuration for this batch
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
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
        Schema::dropIfExists('import_batches');
    }
};
