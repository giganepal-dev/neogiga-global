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
        if (!Schema::hasTable('pcb_project_members')) {
            Schema::create('pcb_project_members', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('project_id');
                $table->uuid('user_id');
                $table->uuid('organization_id')->nullable();
                $table->string('role')->default('member'); // owner, admin, engineer, viewer
                $table->boolean('can_edit')->default(false);
                $table->boolean('can_upload_files')->default(false);
                $table->boolean('can_approve')->default(false);
                $table->boolean('can_invite')->default(false);
                $table->timestamp('joined_at')->useCurrent();
                $table->uuid('invited_by_id')->nullable();
                $table->timestamp('expires_at')->nullable(); // For temporary supplier access
                $table->timestamps();

                $table->unique(['project_id', 'user_id']);
                $table->index('project_id');
                $table->index('user_id');
                $table->index('organization_id');

                $table->foreign('project_id')->references('id')->on('pcb_projects')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                if (Schema::hasTable('organizations')) {
                    $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_project_members');
    }
};
