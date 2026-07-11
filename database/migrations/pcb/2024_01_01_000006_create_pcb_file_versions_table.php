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
        if (!Schema::hasTable('pcb_file_versions')) {
            Schema::create('pcb_file_versions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('file_id');
                $table->integer('version_number');
                $table->uuid('uploaded_by_id');
                $table->string('original_filename');
                $table->string('stored_filename');
                $table->string('file_path');
                $table->bigInteger('file_size');
                $table->string('checksum_sha256');
                $table->text('change_summary')->nullable();
                $table->timestamps();

                $table->unique(['file_id', 'version_number']);
                $table->index('file_id');

                $table->foreign('file_id')->references('id')->on('pcb_files')->onDelete('cascade');
                $table->foreign('uploaded_by_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_file_versions');
    }
};
