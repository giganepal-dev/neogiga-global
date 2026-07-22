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
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->index();
            $table->string('reply_to_email')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->longText('html_content')->nullable();
            $table->longText('text_content')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('provider', 30)->nullable();
            $table->string('status', 20)->default('draft')->index(); // draft, validating, scheduled, queued, sending, paused, completed, cancelled, failed
            $table->string('category', 50)->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->string('timezone', 50)->default('UTC');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('track_opens')->default(true);
            $table->boolean('track_clicks')->default(true);
            $table->json('utm_params')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['provider', 'status']);
        });

        Schema::create('email_campaign_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('email_campaigns')->cascadeOnDelete()->index();
            $table->foreignId('group_id')->constrained('email_groups')->cascadeOnDelete()->index();
            $table->string('relation_type', 20)->default('include'); // include, exclude
            $table->timestamps();

            $table->unique(['campaign_id', 'group_id', 'relation_type']);
        });

        Schema::create('email_campaign_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('email_campaigns')->cascadeOnDelete()->index();
            $table->foreignId('segment_id')->constrained('email_segments')->cascadeOnDelete()->index();
            $table->string('relation_type', 20)->default('include'); // include, exclude
            $table->timestamps();

            $table->unique(['campaign_id', 'segment_id', 'relation_type']);
        });

        Schema::create('email_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('email_campaigns')->cascadeOnDelete()->index();
            $table->foreignId('subscriber_id')->constrained('email_subscribers')->cascadeOnDelete()->index();
            $table->string('status', 20)->default('pending')->index(); // pending, queued, sent, delivered, opened, clicked, bounced, complained, failed, skipped
            $table->string('failure_reason')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->integer('open_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->string('provider_message_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'subscriber_id']);
            $table->index(['status', 'queued_at']);
            $table->index(['campaign_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaign_recipients');
        Schema::dropIfExists('email_campaign_segments');
        Schema::dropIfExists('email_campaign_groups');
        Schema::dropIfExists('email_campaigns');
    }
};
