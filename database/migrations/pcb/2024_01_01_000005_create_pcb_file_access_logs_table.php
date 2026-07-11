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
        if (!Schema::hasTable('pcb_file_access_logs')) {
            Schema::create('pcb_file_access_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('file_id');
                $table->uuid('user_id');
                $table->string('action'); // view, download, share, delete
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->uuid('organization_id')->nullable();
                $table->string('access_reason')->nullable(); // For audit trail
                $table->timestamp('accessed_at')->useCurrent();

                $table->index('file_id');
                $table->index('user_id');
                $table->index('accessed_at');

                $table->foreign('file_id')->references('id')->on('pcb_files')->onDelete('cascade');
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
        Schema::dropIfExists('pcb_file_access_logs');
    }
};
