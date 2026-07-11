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
        if (!Schema::hasTable('pcb_projects')) {
            Schema::create('pcb_projects', function (Blueprint $table) {
                $table->uuid('id')->primary();
                
                // Relationships
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->string('marketplace')->default('global'); // en, np, in, etc.
                
                // Identity
                $table->string('name');
                $table->string('code')->unique(); // Auto-generated PCB-XXXXXX
                $table->text('description')->nullable();
                $table->string('application_type')->nullable(); // IoT, Automotive, Industrial, etc.
                
                // Classification
                $table->enum('confidentiality', ['public', 'internal', 'confidential', 'nda_required'])->default('internal');
                $table->enum('project_type', ['prototype', 'production'])->default('prototype');
                
                // Targets
                $table->unsignedInteger('target_quantity')->default(5);
                $table->decimal('target_budget', 15, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->date('required_date')->nullable();
                
                // Logistics
                $table->string('destination_country')->nullable();
                $table->string('shipping_postal_code')->nullable();
                $table->string('preferred_region')->nullable(); // Asia, Europe, NA
                $table->unsignedBigInteger('preferred_manufacturer_id')->nullable()->index();
                $table->foreignId('preferred_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                
                // Assignment
                $table->foreignId('assigned_engineer_id')->nullable()->constrained('users')->nullOnDelete();
                
                // Status
                $table->enum('status', [
                    'draft', 'requirements_pending', 'design_requested', 'design_in_progress',
                    'design_review', 'design_approved', 'files_ready', 'quote_pending',
                    'quoted', 'awaiting_approval', 'ordered', 'manufacturing',
                    'inspection', 'shipped', 'completed', 'on_hold', 'cancelled'
                ])->default('draft');
                
                // Versioning
                $table->unsignedInteger('current_version')->default(1);
                
                // Timestamps
                $table->timestamps();
                $table->softDeletes();
                
                // Indexes
                $table->index(['user_id', 'status']);
                $table->index(['organization_id', 'status']);
                $table->index(['code']);
                $table->index(['marketplace', 'status']);
            });
        }

        if (!Schema::hasTable('pcb_project_members')) {
            Schema::create('pcb_project_members', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('role', ['owner', 'admin', 'editor', 'viewer', 'supplier', 'engineer']);
                $table->timestamp('access_expires_at')->nullable();
                $table->boolean('nda_accepted')->default(false);
                $table->timestamp('nda_accepted_at')->nullable();
                $table->timestamps();
                
                $table->unique(['project_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('pcb_project_versions')) {
            Schema::create('pcb_project_versions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
                $table->unsignedInteger('version_number');
                $table->text('change_summary')->nullable();
                $table->foreignId('created_by_id')->constrained('users');
                $table->json('snapshot_data')->nullable(); // Snapshot of config at this version
                $table->timestamps();
                
                $table->index(['project_id', 'version_number']);
            });
        }
        
        if (!Schema::hasTable('pcb_project_activity_logs')) {
            Schema::create('pcb_project_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action'); // file_uploaded, quote_requested, status_changed
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                
                $table->index(['project_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_project_activity_logs');
        Schema::dropIfExists('pcb_project_versions');
        Schema::dropIfExists('pcb_project_members');
        Schema::dropIfExists('pcb_projects');
    }
};
