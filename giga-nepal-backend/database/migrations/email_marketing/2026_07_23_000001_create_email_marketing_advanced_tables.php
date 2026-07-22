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
        $this->createSubscribersTable();
        $this->createGroupsTable();
        $this->createGroupSubscriberPivot();
        $this->createTagsTable();
        $this->createSubscriberTagsPivot();
        $this->createSegmentsTable();
        $this->createSegmentRulesTable();
        $this->createCountryGroupsTable();
        $this->createSenderProfilesTable();
        $this->createProviderConfigsExtension();
        $this->createImportJobsTables();
        $this->createSuppressionListExtension();
        $this->createConsentLogsTable();
        $this->createPreferencesTable();
        $this->createAutomationWorkflowsTable();
        $this->createAuditLogsTable();
        $this->addIndexes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_audit_logs');
        Schema::dropIfExists('email_automation_steps');
        Schema::dropIfExists('email_automation_workflows');
        Schema::dropIfExists('email_preferences');
        Schema::dropIfExists('email_consent_logs');
        Schema::dropIfExists('email_import_row_errors');
        Schema::dropIfExists('email_import_rows');
        Schema::dropIfExists('email_import_files');
        Schema::dropIfExists('email_import_jobs');
        Schema::dropIfExists('email_import_mappings');
        Schema::dropIfExists('email_country_groups');
        Schema::dropIfExists('email_subscriber_tags');
        Schema::dropIfExists('email_tags');
        Schema::dropIfExists('email_segment_rules');
        Schema::dropIfExists('email_segments');
        Schema::dropIfExists('email_group_subscriber');
        Schema::dropIfExists('email_groups');
        Schema::dropIfExists('email_senders_extension');
        Schema::dropIfExists('email_provider_configs_extension');
        $this->dropSubscribersTable();
    }

    private function createSubscribersTable(): void
    {
        if (Schema::hasTable('email_subscribers')) {
            return;
        }

        Schema::create('email_subscribers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('email')->index();
            $table->string('normalized_email')->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('job_title')->nullable();
            
            // Subscriber classification
            $table->string('subscriber_type', 50)->default('newsletter_subscriber')->index();
            $table->string('customer_type', 50)->nullable()->index();
            $table->string('source', 50)->default('manual')->index();
            $table->string('source_reference')->nullable();
            
            // Relationships
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->unsignedBigInteger('country_id')->nullable()->index();
            
            // Regional data
            $table->string('region_id')->nullable()->index();
            $table->string('country_code', 2)->nullable()->index();
            $table->string('state_or_province')->nullable();
            $table->string('city')->nullable();
            $table->string('preferred_language', 12)->default('en');
            $table->string('preferred_currency', 3)->default('USD');
            $table->string('timezone', 50)->default('UTC');
            
            // Status and consent
            $table->string('status', 20)->default('subscribed')->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            
            // Engagement tracking
            $table->timestamp('last_email_sent_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->decimal('engagement_score', 5, 2)->default(0)->index();
            
            // Aggregate statistics
            $table->unsignedInteger('total_sent')->default(0);
            $table->unsignedInteger('total_delivered')->default(0);
            $table->unsignedInteger('total_opened')->default(0);
            $table->unsignedInteger('total_clicked')->default(0);
            $table->unsignedInteger('total_bounced')->default(0);
            $table->unsignedInteger('total_complaints')->default(0);
            
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique(['normalized_email', 'marketplace_id'], 'unique_subscriber_per_marketplace');
        });
    }

    private function dropSubscribersTable(): void
    {
        Schema::dropIfExists('email_subscribers');
    }

    private function createGroupsTable(): void
    {
        if (Schema::hasTable('email_groups')) {
            return;
        }

        Schema::create('email_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Group type
            $table->string('group_type', 30)->default('custom')->index(); // country, custom, dynamic
            $table->string('country_code', 2)->nullable()->index();
            
            // Regional settings
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->string('default_language', 12)->default('en');
            $table->string('default_currency', 3)->default('USD');
            
            // Sender configuration
            $table->unsignedBigInteger('sender_profile_id')->nullable();
            $table->string('provider', 50)->nullable()->index();
            
            // Compliance
            $table->text('physical_address')->nullable();
            $table->text('unsubscribe_footer')->nullable();
            
            // Sending limits
            $table->unsignedInteger('daily_limit')->default(1000);
            $table->unsignedInteger('hourly_limit')->default(100);
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false);
            
            $table->timestamps();
        });
    }

    private function createGroupSubscriberPivot(): void
    {
        if (Schema::hasTable('email_group_subscriber')) {
            return;
        }

        Schema::create('email_group_subscriber', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id')->index();
            $table->unsignedBigInteger('group_id')->index();
            $table->string('assignment_source', 50)->default('manual')->index(); // manual, import, auto, rule
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->json('assignment_context')->nullable();
            $table->timestamps();
            
            $table->unique(['subscriber_id', 'group_id'], 'unique_subscriber_group');
            $table->foreign('subscriber_id')->references('id')->on('email_subscribers')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('email_groups')->onDelete('cascade');
        });
    }

    private function createTagsTable(): void
    {
        if (Schema::hasTable('email_tags')) {
            return;
        }

        Schema::create('email_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    private function createSubscriberTagsPivot(): void
    {
        if (Schema::hasTable('email_subscriber_tags')) {
            return;
        }

        Schema::create('email_subscriber_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id')->index();
            $table->unsignedBigInteger('tag_id')->index();
            $table->string('source', 50)->default('manual');
            $table->timestamp('added_at')->nullable();
            $table->timestamps();
            
            $table->unique(['subscriber_id', 'tag_id']);
            $table->foreign('subscriber_id')->references('id')->on('email_subscribers')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('email_tags')->onDelete('cascade');
        });
    }

    private function createSegmentsTable(): void
    {
        if (Schema::hasTable('email_segments')) {
            return;
        }

        Schema::create('email_segments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Segment type
            $table->string('segment_type', 30)->default('dynamic')->index(); // static, dynamic, smart
            $table->unsignedBigInteger('created_by')->nullable();
            
            // Matching rules (JSON)
            $table->json('rules')->nullable();
            $table->json('exclusions')->nullable();
            
            // Recalculation
            $table->string('recalc_strategy', 30)->default('on_demand'); // on_demand, scheduled, real_time
            $table->timestamp('last_recalculated_at')->nullable();
            $table->unsignedInteger('subscriber_count')->default(0);
            
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    private function createSegmentRulesTable(): void
    {
        if (Schema::hasTable('email_segment_rules')) {
            return;
        }

        Schema::create('email_segment_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('segment_id')->index();
            $table->string('field', 50)->index(); // country_code, subscriber_type, engagement_score, etc.
            $table->string('operator', 20)->default('equals'); // equals, not_equals, contains, greater_than, less_than, etc.
            $table->json('value')->nullable();
            $table->string('boolean_operator', 10)->default('and'); // and, or
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('segment_id')->references('id')->on('email_segments')->onDelete('cascade');
        });
    }

    private function createCountryGroupsTable(): void
    {
        if (Schema::hasTable('email_country_groups')) {
            return;
        }

        Schema::create('email_country_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Nepal, India, etc.
            $table->string('code', 10)->unique(); // NP, IN, BD, LK, AU, BT, GLOBAL, UNASSIGNED
            $table->json('country_codes')->nullable(); // ['NP'] or ['IN', 'NP'] for regional
            $table->unsignedBigInteger('primary_group_id')->nullable();
            $table->string('priority', 20)->default('medium'); // high, medium, low
            $table->boolean('is_auto_assignable')->default(true);
            $table->json('assignment_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('primary_group_id')->references('id')->on('email_groups');
        });
    }

    private function createSenderProfilesTable(): void
    {
        if (Schema::hasTable('email_senders_extension')) {
            return;
        }

        Schema::create('email_senders_extension', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_profile_id')->index();
            $table->string('sender_name');
            $table->string('sender_email')->index();
            $table->string('reply_to_email')->nullable();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->unsignedBigInteger('country_group_id')->nullable();
            $table->string('provider', 50)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_default')->default(false);
            $table->json('verification_details')->nullable();
            $table->timestamps();
        });
    }

    private function createProviderConfigsExtension(): void
    {
        if (Schema::hasTable('email_provider_configs_extension')) {
            return;
        }

        Schema::create('email_provider_configs_extension', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->index(); // resend, ses, smtp
            $table->string('config_key', 100)->unique();
            $table->json('settings')->nullable();
            $table->text('encrypted_credentials')->nullable();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->unsignedBigInteger('country_group_id')->nullable();
            $table->string('scope', 30)->default('global'); // global, marketplace, country_group
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_fallback')->default(false);
            $table->unsignedInteger('rate_limit_per_minute')->default(60);
            $table->unsignedInteger('daily_limit')->default(10000);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_health_check_at')->nullable();
            $table->string('health_status', 20)->default('unknown');
            $table->timestamps();
        });
    }

    private function createImportJobsTables(): void
    {
        // Import jobs
        if (! Schema::hasTable('email_import_jobs')) {
            Schema::create('email_import_jobs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('target_group_id')->nullable();
                $table->string('assignment_mode', 30)->default('manual'); // manual, auto_by_country
                $table->string('duplicate_handling', 30)->default('skip'); // skip, update, merge
                $table->boolean('validate_emails')->default(true);
                $table->boolean('check_suppression')->default(true);
                $table->boolean('respect_unsubscribed')->default(true);
                $table->json('column_mapping')->nullable();
                $table->json('default_values')->nullable();
                $table->string('status', 30)->default('draft')->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // Import files
        if (! Schema::hasTable('email_import_files')) {
            Schema::create('email_import_files', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('import_job_id')->index();
                $table->string('original_filename');
                $table->string('stored_path');
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('file_hash', 64)->index();
                $table->json('sheets')->nullable();
                $table->string('selected_sheet')->nullable();
                $table->unsignedInteger('total_rows')->default(0);
                $table->timestamps();
                
                $table->foreign('import_job_id')->references('id')->on('email_import_jobs')->onDelete('cascade');
            });
        }

        // Import rows
        if (! Schema::hasTable('email_import_rows')) {
            Schema::create('email_import_rows', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('import_file_id')->index();
                $table->unsignedInteger('row_number')->index();
                $table->json('raw_data')->nullable();
                $table->json('mapped_data')->nullable();
                $table->string('status', 30)->default('pending')->index();
                $table->unsignedBigInteger('subscriber_id')->nullable();
                $table->string('processing_error')->nullable();
                $table->timestamps();
                
                $table->foreign('import_file_id')->references('id')->on('email_import_files')->onDelete('cascade');
            });
        }

        // Import row errors
        if (! Schema::hasTable('email_import_row_errors')) {
            Schema::create('email_import_row_errors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('import_row_id')->index();
                $table->string('error_type', 50)->index();
                $table->string('error_field')->nullable();
                $table->text('error_message');
                $table->timestamps();
                
                $table->foreign('import_row_id')->references('id')->on('email_import_rows')->onDelete('cascade');
            });
        }

        // Import mappings (saved templates)
        if (! Schema::hasTable('email_import_mappings')) {
            Schema::create('email_import_mappings', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->json('mapping')->nullable();
                $table->json('default_values')->nullable();
                $table->boolean('is_system')->default(false);
                $table->timestamps();
            });
        }
    }

    private function createSuppressionListExtension(): void
    {
        if (! Schema::hasTable('email_suppressions_extension')) {
            Schema::create('email_suppressions_extension', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subscriber_id')->nullable()->index();
                $table->string('email')->index();
                $table->string('reason', 50)->index(); // bounce_hard, bounce_soft, complaint, unsubscribe, manual
                $table->string('source', 50)->default('system');
                $table->unsignedBigInteger('campaign_id')->nullable();
                $table->unsignedBigInteger('message_id')->nullable();
                $table->text('details')->nullable();
                $table->boolean('is_global')->default(false);
                $table->boolean('is_permanent')->default(false);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                
                $table->unique(['email', 'reason'], 'unique_suppression_per_reason');
            });
        }
    }

    private function createConsentLogsTable(): void
    {
        if (Schema::hasTable('email_consent_logs')) {
            return;
        }

        Schema::create('email_consent_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id')->nullable()->index();
            $table->string('email')->index();
            $table->string('consent_type', 50)->index(); // promotional, transactional, newsletter, etc.
            $table->string('status', 20)->default('pending')->index(); // granted, denied, withdrawn
            $table->string('source', 50)->default('manual');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('policy_version', 20)->nullable();
            $table->string('jurisdiction', 50)->nullable();
            $table->unsignedBigInteger('marketplace_id')->nullable();
            $table->json('evidence')->nullable();
            $table->timestamp('effective_at')->nullable();
            $table->timestamps();
        });
    }

    private function createPreferencesTable(): void
    {
        if (Schema::hasTable('email_preferences')) {
            return;
        }

        Schema::create('email_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id')->unique()->index();
            $table->string('email')->unique();
            $table->json('categories')->nullable(); // product_updates, promotions, newsletters, events
            $table->string('preferred_language', 12)->default('en');
            $table->string('preferred_format', 20)->default('html'); // html, text, both
            $table->string('frequency', 30)->default('standard'); // instant, daily_digest, weekly
            $table->boolean('all_marketing_opt_out')->default(false)->index();
            $table->string('time_zone', 50)->default('UTC');
            $table->timestamp('updated_by_recipient_at')->nullable();
            $table->string('preference_token', 64)->unique()->nullable()->index();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
            
            $table->foreign('subscriber_id')->references('id')->on('email_subscribers')->onDelete('cascade');
        });
    }

    private function createAutomationWorkflowsTable(): void
    {
        if (Schema::hasTable('email_automation_workflows')) {
            return;
        }

        Schema::create('email_automation_workflows', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->index();
            $table->string('trigger_type', 50)->index(); // subscriber_created, purchase_made, cart_abandoned, etc.
            $table->json('trigger_conditions')->nullable();
            $table->json('filters')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('email_automation_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->index();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedSmallInteger('delay_minutes')->default(0);
            $table->unsignedSmallInteger('step_order')->default(0);
            $table->json('conditions')->nullable();
            $table->string('action_type', 50)->default('send_email');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('workflow_id')->references('id')->on('email_automation_workflows')->onDelete('cascade');
        });
    }

    private function createAuditLogsTable(): void
    {
        if (Schema::hasTable('email_audit_logs')) {
            return;
        }

        Schema::create('email_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->index();
            $table->string('entity_type', 50)->index(); // subscriber, campaign, group, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    private function addIndexes(): void
    {
        // Additional composite indexes for performance
        if (Schema::hasTable('email_subscribers')) {
            try {
                Schema::table('email_subscribers', function (Blueprint $table) {
                    $table->index(['status', 'country_code']);
                    $table->index(['subscriber_type', 'status']);
                    $table->index(['created_at', 'status']);
                });
            } catch (\Exception $e) {
                // Indexes may already exist
            }
        }
    }
};
