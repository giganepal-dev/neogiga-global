<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 7: Data Staging - import_files table
     * Stores metadata about uploaded/imported files
     */
    public function up(): void
    {
        Schema::create('import_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_filename'); // sanitized filename on disk
            $table->string('file_path'); // relative path in storage
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('mime_type');
            $table->string('file_encoding')->default('UTF-8');
            $table->string('delimiter')->default(','); // for CSV
            $table->integer('header_row')->default(1);
            $table->string('checksum_sha256');
            $table->json('detected_columns')->nullable();
            $table->boolean('validated')->default(false);
            $table->text('validation_errors')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('import_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_files');
    }
};
