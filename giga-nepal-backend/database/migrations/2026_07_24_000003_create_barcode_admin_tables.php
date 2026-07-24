<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Barcode settings
        if (!Schema::hasTable('barcode_settings')) {
            Schema::create('barcode_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->json('value')->nullable();
                $table->string('type')->default('string'); // string, boolean, integer, json
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Barcode audit logs
        if (!Schema::hasTable('barcode_audit_logs')) {
            Schema::create('barcode_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('barcode_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('action', 50)->index(); // created, updated, generated, replaced, archived, deleted, etc.
                $table->string('entity_type', 50)->default('barcode');
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->text('reason')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->string('request_id', 100)->nullable();
                $table->timestamps();

                $table->index(['entity_type', 'entity_id']);
                $table->index(['action', 'created_at']);
            });
        }

        // Barcode approvals
        if (!Schema::hasTable('barcode_approvals')) {
            Schema::create('barcode_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('barcode_id')->nullable()->index();
                $table->unsignedBigInteger('requested_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->string('action', 50); // replace, delete, bulk_generate, import_external
                $table->string('status', 30)->default('pending'); // pending, approved, rejected
                $table->json('payload')->nullable();
                $table->text('reason')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->foreign('barcode_id')->references('id')->on('product_barcodes')->nullOnDelete();
                $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Barcode import jobs
        if (!Schema::hasTable('barcode_import_jobs')) {
            Schema::create('barcode_import_jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('file_path')->nullable();
                $table->string('file_name')->nullable();
                $table->string('status', 30)->default('pending'); // pending, processing, completed, failed
                $table->integer('total_rows')->default(0);
                $table->integer('processed_rows')->default(0);
                $table->integer('success_rows')->default(0);
                $table->integer('failed_rows')->default(0);
                $table->integer('duplicate_rows')->default(0);
                $table->json('column_mapping')->nullable();
                $table->json('errors')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Barcode label template assignments
        if (!Schema::hasTable('barcode_template_assignments')) {
            Schema::create('barcode_template_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('template_id')->index();
                $table->string('assignable_type', 50); // warehouse, region, category
                $table->unsignedBigInteger('assignable_id')->index();
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->foreign('template_id')->references('id')->on('barcode_label_templates')->cascadeOnDelete();
                $table->unique(['template_id', 'assignable_type', 'assignable_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_template_assignments');
        Schema::dropIfExists('barcode_import_jobs');
        Schema::dropIfExists('barcode_approvals');
        Schema::dropIfExists('barcode_audit_logs');
        Schema::dropIfExists('barcode_settings');
    }
};
