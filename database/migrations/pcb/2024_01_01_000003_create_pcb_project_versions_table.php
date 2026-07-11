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
        if (!Schema::hasTable('pcb_project_versions')) {
            Schema::create('pcb_project_versions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('project_id');
                $table->integer('version_number');
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->string('change_summary')->nullable();
                $table->uuid('created_by_id');
                $table->enum('status', ['draft', 'submitted', 'reviewed', 'approved', 'rejected'])->default('draft');
                $table->json('metadata')->nullable(); // Store version-specific config
                $table->timestamps();

                $table->unique(['project_id', 'version_number']);
                $table->index('project_id');
                $table->index('created_by_id');

                $table->foreign('project_id')->references('id')->on('pcb_projects')->onDelete('cascade');
                $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_project_versions');
    }
};
