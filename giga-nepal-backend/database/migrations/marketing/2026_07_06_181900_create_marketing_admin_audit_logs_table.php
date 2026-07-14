<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketing_admin_audit_logs')) {
            Schema::create('marketing_admin_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_email')->nullable()->index();
                $table->string('action')->index();
                $table->string('entity_type')->nullable()->index();
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['entity_type', 'entity_id'], 'marketing_admin_audit_entity_index');
            });
        }
    }

    public function down(): void
    {
        // Production-safe migration: do not drop audit history automatically.
    }
};
