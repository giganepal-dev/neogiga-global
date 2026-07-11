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
        if (!Schema::hasTable('pcb_file_scan_results')) {
            Schema::create('pcb_file_scan_results', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('file_id');
                $table->string('scanner_name'); // clamav, custom, etc.
                $table->string('scanner_version')->nullable();
                $table->enum('result', ['clean', 'infected', 'suspicious', 'error'])->default('clean');
                $table->string('threat_name')->nullable();
                $table->text('scan_log')->nullable();
                $table->integer('scan_duration_ms')->nullable();
                $table->timestamp('scanned_at')->useCurrent();

                $table->index('file_id');
                $table->index('result');
                $table->index('scanned_at');

                $table->foreign('file_id')->references('id')->on('pcb_files')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_file_scan_results');
    }
};
