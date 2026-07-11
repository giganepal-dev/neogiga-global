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
        if (!Schema::hasTable('pcb_file_shares')) {
            Schema::create('pcb_file_shares', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('file_id');
                $table->uuid('shared_by_id');
                $table->uuid('shared_with_user_id')->nullable(); // Null means shared via link
                $table->uuid('shared_with_organization_id')->nullable();
                $table->string('share_type')->default('user'); // user, organization, link
                $table->string('access_token')->unique(); // For link-based sharing
                $table->enum('permission', ['view', 'download'])->default('view');
                $table->boolean('requires_nda')->default(false);
                $table->boolean('nda_accepted')->default(false);
                $table->timestamp('nda_accepted_at')->nullable();
                $table->timestamp('expires_at');
                $table->integer('access_count')->default(0);
                $table->integer('max_access_count')->nullable();
                $table->timestamps();

                $table->index('file_id');
                $table->index('shared_by_id');
                $table->index('access_token');
                $table->index('expires_at');

                $table->foreign('file_id')->references('id')->on('pcb_files')->onDelete('cascade');
                $table->foreign('shared_by_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('shared_with_user_id')->references('id')->on('users')->onDelete('set null');
                if (Schema::hasTable('organizations')) {
                    $table->foreign('shared_with_organization_id')->references('id')->on('organizations')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcb_file_shares');
    }
};
