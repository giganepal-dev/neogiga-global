<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->extendNewsletterCampaigns();
        $this->extendNewsletterRecipients();
        $this->extendEmailMessages();
        $this->extendWebhookEvents();
        $this->createNewsletterTemplateVersions();
        $this->createNewsletterAudienceSnapshots();
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_audience_snapshots');
        Schema::dropIfExists('newsletter_template_versions');
        $this->dropColumns('email_webhook_events', ['newsletter_campaign_id', 'newsletter_campaign_recipient_id']);
        $this->dropColumns('email_messages', ['newsletter_campaign_id']);
        $this->dropColumns('newsletter_campaign_recipients', ['newsletter_audience_snapshot_id', 'eligibility_status', 'eligibility_reasons', 'snapshot_hash']);
        $this->dropColumns('newsletter_campaigns', [
            'marketplace_id', 'internal_reference', 'reply_to', 'requires_approval', 'production_send_enabled',
            'approved_by', 'approved_at', 'audience_snapshot_hash', 'paused_at', 'cancelled_at', 'send_cursor', 'failure_reason',
        ]);
    }

    private function extendNewsletterCampaigns(): void
    {
        $this->addColumns('newsletter_campaigns', [
            'marketplace_id' => fn (Blueprint $table) => $table->unsignedBigInteger('marketplace_id')->nullable()->index(),
            'internal_reference' => fn (Blueprint $table) => $table->string('internal_reference')->nullable()->unique(),
            'reply_to' => fn (Blueprint $table) => $table->string('reply_to')->nullable(),
            'requires_approval' => fn (Blueprint $table) => $table->boolean('requires_approval')->default(true),
            'production_send_enabled' => fn (Blueprint $table) => $table->boolean('production_send_enabled')->default(false)->index(),
            'approved_by' => fn (Blueprint $table) => $table->unsignedBigInteger('approved_by')->nullable()->index(),
            'approved_at' => fn (Blueprint $table) => $table->timestamp('approved_at')->nullable()->index(),
            'audience_snapshot_hash' => fn (Blueprint $table) => $table->string('audience_snapshot_hash', 64)->nullable()->index(),
            'paused_at' => fn (Blueprint $table) => $table->timestamp('paused_at')->nullable(),
            'cancelled_at' => fn (Blueprint $table) => $table->timestamp('cancelled_at')->nullable(),
            'send_cursor' => fn (Blueprint $table) => $table->unsignedBigInteger('send_cursor')->default(0),
            'failure_reason' => fn (Blueprint $table) => $table->text('failure_reason')->nullable(),
        ]);
    }

    private function extendNewsletterRecipients(): void
    {
        $this->addColumns('newsletter_campaign_recipients', [
            'newsletter_audience_snapshot_id' => fn (Blueprint $table) => $table->unsignedBigInteger('newsletter_audience_snapshot_id')->nullable()->index(),
            'eligibility_status' => fn (Blueprint $table) => $table->string('eligibility_status', 80)->default('legacy_review_required')->index(),
            'eligibility_reasons' => fn (Blueprint $table) => $table->json('eligibility_reasons')->nullable(),
            'snapshot_hash' => fn (Blueprint $table) => $table->string('snapshot_hash', 64)->nullable()->index(),
        ]);
    }

    private function extendEmailMessages(): void
    {
        $this->addColumns('email_messages', [
            'newsletter_campaign_id' => fn (Blueprint $table) => $table->unsignedBigInteger('newsletter_campaign_id')->nullable()->index(),
        ]);
    }

    private function extendWebhookEvents(): void
    {
        $this->addColumns('email_webhook_events', [
            'newsletter_campaign_id' => fn (Blueprint $table) => $table->unsignedBigInteger('newsletter_campaign_id')->nullable()->index(),
            'newsletter_campaign_recipient_id' => fn (Blueprint $table) => $table->unsignedBigInteger('newsletter_campaign_recipient_id')->nullable()->index(),
        ]);
    }

    private function createNewsletterAudienceSnapshots(): void
    {
        if (Schema::hasTable('newsletter_audience_snapshots')) {
            return;
        }

        Schema::create('newsletter_audience_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_campaign_id')->index();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 40)->default('frozen')->index();
            $table->json('rules')->nullable();
            $table->unsignedInteger('planned_count')->default(0);
            $table->unsignedInteger('eligible_count')->default(0);
            $table->unsignedInteger('excluded_count')->default(0);
            $table->json('exclusion_totals')->nullable();
            $table->string('snapshot_hash', 64)->index();
            $table->timestamp('frozen_at')->index();
            $table->unsignedBigInteger('frozen_by')->nullable();
            $table->timestamps();
            $table->unique(['newsletter_campaign_id', 'version'], 'newsletter_audience_snapshot_version_unique');
        });
    }

    private function createNewsletterTemplateVersions(): void
    {
        if (Schema::hasTable('newsletter_template_versions')) {
            return;
        }

        Schema::create('newsletter_template_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_template_id')->index();
            $table->unsignedInteger('version');
            $table->string('subject');
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->json('variables')->nullable();
            $table->string('language', 12)->default('en');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->json('validation_results')->nullable();
            $table->timestamps();
            $table->unique(['newsletter_template_id', 'version'], 'newsletter_template_version_unique');
        });
    }

    /** @param array<string, callable(Blueprint): void> $columns */
    private function addColumns(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }
        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, fn (Blueprint $table) => $definition($table));
            }
        }
    }

    /** @param list<string> $columns */
    private function dropColumns(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }
        $present = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($tableName, $column)));
        if ($present !== []) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($present));
        }
    }
};
