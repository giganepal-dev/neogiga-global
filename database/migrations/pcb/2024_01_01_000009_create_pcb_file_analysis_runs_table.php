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
        if (!Schema::hasTable('pcb_file_analysis_runs')) {
            Schema::create('pcb_file_analysis_runs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('file_id');
                $table->string('analysis_type'); // gerber_parse, dimension_detect, layer_detect, dfm_check
                $table->string('parser_version')->nullable();
                $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
                $table->json('configuration')->nullable();
                $table->json('results')->nullable();
                $table->text('error_message')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->uuid('triggered_by_id')->nullable();
                $table->timestamps();

                $table->index('file_id');
                $table->index('analysis_type');
                $table->index('status');

                $table->foreign('file_id')->references('id')->on('pcb_files')->onDelete('cascade');
                $table->foreign('triggered_by_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_file_analysis_runs');
    }
};
