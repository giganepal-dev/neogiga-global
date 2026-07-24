<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campaign contact import tracking
        Schema::create('campaign_contact_imports', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable();
            $table->string('source', 50)->default('csv');
            $table->string('status', 50)->default('pending');
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('created_rows')->default(0);
            $table->integer('updated_rows')->default(0);
            $table->integer('linked_rows')->default(0);
            $table->integer('skipped_rows')->default(0);
            $table->integer('error_rows')->default(0);
            $table->string('batch', 190)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Campaign contact import errors
        Schema::create('campaign_contact_import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_contact_import_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->string('field', 100)->nullable();
            $table->string('code', 50);
            $table->string('severity', 20)->default('error');
            $table->text('message');
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });

        // Customer invitations
        Schema::create('customer_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('token', 255)->unique();
            $table->foreignId('subscriber_id')->nullable()->constrained('email_subscribers')->nullOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Contact conversion logs
        Schema::create('contact_conversion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->nullable()->constrained('email_subscribers')->nullOnDelete();
            $table->string('conversion_type', 50);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customer_profiles')->nullOnDelete();
            $table->timestamp('converted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_conversion_logs');
        Schema::dropIfExists('customer_invitations');
        Schema::dropIfExists('campaign_contact_import_errors');
        Schema::dropIfExists('campaign_contact_imports');
    }
};
