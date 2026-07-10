<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 7: Data Staging - import_row_errors table
     * Detailed error tracking for failed import rows
     */
    public function up(): void
    {
        Schema::create('import_row_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_row_id')->constrained('import_rows')->cascadeOnDelete();
            $table->string('error_code'); // e.g., MISSING_MPN, INVALID_MANUFACTURER, DUPLICATE
            $table->text('error_message');
            $table->string('field_name')->nullable();
            $table->text('field_value')->nullable();
            $table->json('context')->nullable();
            $table->boolean('resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            
            $table->index(['import_row_id', 'resolved']);
            $table->index('error_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_row_errors');
    }
};
