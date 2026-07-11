<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pcb_files')) {
            Schema::create('pcb_files', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('project_id');
                $table->uuid('version_id')->nullable();
                $table->uuid('uploaded_by_id');
                $table->string('file_type'); // gerber, schematic, bom, cpl, drill, step, dxf, etc.
                $table->string('original_filename');
                $table->string('stored_filename'); // Sanitized unique filename
                $table->string('file_path'); // Relative path in private storage
                $table->string('mime_type');
                $table->bigInteger('file_size'); // in bytes
                $table->string('checksum_sha256');
                $table->json('metadata')->nullable(); // Layer info, dimensions, etc.
                $table->enum('status', ['pending_scan', 'scanned', 'infected', 'invalid', 'processed'])->default('pending_scan');
                $table->text('scan_result')->nullable();
                $table->integer('download_count')->default(0);
                $table->timestamp('last_downloaded_at')->nullable();
                $table->timestamp('expires_at')->nullable(); // For temporary supplier access
                $table->boolean('is_encrypted')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index('project_id');
                $table->index('version_id');
                $table->index('uploaded_by_id');
                $table->index('file_type');
                $table->index('status');

                $table->foreign('project_id')->references('id')->on('pcb_projects')->onDelete('cascade');
                $table->foreign('version_id')->references('id')->on('pcb_project_versions')->onDelete('set null');
                $table->foreign('uploaded_by_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_files');
    }
};
