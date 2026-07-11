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
        if (!Schema::hasTable('pcb_detected_dimensions')) {
            Schema::create('pcb_detected_dimensions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('analysis_run_id');
                $table->decimal('width_mm', 10, 4)->nullable();
                $table->decimal('height_mm', 10, 4)->nullable();
                $table->decimal('area_mm2', 12, 4)->nullable();
                $table->integer('detected_layers_count')->nullable();
                $table->decimal('minimum_track_mm', 8, 4)->nullable();
                $table->decimal('minimum_spacing_mm', 8, 4)->nullable();
                $table->decimal('minimum_drill_mm', 8, 4)->nullable();
                $table->integer('hole_count')->nullable();
                $table->integer('slot_count')->nullable();
                $table->boolean('has castellated_holes')->default(false);
                $table->boolean('has_edge_plating')->default(false);
                $table->boolean('is_panelized')->default(false);
                $table->string('confidence_level')->default('medium');
                $table->json('raw_measurements')->nullable();

                $table->index('analysis_run_id');

                $table->foreign('analysis_run_id')->references('id')->on('pcb_file_analysis_runs')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_detected_dimensions');
    }
};
