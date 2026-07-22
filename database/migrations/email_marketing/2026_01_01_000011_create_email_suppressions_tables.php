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
        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('subscriber_id')->constrained('email_subscribers')->cascadeOnDelete()->index();
            $table->string('email')->index();
            $table->string('suppression_type', 30)->index(); // bounce, complaint, unsubscribe, manual, spam_report
            $table->string('reason')->nullable();
            $table->string('source', 50)->default('system'); // system, webhook, manual, import
            $table->boolean('is_permanent')->default(false)->index();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['suppression_type', 'is_permanent']);
            $table->unique(['subscriber_id', 'suppression_type']);
        });

        Schema::create('email_consents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('subscriber_id')->constrained('email_subscribers')->cascadeOnDelete()->index();
            $table->string('consent_type', 50)->index(); // transactional, promotional, newsletter, product_updates, seller_comm, event_emails, regional_offers
            $table->string('status', 20)->default('pending')->index(); // granted, denied, pending, withdrawn
            $table->string('source', 50)->default('manual');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('policy_version')->nullable();
            $table->string('region', 50)->nullable();
            $table->string('evidence_reference')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['consent_type', 'status']);
            $table->unique(['subscriber_id', 'consent_type']);
        });

        Schema::create('email_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->unique()->constrained('email_subscribers')->cascadeOnDelete()->index();
            $table->string('preferred_language', 10)->default('en');
            $table->string('preferred_currency', 3)->default('USD');
            $table->string('preferred_region', 50)->nullable();
            $table->integer('email_frequency')->default(1); // 0=none, 1=daily, 2=weekly, 3=monthly
            $table->boolean('receive_product_updates')->default(true);
            $table->boolean('receive_promotional')->default(true);
            $table->boolean('receive_newsletter')->default(true);
            $table->boolean('receive_seller_comm')->default(true);
            $table->boolean('receive_event_emails')->default(true);
            $table->boolean('receive_regional_offers')->default(true);
            $table->json('custom_preferences')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_preferences');
        Schema::dropIfExists('email_consents');
        Schema::dropIfExists('email_suppressions');
    }
};
