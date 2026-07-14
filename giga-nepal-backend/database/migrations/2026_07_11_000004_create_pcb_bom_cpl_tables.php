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
        // Extend existing BOM tables with PCB-specific fields if they exist
        // Otherwise create minimal CPL tables
        
        if (!Schema::hasTable('pcb_cpl_imports')) {
            Schema::create('pcb_cpl_imports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('filename_original');
                $table->string('filename_stored');
                $table->unsignedBigInteger('file_size');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
                $table->text('error_message')->nullable();
                $table->unsignedInteger('total_lines')->default(0);
                $table->unsignedInteger('valid_lines')->default(0);
                $table->unsignedInteger('error_lines')->default(0);
                $table->timestamps();
                
                $table->index(['project_id', 'status']);
            });
        }

        if (!Schema::hasTable('pcb_cpl_lines')) {
            Schema::create('pcb_cpl_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('cpl_import_id')->constrained('pcb_cpl_imports')->onDelete('cascade');
                $table->unsignedInteger('line_number');
                
                // Component identification
                $table->string('reference_designator'); // R1, C5, U3, etc.
                $table->string('comment')->nullable(); // Value or description
                $table->string('footprint')->nullable();
                $table->string('package')->nullable();
                
                // Placement data
                $table->decimal('x_mm', 10, 4)->nullable();
                $table->decimal('y_mm', 10, 4)->nullable();
                $table->decimal('rotation_deg', 6, 2)->nullable();
                $table->enum('side', ['top', 'bottom'])->default('top');
                
                // Status
                $table->boolean('is_dnp')->default(false); // Do Not Populate
                
                // Validation
                $table->boolean('bom_matched')->default(false);
                $table->foreignUuid('matched_bom_line_id')->nullable();
                $table->foreignId('matched_product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->boolean('placement_validated')->default(false);
                
                // Errors
                $table->json('validation_errors')->nullable();
                
                $table->timestamps();
                
                $table->index(['cpl_import_id', 'reference_designator']);
            });
        }

        if (!Schema::hasTable('pcb_cpl_validation_errors')) {
            Schema::create('pcb_cpl_validation_errors', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('cpl_import_id')->constrained('pcb_cpl_imports')->onDelete('cascade');
                $table->foreignId('cpl_line_id')->nullable()->constrained('pcb_cpl_lines')->nullOnDelete();
                $table->unsignedInteger('line_number');
                $table->string('error_code'); // DUPLICATE_DESIGNATOR, MISSING_BOM, INVALID_COORDS, etc.
                $table->text('error_message');
                $table->json('error_details')->nullable();
                $table->boolean('resolved')->default(false);
                $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();
                
                $table->index(['cpl_import_id', 'resolved']);
            });
        }

        // Component matching tables
        if (!Schema::hasTable('pcb_component_matches')) {
            Schema::create('pcb_component_matches', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
                $table->foreignUuid('bom_line_id')->nullable(); // Reference to existing BOM line if applicable
                $table->string('requested_mpn')->nullable();
                $table->string('requested_manufacturer')->nullable();
                $table->string('requested_description')->nullable();
                $table->string('requested_package')->nullable();
                
                // Match result
                $table->foreignId('matched_product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('matched_mpn')->nullable();
                $table->string('matched_manufacturer')->nullable();
                $table->enum('match_confidence', ['exact', 'high', 'medium', 'low', 'no_match'])->default('no_match');
                $table->string('match_reason')->nullable();
                
                // Approval status
                $table->boolean('customer_approved')->default(false);
                $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->boolean('engineer_approved')->default(false);
                $table->foreignId('engineer_approved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('engineer_approved_at')->nullable();
                
                // Alternatives
                $table->boolean('alternative_allowed')->default(true);
                $table->json('alternative_candidates')->nullable();
                
                $table->timestamps();
                
                $table->index(['project_id', 'match_confidence']);
            });
        }

        if (!Schema::hasTable('pcb_component_substitutions')) {
            Schema::create('pcb_component_substitutions', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('component_match_id')->constrained('pcb_component_matches')->onDelete('cascade');
                $table->foreignId('original_product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('substitute_product_id')->constrained('products');
                $table->enum('substitution_type', ['manufacturer_alternate', 'functional_equivalent', 'upgrade', 'cost_reduction']);
                $table->text('justification');
                $table->boolean('requires_approval')->default(true);
                $table->boolean('approved')->default(false);
                $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                
                $table->index(['component_match_id', 'approved']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_component_substitutions');
        Schema::dropIfExists('pcb_component_matches');
        Schema::dropIfExists('pcb_cpl_validation_errors');
        Schema::dropIfExists('pcb_cpl_lines');
        Schema::dropIfExists('pcb_cpl_imports');
    }
};
