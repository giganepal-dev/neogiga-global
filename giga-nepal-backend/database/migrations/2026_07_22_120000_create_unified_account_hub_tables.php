<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_account_roles')) {
            Schema::create('user_account_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role_key', 50)->index();
                $table->string('status', 30)->default('approved')->index();
                $table->string('source_type', 80)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'role_key', 'marketplace_id'], 'user_account_role_scope_unique');
                $table->index(['source_type', 'source_id']);
            });
        }

        if (! Schema::hasTable('account_applications')) {
            Schema::create('account_applications', function (Blueprint $table) {
                $table->id();
                $table->string('application_number', 40)->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role_key', 50)->index();
                $table->string('status', 30)->default('draft')->index();
                $table->string('company_name')->nullable();
                $table->string('legal_name')->nullable();
                $table->string('registration_number')->nullable();
                $table->string('tax_number')->nullable();
                $table->string('contact_phone')->nullable();
                $table->string('website')->nullable();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->string('territory')->nullable();
                $table->text('business_description')->nullable();
                $table->text('applicant_notes')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'role_key', 'status'], 'account_application_owner_status');
            });
        }

        if (! Schema::hasTable('account_application_documents')) {
            Schema::create('account_application_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_application_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('document_type', 80)->index();
                $table->string('original_name');
                $table->string('storage_disk', 30)->default('local');
                $table->string('storage_path');
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('sha256', 64)->nullable()->index();
                $table->string('status', 30)->default('pending')->index();
                $table->text('review_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('account_application_events')) {
            Schema::create('account_application_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_application_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('actor_user_id')->nullable()->index();
                $table->string('event_type', 60)->index();
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30)->nullable();
                $table->text('notes')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Production-safety rule: account history and approvals are never
        // destroyed automatically. Recovery is performed from a verified backup.
    }
};
