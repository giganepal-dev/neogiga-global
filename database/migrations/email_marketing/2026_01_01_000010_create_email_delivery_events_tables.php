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
        Schema::create('email_delivery_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('campaign_id')->nullable()->constrained('email_campaigns')->nullOnDelete()->index();
            $table->foreignId('recipient_id')->nullable()->constrained('email_campaign_recipients')->nullOnDelete()->index();
            $table->foreignId('subscriber_id')->constrained('email_subscribers')->cascadeOnDelete()->index();
            $table->string('event_type', 30)->index(); // queued, sent, delivered, opened, clicked, soft_bounced, hard_bounced, complained, rejected, deferred, unsubscribed
            $table->string('provider', 30)->index();
            $table->string('provider_event_id')->index();
            $table->string('provider_message_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->boolean('processed')->default(false)->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'occurred_at']);
            $table->index(['subscriber_id', 'event_type']);
            $table->unique(['provider', 'provider_event_id']);
        });

        Schema::create('email_click_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_event_id')->constrained('email_delivery_events')->cascadeOnDelete()->index();
            $table->foreignId('campaign_id')->constrained('email_campaigns')->cascadeOnDelete()->index();
            $table->foreignId('subscriber_id')->constrained('email_subscribers')->cascadeOnDelete()->index();
            $table->string('url')->index();
            $table->string('link_id')->nullable();
            $table->timestamp('clicked_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'clicked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_click_events');
        Schema::dropIfExists('email_delivery_events');
    }
};
