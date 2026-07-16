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
        $this->enhanceEmailSenderProfiles();
        $this->enhanceEmailSuppressions();
        $this->createMailPreferences();
        $this->createMailUnsubscribeTokens();
        $this->createMailDispatches();
        $this->createMailEvents();
        $this->createMailCampaignRecipients();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_campaign_recipients');
        Schema::dropIfExists('mail_events');
        Schema::dropIfExists('mail_dispatches');
        Schema::dropIfExists('mail_unsubscribe_tokens');
        Schema::dropIfExists('mail_preferences');
        
        $this->dropEnhancements();
    }

    private function enhanceEmailSenderProfiles(): void
    {
        if (!Schema::hasTable('email_sender_profiles')) {
            return;
        }

        Schema::table('email_sender_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('email_sender_profiles', 'country_code')) {
                $table->string('country_code', 2)->nullable()->after('marketplace_id')->index();
            }
            
            if (!Schema::hasColumn('email_sender_profiles', 'sender_type')) {
                $table->string('sender_type', 40)->default('transactional')->after('purpose')->index();
            }
            
            if (!Schema::hasColumn('email_sender_profiles', 'ses_region')) {
                $table->string('ses_region', 20)->default('us-east-1')->after('domain');
            }
            
            if (!Schema::hasColumn('email_sender_profiles', 'configuration_set')) {
                $table->string('configuration_set', 100)->nullable()->after('ses_region');
            }
            
            if (!Schema::hasColumn('email_sender_profiles', 'hourly_limit')) {
                $table->unsignedInteger('hourly_limit')->nullable()->after('daily_limit');
            }
        });
    }

    private function enhanceEmailSuppressions(): void
    {
        if (!Schema::hasTable('email_suppressions')) {
            return;
        }

        Schema::table('email_suppressions', function (Blueprint $table) {
            if (!Schema::hasColumn('email_suppressions', 'marketplace_id')) {
                $table->unsignedBigInteger('marketplace_id')->nullable()->after('source')->index();
            }
            
            if (!Schema::hasColumn('email_suppressions', 'suppressed_at')) {
                $table->timestamp('suppressed_at')->useCurrent()->after('source');
            }
            
            if (!Schema::hasColumn('email_suppressions', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('suppressed_at')->index();
            }
            
            if (!Schema::hasColumn('email_suppressions', 'metadata')) {
                $table->json('metadata')->nullable()->after('is_active');
            }
            
            // Add composite index for active suppressions
            $table->index(['email_address', 'is_active'], 'idx_email_active');
        });
    }

    private function createMailPreferences(): void
    {
        if (Schema::hasTable('mail_preferences')) {
            return;
        }

        Schema::create('mail_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email', 255)->notNullable()->index();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->boolean('transactional_allowed')->default(true);
            $table->boolean('marketing_allowed')->default(false);
            $table->boolean('newsletter_allowed')->default(false);
            $table->boolean('product_alerts_allowed')->default(false);
            $table->boolean('seller_updates_allowed')->default(true);
            $table->boolean('rfq_updates_allowed')->default(true);
            $table->string('consent_source', 50)->nullable();
            $table->string('consent_ip', 45)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'marketplace_id']);
            $table->index(['email', 'marketing_allowed']);
        });
    }

    private function createMailUnsubscribeTokens(): void
    {
        if (Schema::hasTable('mail_unsubscribe_tokens')) {
            return;
        }

        Schema::create('mail_unsubscribe_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->notNullable()->index();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->string('token_hash', 64)->notNullable()->index();
            $table->string('scope', 40)->default('global'); // global, marketplace, campaign
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['token_hash', 'used_at']);
        });
    }

    private function createMailDispatches(): void
    {
        if (Schema::hasTable('mail_dispatches')) {
            return;
        }

        Schema::create('mail_dispatches', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_uuid')->unique();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->unsignedBigInteger('sender_profile_id')->nullable()->index();
            $table->foreign('sender_profile_id')->references('id')->on('email_sender_profiles')->nullOnDelete();
            
            $table->string('mail_type', 40)->notNullable()->index(); // critical, transactional, marketing, rfq, seller
            $table->string('mail_class', 40)->nullable(); // specific mailable class
            $table->string('recipient_email', 255)->notNullable()->index();
            $table->string('recipient_name', 255)->nullable();
            $table->string('subject', 500)->notNullable();
            
            $table->string('ses_message_id', 255)->nullable()->index();
            $table->string('configuration_set', 100)->nullable();
            
            $table->string('status', 40)->default('queued')->index(); // queued, sending, sent, delivered, failed, bounced, complained
            $table->string('queue_name', 50)->nullable();
            
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['mail_type', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    private function createMailEvents(): void
    {
        if (Schema::hasTable('mail_events')) {
            return;
        }

        Schema::create('mail_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mail_dispatch_id')->nullable()->index();
            $table->foreign('mail_dispatch_id')->references('id')->on('mail_dispatches')->nullOnDelete();
            
            $table->string('ses_message_id', 255)->nullable()->index();
            $table->string('event_type', 40)->notNullable()->index(); // send, delivery, bounce, complaint, reject, open, click
            $table->timestamp('event_timestamp')->notNullable()->index();
            $table->string('recipient_email', 255)->nullable()->index();
            $table->json('payload')->notNullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'event_timestamp']);
        });
    }

    private function createMailCampaignRecipients(): void
    {
        if (Schema::hasTable('mail_campaign_recipients')) {
            return;
        }

        Schema::create('mail_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->notNullable()->index();
            $table->foreign('campaign_id')->references('id')->on('email_campaigns')->cascadeOnDelete();
            
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email', 255)->notNullable()->index();
            $table->string('recipient_name', 255)->nullable();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            
            $table->string('status', 40)->default('pending')->index(); // pending, queued, sent, delivered, bounced, complained, unsubscribed, failed
            $table->string('ses_message_id', 255)->nullable()->index();
            
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index(['email', 'status']);
        });
    }

    private function dropEnhancements(): void
    {
        // Note: In production, you would need to carefully handle column drops
        // This is a simplified version for development
        if (Schema::hasTable('email_sender_profiles')) {
            Schema::table('email_sender_profiles', function (Blueprint $table) {
                $columns = ['country_code', 'sender_type', 'ses_region', 'configuration_set', 'hourly_limit'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('email_sender_profiles', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('email_suppressions')) {
            Schema::table('email_suppressions', function (Blueprint $table) {
                $columns = ['marketplace_id', 'suppressed_at', 'is_active', 'metadata'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('email_suppressions', $column)) {
                        $table->dropColumn($column);
                    }
                }
                $table->dropIndex('idx_email_active');
            });
        }
    }
};
