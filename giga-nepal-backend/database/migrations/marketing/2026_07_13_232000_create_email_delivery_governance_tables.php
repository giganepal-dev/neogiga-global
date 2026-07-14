<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createEmailSenderProfiles();
        $this->createEmailDomains();
        $this->createEmailTemplateVersions();
        $this->createCampaignAudienceSnapshots();
        $this->createCampaignLinks();
        $this->createEmailWebhookEvents();
        $this->createEmailBounces();
        $this->createEmailComplaints();
        $this->createCommunicationLogs();
        $this->createCommunicationFailures();
        $this->createEmailDeliveryCircuitBreakers();
        $this->extendEmailProviderConfigs();
        $this->extendEmailMessages();
        $this->extendEmailMessageEvents();
    }

    public function down(): void
    {
        $this->dropColumns('email_message_events', ['email_webhook_event_id', 'normalized_event_type', 'is_unique']);
        $this->dropColumns('email_messages', [
            'idempotency_key', 'sender_profile_id', 'marketplace_id', 'country_id', 'queue_name', 'related_type',
            'related_id', 'provider_message_id', 'attempts', 'delivered_at', 'failed_at', 'failure_reason',
        ]);
        $this->dropColumns('email_provider_configs', [
            'channel', 'api_base_url', 'account_id', 'sender_profile_id', 'sending_domain', 'reply_to',
            'rate_limit_per_minute', 'daily_limit', 'encrypted_settings', 'webhook_secret_encrypted',
            'last_tested_at', 'last_test_status',
        ]);

        foreach ([
            'email_delivery_circuit_breakers',
            'communication_failures',
            'communication_logs',
            'email_complaints',
            'email_bounces',
            'email_webhook_events',
            'campaign_links',
            'campaign_audience_snapshots',
            'email_template_versions',
            'email_domains',
            'email_sender_profiles',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createEmailSenderProfiles(): void
    {
        if (Schema::hasTable('email_sender_profiles')) {
            return;
        }
        Schema::create('email_sender_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->string('name')->unique();
            $table->string('purpose', 40)->index();
            $table->string('from_name');
            $table->string('from_email');
            $table->string('reply_to')->nullable();
            $table->string('domain')->index();
            $table->string('base_url', 2048);
            $table->string('default_currency', 3)->nullable();
            $table->string('default_language', 12)->default('en');
            $table->boolean('is_verified')->default(false)->index();
            $table->boolean('is_enabled')->default(false)->index();
            $table->json('branding')->nullable();
            $table->timestamps();
        });
    }

    private function createEmailDomains(): void
    {
        if (Schema::hasTable('email_domains')) {
            return;
        }
        Schema::create('email_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->string('domain')->unique();
            $table->string('purpose', 40)->index();
            $table->string('provider')->nullable()->index();
            $table->string('return_path_domain')->nullable();
            $table->string('bounce_domain')->nullable();
            $table->string('spf_status', 40)->default('unknown');
            $table->string('dkim_status', 40)->default('unknown');
            $table->string('dmarc_status', 40)->default('unknown');
            $table->string('provider_verification_status', 40)->default('unknown');
            $table->timestamp('last_checked_at')->nullable();
            $table->json('verification_details')->nullable();
            $table->timestamps();
        });
    }

    private function createEmailTemplateVersions(): void
    {
        if (Schema::hasTable('email_template_versions')) {
            return;
        }
        Schema::create('email_template_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_template_id')->index();
            $table->unsignedInteger('version');
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->json('variables')->nullable();
            $table->string('language', 12)->default('en');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->json('validation_results')->nullable();
            $table->timestamps();
            $table->unique(['email_template_id', 'version'], 'email_template_version_unique');
        });
    }

    private function createCampaignAudienceSnapshots(): void
    {
        if (Schema::hasTable('campaign_audience_snapshots')) {
            return;
        }
        Schema::create('campaign_audience_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_campaign_id')->index();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 40)->default('preparing')->index();
            $table->json('rules')->nullable();
            $table->json('exclusions')->nullable();
            $table->unsignedInteger('planned_count')->default(0);
            $table->unsignedInteger('eligible_count')->default(0);
            $table->unsignedInteger('excluded_count')->default(0);
            $table->json('exclusion_totals')->nullable();
            $table->string('snapshot_hash', 64)->nullable()->index();
            $table->timestamp('frozen_at')->nullable();
            $table->unsignedBigInteger('frozen_by')->nullable();
            $table->timestamps();
            $table->unique(['email_campaign_id', 'version'], 'campaign_audience_snapshot_version_unique');
        });
    }

    private function createCampaignLinks(): void
    {
        if (Schema::hasTable('campaign_links')) {
            return;
        }
        Schema::create('campaign_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_campaign_id')->index();
            $table->string('url', 2048);
            $table->string('normalized_url', 2048);
            $table->string('tracking_key', 64)->unique();
            $table->unsignedInteger('click_count')->default(0);
            $table->unsignedInteger('unique_click_count')->default(0);
            $table->timestamps();
        });
    }

    private function createEmailWebhookEvents(): void
    {
        if (Schema::hasTable('email_webhook_events')) {
            return;
        }
        Schema::create('email_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 80)->index();
            $table->string('provider_event_id')->nullable();
            $table->string('deduplication_key', 128)->unique();
            $table->string('event_type', 80)->index();
            $table->string('normalized_event_type', 80)->index();
            $table->string('provider_message_id')->nullable()->index();
            $table->unsignedBigInteger('email_message_id')->nullable()->index();
            $table->unsignedBigInteger('email_campaign_id')->nullable()->index();
            $table->unsignedBigInteger('email_campaign_recipient_id')->nullable()->index();
            $table->string('recipient_hash', 64)->nullable()->index();
            $table->longText('raw_payload_encrypted');
            $table->json('normalized_payload')->nullable();
            $table->boolean('signature_verified')->default(false)->index();
            $table->string('processing_status', 40)->default('pending')->index();
            $table->text('processing_error')->nullable();
            $table->timestamp('provider_occurred_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    private function createEmailBounces(): void
    {
        if (Schema::hasTable('email_bounces')) {
            return;
        }
        Schema::create('email_bounces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_webhook_event_id')->index();
            $table->unsignedBigInteger('email_message_id')->nullable()->index();
            $table->unsignedBigInteger('contact_email_address_id')->nullable()->index();
            $table->string('email_hash', 64)->index();
            $table->string('bounce_type', 40)->index();
            $table->string('provider_code')->nullable();
            $table->text('diagnostic')->nullable();
            $table->timestamp('bounced_at')->index();
            $table->timestamps();
        });
    }

    private function createEmailComplaints(): void
    {
        if (Schema::hasTable('email_complaints')) {
            return;
        }
        Schema::create('email_complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_webhook_event_id')->index();
            $table->unsignedBigInteger('email_message_id')->nullable()->index();
            $table->unsignedBigInteger('contact_email_address_id')->nullable()->index();
            $table->string('email_hash', 64)->index();
            $table->string('complaint_type')->nullable();
            $table->string('provider_feedback_id')->nullable();
            $table->timestamp('complained_at')->index();
            $table->timestamps();
        });
    }

    private function createCommunicationLogs(): void
    {
        if (Schema::hasTable('communication_logs')) {
            return;
        }
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 128)->unique();
            $table->string('event_type', 100)->index();
            $table->string('channel', 30)->default('email')->index();
            $table->string('message_class', 40)->index();
            $table->unsignedBigInteger('email_message_id')->nullable()->index();
            $table->unsignedBigInteger('customer_contact_id')->nullable()->index();
            $table->string('recipient_hash', 64)->index();
            $table->unsignedBigInteger('email_template_id')->nullable()->index();
            $table->unsignedBigInteger('email_template_version_id')->nullable()->index();
            $table->string('provider')->nullable()->index();
            $table->string('provider_message_id')->nullable()->index();
            $table->string('status', 40)->default('queued')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('related_type')->nullable()->index();
            $table->unsignedBigInteger('related_id')->nullable()->index();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    private function createCommunicationFailures(): void
    {
        if (Schema::hasTable('communication_failures')) {
            return;
        }
        Schema::create('communication_failures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('communication_log_id')->nullable()->index();
            $table->unsignedBigInteger('email_message_id')->nullable()->index();
            $table->string('provider')->nullable()->index();
            $table->string('failure_code')->nullable()->index();
            $table->text('failure_reason');
            $table->boolean('is_retryable')->default(false)->index();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->timestamp('retry_at')->nullable()->index();
            $table->json('safe_context')->nullable();
            $table->timestamps();
        });
    }

    private function createEmailDeliveryCircuitBreakers(): void
    {
        if (Schema::hasTable('email_delivery_circuit_breakers')) {
            return;
        }
        Schema::create('email_delivery_circuit_breakers', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('channel', 40)->index();
            $table->string('state', 20)->default('closed')->index();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('retry_after')->nullable()->index();
            $table->string('last_failure_code')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'channel'], 'email_circuit_provider_channel_unique');
        });
    }

    private function extendEmailProviderConfigs(): void
    {
        $this->addColumns('email_provider_configs', [
            'channel' => fn (Blueprint $table) => $table->string('channel', 40)->default('marketing')->index(),
            'api_base_url' => fn (Blueprint $table) => $table->string('api_base_url', 2048)->nullable(),
            'account_id' => fn (Blueprint $table) => $table->string('account_id')->nullable(),
            'sender_profile_id' => fn (Blueprint $table) => $table->unsignedBigInteger('sender_profile_id')->nullable()->index(),
            'sending_domain' => fn (Blueprint $table) => $table->string('sending_domain')->nullable(),
            'reply_to' => fn (Blueprint $table) => $table->string('reply_to')->nullable(),
            'rate_limit_per_minute' => fn (Blueprint $table) => $table->unsignedInteger('rate_limit_per_minute')->default(60),
            'daily_limit' => fn (Blueprint $table) => $table->unsignedInteger('daily_limit')->default(5000),
            'encrypted_settings' => fn (Blueprint $table) => $table->longText('encrypted_settings')->nullable(),
            'webhook_secret_encrypted' => fn (Blueprint $table) => $table->longText('webhook_secret_encrypted')->nullable(),
            'last_tested_at' => fn (Blueprint $table) => $table->timestamp('last_tested_at')->nullable(),
            'last_test_status' => fn (Blueprint $table) => $table->string('last_test_status', 40)->nullable(),
        ]);
    }

    private function extendEmailMessages(): void
    {
        $this->addColumns('email_messages', [
            'idempotency_key' => fn (Blueprint $table) => $table->string('idempotency_key', 128)->nullable()->unique(),
            'sender_profile_id' => fn (Blueprint $table) => $table->unsignedBigInteger('sender_profile_id')->nullable()->index(),
            'marketplace_id' => fn (Blueprint $table) => $table->unsignedBigInteger('marketplace_id')->nullable()->index(),
            'country_id' => fn (Blueprint $table) => $table->unsignedBigInteger('country_id')->nullable()->index(),
            'queue_name' => fn (Blueprint $table) => $table->string('queue_name', 40)->default('transactional')->index(),
            'related_type' => fn (Blueprint $table) => $table->string('related_type')->nullable()->index(),
            'related_id' => fn (Blueprint $table) => $table->unsignedBigInteger('related_id')->nullable()->index(),
            'provider_message_id' => fn (Blueprint $table) => $table->string('provider_message_id')->nullable()->index(),
            'attempts' => fn (Blueprint $table) => $table->unsignedSmallInteger('attempts')->default(0),
            'delivered_at' => fn (Blueprint $table) => $table->timestamp('delivered_at')->nullable(),
            'failed_at' => fn (Blueprint $table) => $table->timestamp('failed_at')->nullable(),
            'failure_reason' => fn (Blueprint $table) => $table->text('failure_reason')->nullable(),
        ]);
    }

    private function extendEmailMessageEvents(): void
    {
        $this->addColumns('email_message_events', [
            'email_webhook_event_id' => fn (Blueprint $table) => $table->unsignedBigInteger('email_webhook_event_id')->nullable()->index(),
            'normalized_event_type' => fn (Blueprint $table) => $table->string('normalized_event_type', 80)->nullable()->index(),
            'is_unique' => fn (Blueprint $table) => $table->boolean('is_unique')->default(true)->index(),
        ]);
    }

    /** @param array<string, callable(Blueprint): void> $columns */
    private function addColumns(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }
        foreach ($columns as $column => $callback) {
            if (! Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, $callback);
            }
        }
    }

    /** @param list<string> $columns */
    private function dropColumns(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }
        $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($tableName, $column)));
        if ($existing !== []) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }
};
