<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
                $table->uuid('user_id');
                $table->uuid('organization_id')->nullable();
                $table->string('marketplace', 10)->default('global');
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->string('application_type')->nullable(); // IoT, Automotive, Industrial, etc.
                $table->enum('confidentiality', ['public', 'internal', 'confidential'])->default('internal');
                $table->enum('project_type', ['prototype', 'production'])->default('prototype');
                $table->integer('target_quantity')->default(1);
                $table->decimal('target_budget', 15, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->date('required_date')->nullable();
                $table->string('destination_country', 2)->nullable();
                $table->string('shipping_postal_code', 20)->nullable();
                $table->string('preferred_region', 50)->nullable();
                $table->uuid('preferred_manufacturer_id')->nullable();
                $table->uuid('preferred_warehouse_id')->nullable();
                $table->uuid('assigned_engineer_id')->nullable();
                $table->enum('status', [
                    'draft',
                    'requirements_pending',
                    'design_requested',
                    'design_in_progress',
                    'design_review',
                    'design_approved',
                    'files_ready',
                    'quote_pending',
                    'quoted',
                    'awaiting_approval',
                    'ordered',
                    'manufacturing',
                    'inspection',
                    'shipped',
                    'completed',
                    'on_hold',
                    'cancelled'
                ])->default('draft');
                $table->integer('current_version')->default(1);
                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index('user_id');
                $table->index('organization_id');
                $table->index('marketplace');
                $table->index('status');
                $table->index('code');
                $table->index(['organization_id', 'status']);
                $table->index(['user_id', 'status']);
                
                // Foreign keys (will be added after verifying tables exist)
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                if (Schema::hasTable('organizations')) {
                    $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
                }
            });

            // Add comment
            DB::statement("COMMENT ON TABLE pcb_projects IS 'PCB project workspace for managing design, fabrication, and assembly projects'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_projects');
    }
};
