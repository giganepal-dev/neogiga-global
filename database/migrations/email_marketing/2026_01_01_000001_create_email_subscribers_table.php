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
        Schema::create('email_subscribers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('email', 320)->index();
            $table->string('normalized_email', 320)->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('job_title')->nullable();
            $table->string('subscriber_type', 50)->default('newsletter_subscriber')->index();
            $table->string('customer_type', 50)->nullable();
            $table->string('source', 50)->default('manual')->index();
            $table->string('source_reference')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete()->index();
            $table->string('country_code', 2)->nullable()->index();
            $table->foreignId('country_id')->nullable()->constrained('countries', 'iso_code')->nullOnDelete()->index();
            $table->string('state_or_province')->nullable();
            $table->string('city')->nullable();
            $table->string('preferred_language', 10)->default('en');
            $table->string('preferred_currency', 3)->default('USD');
            $table->string('timezone', 50)->default('UTC');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('last_email_sent_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->integer('engagement_score')->default(0)->index();
            $table->unsignedBigInteger('total_sent')->default(0);
            $table->unsignedBigInteger('total_delivered')->default(0);
            $table->unsignedBigInteger('total_opened')->default(0);
            $table->unsignedBigInteger('total_clicked')->default(0);
            $table->unsignedBigInteger('total_bounced')->default(0);
            $table->unsignedBigInteger('total_complaints')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['country_code', 'status']);
            $table->index(['subscriber_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_subscribers');
    }
};
