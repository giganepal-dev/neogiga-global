<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 7: Data Staging - import_rows table
     * Individual rows from import files before processing
     */
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_file_id')->constrained('import_files')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw_data'); // original row data as associative array
            $table->enum('status', ['pending', 'validated', 'staged', 'error', 'skipped'])->default('pending');
            $table->text('validation_errors')->nullable();
            $table->foreignId('staged_record_id')->nullable()->comment('polymorphic reference to staged table');
            $table->string('staged_table')->nullable(); // staged_products, staged_manufacturers, etc.
            $table->timestamps();
            
            $table->index(['import_file_id', 'status']);
            $table->index('row_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
