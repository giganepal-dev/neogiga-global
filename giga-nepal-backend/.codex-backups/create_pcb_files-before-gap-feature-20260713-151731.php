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
                
                // Relationships
                $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
                $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
                $table->foreignUuid('version_id')->nullable()->constrained('pcb_project_versions')->nullOnDelete();
                
                // File identity
                $table->string('filename_original');
                $table->string('filename_stored'); // Sanitized unique name
                $table->string('file_type'); // gerber, schematic, bom, cpl, drill, etc.
                $table->string('mime_type');
                $table->unsignedBigInteger('file_size'); // bytes
                
                // Classification
                $table->enum('layer_type', [
                    'top_copper', 'bottom_copper', 'inner_copper_1', 'inner_copper_2',
                    'top_solder_mask', 'bottom_solder_mask',
                    'top_silkscreen', 'bottom_silkscreen',
                    'top_paste', 'bottom_paste',
                    'board_outline', 'drill', 'slot', 'mechanical',
                    'schematic', 'pcb_source', 'bom', 'cpl',
                    'assembly_drawing', 'fab_drawing', 'step', 'dxf',
                    'ipc356', 'test_file', 'firmware', 'certificate', 'quality_report',
                    'other'
                ])->nullable();
                
                // Storage
                $table->string('storage_disk'); // private, s3-private
                $table->string('storage_path');
                $table->string('encryption_key_ref')->nullable(); // Reference to encryption key
                
                // Security & Validation
                $table->boolean('malware_scanned')->default(false);
                $table->boolean('malware_clean')->default(true);
                $table->timestamp('scanned_at')->nullable();
                $table->boolean('signature_validated')->default(false);
                $table->boolean('mime_validated')->default(false);
                
                // Processing status
                $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
                $table->text('processing_error')->nullable();
                
                // Access control
                $table->boolean('nda_required')->default(false);
                $table->json('access_permissions')->nullable(); // Custom access rules
                
                // Metadata
                $table->json('metadata')->nullable(); // Layer detection results, etc.
                
                // Timestamps
                $table->timestamps();
                $table->softDeletes();
                
                // Indexes
                $table->index(['project_id', 'file_type']);
                $table->index(['filename_stored']);
            });
        }

        if (!Schema::hasTable('pcb_file_versions')) {
            Schema::create('pcb_file_versions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('file_id')->constrained('pcb_files')->onDelete('cascade');
                $table->unsignedInteger('version_number');
                $table->string('filename_original');
                $table->string('filename_stored');
                $table->string('storage_path');
                $table->unsignedBigInteger('file_size');
                $table->text('change_summary')->nullable();
                $table->foreignUuid('uploaded_by_id')->constrained('users');
                $table->timestamps();
                
                $table->index(['file_id', 'version_number']);
            });
        }

        if (!Schema::hasTable('pcb_file_access_logs')) {
            Schema::create('pcb_file_access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('file_id')->constrained('pcb_files')->onDelete('cascade');
                $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action'); // view, download, share, expire
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('reason')->nullable(); // Why was this accessed?
                $table->timestamps();
                
                $table->index(['file_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('pcb_file_shares')) {
            Schema::create('pcb_file_shares', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('file_id')->constrained('pcb_files')->onDelete('cascade');
                $table->foreignUuid('shared_by_id')->constrained('users');
                $table->foreignUuid('shared_with_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('shared_with_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
                $table->enum('share_type', ['user', 'organization', 'supplier']);
                $table->timestamp('expires_at')->nullable();
                $table->boolean('requires_ndas')->default(true);
                $table->boolean('nda_accepted')->default(false);
                $table->timestamp('nda_accepted_at')->nullable();
                $table->integer('download_count')->default(0);
                $table->integer('max_downloads')->nullable();
                $table->timestamps();
                
                $table->index(['file_id', 'expires_at']);
            });
        }

        if (!Schema::hasTable('pcb_file_scan_results')) {
            Schema::create('pcb_file_scan_results', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('file_id')->constrained('pcb_files')->onDelete('cascade');
                $table->string('scanner_name'); // clamav, custom, etc.
                $table->string('scanner_version')->nullable();
                $table->boolean('is_clean')->default(true);
                $table->text('threat_name')->nullable();
                $table->json('scan_details')->nullable();
                $table->unsignedInteger('scan_duration_ms');
                $table->timestamps();
                
                $table->index(['file_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_file_scan_results');
        Schema::dropIfExists('pcb_file_shares');
        Schema::dropIfExists('pcb_file_access_logs');
        Schema::dropIfExists('pcb_file_versions');
        Schema::dropIfExists('pcb_files');
    }
};
