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
        if (!Schema::hasTable('pcb_detected_layers')) {
            Schema::create('pcb_detected_layers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('analysis_run_id');
                $table->string('layer_name'); // Top Copper, Bottom Solder Mask, etc.
                $table->string('detected_type'); // copper, mask, silkscreen, paste, outline, drill
                $table->string('side')->nullable(); // top, bottom, inner
                $table->integer('layer_order')->nullable();
                $table->string('original_filename')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_present')->default(true);
                $table->string('confidence_level')->default('high'); // high, medium, low
                $table->json('metadata')->nullable();

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
        Schema::dropIfExists('pcb_detected_layers');
    }
};
