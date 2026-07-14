<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->extendCustomerConsents();
        $this->createEmailSubscriptions();
        $this->createEmailPreferences();
        $this->createCustomerSegmentRules();
        $this->extendSuppressions();
        $this->extendUnsubscribes();
        $this->extendNewsletterSubscribers();
        $this->extendCustomerSegments();
        $this->extendEmailCampaigns();
        $this->extendEmailCampaignRecipients();
    }

    public function down(): void
    {
        $this->dropColumns('email_campaign_recipients', [
            'audience_snapshot_id', 'eligibility_status', 'eligibility_reasons', 'idempotency_key', 'provider_recipient_id',
        ]);
        $this->dropColumns('email_campaigns', [
            'internal_reference', 'preview_text', 'sender_profile_id', 'reply_to', 'marketplace_id', 'target_country_ids',
            'exclusions', 'language', 'timezone', 'tracking_settings', 'utm_settings', 'created_by', 'approved_by',
            'approved_at', 'recipient_count', 'eligible_count', 'excluded_count', 'provider_campaign_id',
            'audience_snapshot_hash', 'send_cursor', 'requires_approval', 'production_send_enabled', 'paused_at',
            'cancelled_at', 'failure_reason',
        ]);
        $this->dropColumns('customer_segments', [
            'marketplace_id', 'country_ids', 'eligibility_policy', 'snapshot_mode', 'created_by', 'requires_consent_review',
        ]);
        $this->dropColumns('newsletter_subscribers', [
            'customer_contact_id', 'contact_email_address_id', 'consent_status', 'lawful_basis', 'consent_evidence', 'consent_recorded_at',
        ]);
        $this->dropColumns('unsubscribes', [
            'customer_contact_id', 'contact_email_address_id', 'email_campaign_id', 'contact_list_id', 'scope',
            'token_hash', 'source', 'user_agent', 'confirmed_at',
        ]);
        $this->dropColumns('suppression_lists', [
            'customer_contact_id', 'contact_email_address_id', 'message_scope', 'reason_code', 'provider',
            'provider_reference', 'is_global', 'is_active', 'is_hard', 'expires_at', 'email_webhook_event_id',
        ]);
        $this->dropColumns('customer_consents', [
            'customer_contact_id', 'contact_email_address_id', 'customer_import_id', 'customer_import_row_id',
            'status', 'lawful_basis', 'evidence', 'jurisdiction', 'country_policy', 'marketplace_id',
            'contact_list_id', 'policy_version', 'effective_at', 'recorded_at',
        ]);

        Schema::dropIfExists('customer_segment_rules');
        Schema::dropIfExists('email_preferences');
        Schema::dropIfExists('email_subscriptions');
    }

    private function extendCustomerConsents(): void
    {
        $this->addColumns('customer_consents', [
            'customer_contact_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_contact_id')->nullable()->index(),
            'contact_email_address_id' => fn (Blueprint $table) => $table->unsignedBigInteger('contact_email_address_id')->nullable()->index(),
            'customer_import_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_import_id')->nullable()->index(),
            'customer_import_row_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_import_row_id')->nullable()->index(),
            'status' => fn (Blueprint $table) => $table->string('status', 80)->default('unknown')->index(),
            'lawful_basis' => fn (Blueprint $table) => $table->string('lawful_basis', 80)->nullable()->index(),
            'evidence' => fn (Blueprint $table) => $table->json('evidence')->nullable(),
            'jurisdiction' => fn (Blueprint $table) => $table->string('jurisdiction', 40)->nullable()->index(),
            'country_policy' => fn (Blueprint $table) => $table->string('country_policy')->nullable(),
            'marketplace_id' => fn (Blueprint $table) => $table->unsignedBigInteger('marketplace_id')->nullable()->index(),
            'contact_list_id' => fn (Blueprint $table) => $table->unsignedBigInteger('contact_list_id')->nullable()->index(),
            'policy_version' => fn (Blueprint $table) => $table->string('policy_version', 40)->nullable(),
            'effective_at' => fn (Blueprint $table) => $table->timestamp('effective_at')->nullable()->index(),
            'recorded_at' => fn (Blueprint $table) => $table->timestamp('recorded_at')->nullable()->index(),
        ]);
    }

    private function createEmailSubscriptions(): void
    {
        if (Schema::hasTable('email_subscriptions')) {
            return;
        }

        Schema::create('email_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_contact_id')->nullable()->index();
            $table->unsignedBigInteger('contact_email_address_id')->nullable()->index();
            $table->unsignedBigInteger('contact_list_id')->nullable()->index();
            $table->string('email')->index();
            $table->string('category', 80)->default('all_marketing')->index();
            $table->string('status', 40)->default('pending')->index();
            $table->string('source')->nullable();
            $table->string('lawful_basis', 80)->nullable();
            $table->json('evidence')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->unique(['email', 'contact_list_id', 'category'], 'email_subscription_scope_unique');
        });
    }

    private function createEmailPreferences(): void
    {
        if (Schema::hasTable('email_preferences')) {
            return;
        }

        Schema::create('email_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_contact_id')->nullable()->index();
            $table->unsignedBigInteger('contact_email_address_id')->nullable()->index();
            $table->string('email')->unique();
            $table->json('categories')->nullable();
            $table->string('preferred_language', 12)->default('en');
            $table->string('preferred_format', 20)->default('html');
            $table->string('frequency', 40)->default('standard');
            $table->boolean('all_marketing_opt_out')->default(false)->index();
            $table->timestamp('updated_by_recipient_at')->nullable();
            $table->timestamps();
        });
    }

    private function createCustomerSegmentRules(): void
    {
        if (Schema::hasTable('customer_segment_rules')) {
            return;
        }

        Schema::create('customer_segment_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_segment_id')->index();
            $table->string('field')->index();
            $table->string('operator', 40)->default('equals');
            $table->json('value')->nullable();
            $table->string('boolean_group', 20)->default('and');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    private function extendSuppressions(): void
    {
        $this->addColumns('suppression_lists', [
            'customer_contact_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_contact_id')->nullable()->index(),
            'contact_email_address_id' => fn (Blueprint $table) => $table->unsignedBigInteger('contact_email_address_id')->nullable()->index(),
            'message_scope' => fn (Blueprint $table) => $table->string('message_scope', 40)->default('marketing')->index(),
            'reason_code' => fn (Blueprint $table) => $table->string('reason_code', 80)->nullable()->index(),
            'provider' => fn (Blueprint $table) => $table->string('provider', 80)->nullable()->index(),
            'provider_reference' => fn (Blueprint $table) => $table->string('provider_reference')->nullable(),
            'is_global' => fn (Blueprint $table) => $table->boolean('is_global')->default(false)->index(),
            'is_active' => fn (Blueprint $table) => $table->boolean('is_active')->default(true)->index(),
            'is_hard' => fn (Blueprint $table) => $table->boolean('is_hard')->default(false)->index(),
            'expires_at' => fn (Blueprint $table) => $table->timestamp('expires_at')->nullable()->index(),
            'email_webhook_event_id' => fn (Blueprint $table) => $table->unsignedBigInteger('email_webhook_event_id')->nullable()->index(),
        ]);
    }

    private function extendUnsubscribes(): void
    {
        $this->addColumns('unsubscribes', [
            'customer_contact_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_contact_id')->nullable()->index(),
            'contact_email_address_id' => fn (Blueprint $table) => $table->unsignedBigInteger('contact_email_address_id')->nullable()->index(),
            'email_campaign_id' => fn (Blueprint $table) => $table->unsignedBigInteger('email_campaign_id')->nullable()->index(),
            'contact_list_id' => fn (Blueprint $table) => $table->unsignedBigInteger('contact_list_id')->nullable()->index(),
            'scope' => fn (Blueprint $table) => $table->string('scope', 40)->default('all_marketing')->index(),
            'token_hash' => fn (Blueprint $table) => $table->string('token_hash', 64)->nullable()->unique(),
            'source' => fn (Blueprint $table) => $table->string('source')->nullable(),
            'user_agent' => fn (Blueprint $table) => $table->text('user_agent')->nullable(),
            'confirmed_at' => fn (Blueprint $table) => $table->timestamp('confirmed_at')->nullable(),
        ]);
    }

    private function extendNewsletterSubscribers(): void
    {
        $this->addColumns('newsletter_subscribers', [
            'customer_contact_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_contact_id')->nullable()->index(),
            'contact_email_address_id' => fn (Blueprint $table) => $table->unsignedBigInteger('contact_email_address_id')->nullable()->index(),
            'consent_status' => fn (Blueprint $table) => $table->string('consent_status', 80)->default('unknown')->index(),
            'lawful_basis' => fn (Blueprint $table) => $table->string('lawful_basis', 80)->nullable(),
            'consent_evidence' => fn (Blueprint $table) => $table->json('consent_evidence')->nullable(),
            'consent_recorded_at' => fn (Blueprint $table) => $table->timestamp('consent_recorded_at')->nullable(),
        ]);
    }

    private function extendCustomerSegments(): void
    {
        $this->addColumns('customer_segments', [
            'marketplace_id' => fn (Blueprint $table) => $table->unsignedBigInteger('marketplace_id')->nullable()->index(),
            'country_ids' => fn (Blueprint $table) => $table->json('country_ids')->nullable(),
            'eligibility_policy' => fn (Blueprint $table) => $table->json('eligibility_policy')->nullable(),
            'snapshot_mode' => fn (Blueprint $table) => $table->string('snapshot_mode', 30)->default('send_time')->index(),
            'created_by' => fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable()->index(),
            'requires_consent_review' => fn (Blueprint $table) => $table->boolean('requires_consent_review')->default(true)->index(),
        ]);
    }

    private function extendEmailCampaigns(): void
    {
        $this->addColumns('email_campaigns', [
            'internal_reference' => fn (Blueprint $table) => $table->string('internal_reference')->nullable()->unique(),
            'preview_text' => fn (Blueprint $table) => $table->string('preview_text')->nullable(),
            'sender_profile_id' => fn (Blueprint $table) => $table->unsignedBigInteger('sender_profile_id')->nullable()->index(),
            'reply_to' => fn (Blueprint $table) => $table->string('reply_to')->nullable(),
            'marketplace_id' => fn (Blueprint $table) => $table->unsignedBigInteger('marketplace_id')->nullable()->index(),
            'target_country_ids' => fn (Blueprint $table) => $table->json('target_country_ids')->nullable(),
            'exclusions' => fn (Blueprint $table) => $table->json('exclusions')->nullable(),
            'language' => fn (Blueprint $table) => $table->string('language', 12)->default('en')->index(),
            'timezone' => fn (Blueprint $table) => $table->string('timezone')->default('UTC'),
            'tracking_settings' => fn (Blueprint $table) => $table->json('tracking_settings')->nullable(),
            'utm_settings' => fn (Blueprint $table) => $table->json('utm_settings')->nullable(),
            'created_by' => fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable()->index(),
            'approved_by' => fn (Blueprint $table) => $table->unsignedBigInteger('approved_by')->nullable()->index(),
            'approved_at' => fn (Blueprint $table) => $table->timestamp('approved_at')->nullable()->index(),
            'recipient_count' => fn (Blueprint $table) => $table->unsignedInteger('recipient_count')->default(0),
            'eligible_count' => fn (Blueprint $table) => $table->unsignedInteger('eligible_count')->default(0),
            'excluded_count' => fn (Blueprint $table) => $table->unsignedInteger('excluded_count')->default(0),
            'provider_campaign_id' => fn (Blueprint $table) => $table->string('provider_campaign_id')->nullable()->index(),
            'audience_snapshot_hash' => fn (Blueprint $table) => $table->string('audience_snapshot_hash', 64)->nullable()->index(),
            'send_cursor' => fn (Blueprint $table) => $table->unsignedInteger('send_cursor')->default(0),
            'requires_approval' => fn (Blueprint $table) => $table->boolean('requires_approval')->default(true)->index(),
            'production_send_enabled' => fn (Blueprint $table) => $table->boolean('production_send_enabled')->default(false)->index(),
            'paused_at' => fn (Blueprint $table) => $table->timestamp('paused_at')->nullable(),
            'cancelled_at' => fn (Blueprint $table) => $table->timestamp('cancelled_at')->nullable(),
            'failure_reason' => fn (Blueprint $table) => $table->text('failure_reason')->nullable(),
        ]);
    }

    private function extendEmailCampaignRecipients(): void
    {
        $this->addColumns('email_campaign_recipients', [
            'audience_snapshot_id' => fn (Blueprint $table) => $table->unsignedBigInteger('audience_snapshot_id')->nullable()->index(),
            'eligibility_status' => fn (Blueprint $table) => $table->string('eligibility_status', 40)->default('pending')->index(),
            'eligibility_reasons' => fn (Blueprint $table) => $table->json('eligibility_reasons')->nullable(),
            'idempotency_key' => fn (Blueprint $table) => $table->string('idempotency_key', 128)->nullable()->unique(),
            'provider_recipient_id' => fn (Blueprint $table) => $table->string('provider_recipient_id')->nullable(),
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
