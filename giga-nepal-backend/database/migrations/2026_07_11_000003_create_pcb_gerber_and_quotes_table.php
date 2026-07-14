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
        if (!Schema::hasTable('pcb_gerber_analysis_runs')) {
            Schema::create('pcb_gerber_analysis_runs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                
                // Relationships
                $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
                $table->foreignUuid('file_id')->nullable()->constrained('pcb_files')->nullOnDelete();
                $table->foreignId('triggered_by_id')->constrained('users');
                
                // Analysis metadata
                $table->string('parser_version')->default('1.0.0');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
                $table->text('error_message')->nullable();
                
                // Detected properties (advisory until reviewed)
                $table->decimal('detected_width_mm', 10, 4)->nullable();
                $table->decimal('detected_height_mm', 10, 4)->nullable();
                $table->unsignedInteger('detected_layer_count')->nullable();
                $table->decimal('detected_min_trace_mm', 10, 4)->nullable();
                $table->decimal('detected_min_spacing_mm', 10, 4)->nullable();
                $table->decimal('detected_min_drill_mm', 10, 4)->nullable();
                $table->unsignedInteger('detected_hole_count')->nullable();
                $table->unsignedInteger('detected_slot_count')->nullable();
                $table->decimal('detected_board_area_cm2', 10, 4)->nullable();
                $table->decimal('detected_copper_area_percent', 5, 2)->nullable();
                
                // Indicators
                $table->boolean('has_castellated_indicator')->default(false);
                $table->boolean('has_edge_plating_indicator')->default(false);
                $table->boolean('has_panelization_indicator')->default(false);
                
                // Confidence levels
                $table->enum('confidence_level', ['low', 'medium', 'high'])->default('medium');
                $table->boolean('engineering_reviewed')->default(false);
                $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                
                // Timestamps
                $table->timestamps();
                
                // Indexes
                $table->index(['project_id', 'status']);
            });
        }

        if (!Schema::hasTable('pcb_detected_layers')) {
            Schema::create('pcb_detected_layers', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('analysis_run_id')->constrained('pcb_gerber_analysis_runs')->onDelete('cascade');
                $table->string('filename');
                $table->enum('detected_type', [
                    'top_copper', 'bottom_copper', 'inner_copper',
                    'top_solder_mask', 'bottom_solder_mask',
                    'top_silkscreen', 'bottom_silkscreen',
                    'top_paste', 'bottom_paste',
                    'board_outline', 'drill', 'slot', 'mechanical', 'unknown'
                ]);
                $table->enum('expected_type', [
                    'top_copper', 'bottom_copper', 'inner_copper_1', 'inner_copper_2',
                    'top_solder_mask', 'bottom_solder_mask',
                    'top_silkscreen', 'bottom_silkscreen',
                    'top_paste', 'bottom_paste',
                    'board_outline', 'drill', 'slot', 'mechanical'
                ])->nullable();
                $table->boolean('is_matched')->default(false);
                $table->unsignedInteger('layer_order')->nullable();
                $table->json('metadata')->nullable(); // Additional parsing data
                $table->timestamps();
                
                $table->index(['analysis_run_id']);
            });
        }

        if (!Schema::hasTable('pcb_analysis_warnings')) {
            Schema::create('pcb_analysis_warnings', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('analysis_run_id')->constrained('pcb_gerber_analysis_runs')->onDelete('cascade');
                $table->enum('severity', ['info', 'warning', 'blocking', 'engineering_review']);
                $table->string('warning_code'); // MISSING_OUTLINE, LAYER_MISMATCH, etc.
                $table->text('message');
                $table->json('details')->nullable();
                $table->boolean('resolved')->default(false);
                $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();
                
                $table->index(['analysis_run_id', 'severity']);
            });
        }

        if (!Schema::hasTable('pcb_quote_configurations')) {
            Schema::create('pcb_quote_configurations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                
                // Relationships
                $table->foreignUuid('project_id')->constrained('pcb_projects')->onDelete('cascade');
                $table->foreignId('created_by_id')->constrained('users');
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                
                // Board specifications
                $table->enum('board_type', ['single_sided', 'double_sided', 'multilayer', 'rigid_flex', 'flex', 'aluminum', 'ceramic'])->default('double_sided');
                $table->unsignedInteger('designs_per_panel')->default(1);
                $table->unsignedInteger('quantity')->default(5);
                
                // Dimensions
                $table->decimal('length_mm', 10, 2)->nullable();
                $table->decimal('width_mm', 10, 2)->nullable();
                $table->decimal('thickness_mm', 6, 2)->default(1.6);
                
                // Stackup
                $table->unsignedInteger('layer_count')->default(2);
                $table->string('substrate_material')->default('FR-4');
                $table->decimal('tg_value', 5, 1)->nullable(); // Glass transition temp
                
                // Copper
                $table->string('outer_copper_oz')->default('1'); // 1oz, 2oz, etc.
                $table->string('inner_copper_oz')->default('0.5');
                $table->decimal('min_trace_mm', 8, 3)->nullable();
                $table->decimal('min_spacing_mm', 8, 3)->nullable();
                $table->decimal('min_hole_mm', 8, 3)->nullable();
                
                // Appearance
                $table->string('solder_mask_color')->default('green');
                $table->string('silkscreen_color')->default('white');
                
                // Surface finish
                $table->enum('surface_finish', ['HASL', 'HASL_Lead_Free', 'ENIG', 'OSP', 'Immersion_Silver', 'Immersion_Tin', 'Gold_Fingers'])->default('HASL_Lead_Free');
                $table->decimal('gold_thickness_um', 6, 2)->nullable();
                
                // Advanced options
                $table->boolean('impedance_control')->default(false);
                $table->json('impedance_requirements')->nullable();
                $table->enum('via_covering', ['tented', 'plugged', 'filled', 'open'])->default('tented');
                $table->boolean('blind_buried_vias')->default(false);
                $table->boolean('hdi')->default(false);
                $table->boolean('edge_plating')->default(false);
                $table->boolean('castellated_holes')->default(false);
                $table->boolean('countersink')->default(false);
                
                // Panelization
                $table->enum('panelization_type', ['none', 'v_score', 'routing', 'tab_route'])->default('none');
                
                // Testing
                $table->boolean('aoi_testing')->default(true);
                $table->boolean('electrical_test')->default(true);
                $table->enum('electrical_test_type', ['flying_probe', 'fixture'])->default('flying_probe');
                
                // Marking & Packaging
                $table->boolean('ul_date_marking')->default(false);
                $table->boolean('customer_marking')->default(false);
                $table->string('packaging_type')->default('standard');
                
                // Lead time
                $table->enum('production_speed', ['standard', 'fast', 'express'])->default('standard');
                $table->unsignedInteger('lead_time_days')->default(7);
                
                // Status
                $table->enum('status', ['draft', 'submitted', 'quoted', 'approved', 'rejected'])->default('draft');
                
                // Pricing (manual quote initially)
                $table->decimal('setup_charge', 15, 2)->default(0);
                $table->decimal('engineering_charge', 15, 2)->default(0);
                $table->decimal('fabrication_unit_price', 15, 2)->nullable();
                $table->decimal('total_fabrication_price', 15, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->boolean('requires_engineering_quote')->default(true);
                $table->text('engineering_notes')->nullable();
                
                // Timestamps
                $table->timestamps();
                
                // Indexes
                $table->index(['project_id', 'status']);
            });
        }

        if (!Schema::hasTable('pcb_quote_line_items')) {
            Schema::create('pcb_quote_line_items', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('quote_id')->constrained('pcb_quote_configurations')->onDelete('cascade');
                $table->string('item_type'); // fabrication, assembly, stencil, testing, engineering, freight
                $table->string('description');
                $table->decimal('unit_price', 15, 2);
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('total_price', 15, 2);
                $table->string('currency', 3)->default('USD');
                $table->boolean('is_optional')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['quote_id', 'item_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_quote_line_items');
        Schema::dropIfExists('pcb_quote_configurations');
        Schema::dropIfExists('pcb_analysis_warnings');
        Schema::dropIfExists('pcb_detected_layers');
        Schema::dropIfExists('pcb_gerber_analysis_runs');
    }
};
