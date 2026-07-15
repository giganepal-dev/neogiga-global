<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds comprehensive currency, exchange rate, email provider, and notification infrastructure
     * while preserving existing data and maintaining backward compatibility.
     */
    public function up(): void
    {
        // Extend currencies table with additional fields for multi-currency support
        if (!Schema::hasColumn('currencies', 'native_symbol')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->string('native_symbol')->nullable()->after('symbol');
                $table->string('currency_position', 10)->default('before')->after('decimal_places'); // before, after
                $table->string('thousands_separator', 5)->default(',')->after('currency_position');
                $table->string('decimal_separator', 5)->default('.')->after('thousands_separator');
            });
        }

        // Exchange rate providers configuration
        if (!Schema::hasTable('exchange_rate_providers')) {
            Schema::create('exchange_rate_providers', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // fawaz_exchange_api, manual, etc.
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->string('api_endpoint')->nullable();
                $table->json('config')->nullable(); // Provider-specific config
                $table->boolean('is_active')->default(true);
                $table->integer('priority')->default(100); // Lower = higher priority
                $table->timestamp('last_successful_fetch_at')->nullable();
                $table->timestamp('last_failed_fetch_at')->nullable();
                $table->integer('consecutive_failures')->default(0);
                $table->timestamps();
                
                $table->index('is_active');
                $table->index('priority');
            });
        }

        // Enhance exchange_rates table with additional tracking fields
        if (!Schema::hasColumn('currency_exchange_rates', 'provider_id')) {
            Schema::table('currency_exchange_rates', function (Blueprint $table) {
                $table->foreignId('provider_id')->nullable()->after('source')
                    ->constrained('exchange_rate_providers')->nullOnDelete();
                $table->decimal('previous_rate', 15, 6)->nullable()->after('rate');
                $table->decimal('rate_change_percent', 8, 4)->nullable()->after('previous_rate');
                $table->boolean('requires_approval')->default(false)->after('is_active');
                $table->boolean('is_approved')->default(false)->after('requires_approval');
                $table->foreignId('approved_by')->nullable()->after('is_approved')
                    ->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable()->after('approved_at');
                $table->timestamp('activated_at')->nullable()->after('approved_at');
                $table->text('rejection_reason')->nullable()->after('activated_at');
                $table->foreignId('rejected_by')->nullable()->after('rejection_reason')
                    ->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
                $table->string('status', 20)->default('pending')->after('rejected_at'); // pending, approved, active, rejected, expired, superseded
            });
        }

        // Exchange rate history for audit trail
        if (!Schema::hasTable('exchange_rate_histories')) {
            Schema::create('exchange_rate_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exchange_rate_id')->constrained()->cascadeOnDelete();
                $table->string('action', 50); // created, approved, activated, rejected, superseded
                $table->decimal('old_rate', 15, 6)->nullable();
                $table->decimal('new_rate', 15, 6)->nullable();
                $table->string('status_before', 20)->nullable();
                $table->string('status_after', 20)->nullable();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->text('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index('exchange_rate_id');
                $table->index('action');
                $table->index('created_at');
            });
        }

        // Marketplace currency settings for regional pricing policies
        if (!Schema::hasTable('marketplace_currency_settings')) {
            Schema::create('marketplace_currency_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketplace_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('pricing_mode', 30)->default('flexible'); // fixed, flexible, landed_cost, price_list, markup_over_exchange
                $table->string('base_currency', 3)->default('USD');
                $table->foreignId('exchange_rate_provider_id')->nullable()->constrained('exchange_rate_providers')->nullOnDelete();
                $table->string('exchange_update_frequency', 20)->default('90_days'); // disabled, manual, daily, weekly, monthly, 90_days, custom
                $table->integer('custom_update_interval_days')->nullable();
                $table->decimal('fixed_exchange_rate', 15, 6)->nullable(); // For fixed rate mode
                $table->decimal('exchange_rate_adjustment_percent', 8, 4)->default(0); // Additional adjustment %
                $table->decimal('regional_markup_percent', 8, 4)->default(0); // Markup over exchange rate
                $table->decimal('minimum_margin_percent', 8, 4)->default(0);
                $table->decimal('maximum_price_movement_percent', 8, 4)->default(10); // Max change per update
                $table->string('rounding_method', 20)->default('nearest'); // nearest, up, down, psychological
                $table->decimal('rounding_increment', 10, 2)->default(0.99); // e.g., 0.99 for psychological pricing
                $table->boolean('auto_activate_rates')->default(false);
                $table->boolean('require_rate_approval')->default(true);
                $table->decimal('staleness_threshold_hours', 10, 2)->default(2160); // 90 days = 2160 hours
                $table->string('fallback_rate_source', 50)->default('last_known_good');
                $table->boolean('allow_product_override')->default(true);
                $table->boolean('allow_category_override')->default(true);
                $table->boolean('allow_brand_override')->default(true);
                $table->boolean('allow_seller_override')->default(true);
                $table->boolean('tax_inclusive_display')->default(false);
                $table->timestamp('last_rate_update_at')->nullable();
                $table->timestamp('next_scheduled_update_at')->nullable();
                $table->json('b2b_pricing_rules')->nullable();
                $table->json('promotional_pricing_rules')->nullable();
                $table->timestamps();
                
                $table->index('pricing_mode');
                $table->index('exchange_update_frequency');
            });
        }

        // Regional price policies for granular control
        if (!Schema::hasTable('regional_price_policies')) {
            Schema::create('regional_price_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
                $table->string('policy_type', 30); // product, category, brand, seller
                $table->foreignId('policyable_id')->nullable(); // Product ID, Category ID, etc.
                $table->string('policyable_type')->nullable(); // App\Models\Marketplace\Product, etc.
                $table->string('pricing_mode', 30)->default('inherit'); // inherit, fixed, flexible, override
                $table->decimal('fixed_price', 15, 2)->nullable();
                $table->decimal('markup_percent', 8, 4)->nullable();
                $table->decimal('minimum_price', 15, 2)->nullable();
                $table->decimal('maximum_price', 15, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('effective_from')->nullable();
                $table->timestamp('effective_until')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->index(['marketplace_id', 'policy_type']);
                $table->index(['policyable_type', 'policyable_id']);
                $table->index('is_active');
            });
        }

        // Regional prices table for storing converted prices
        if (!Schema::hasTable('regional_prices')) {
            Schema::create('regional_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
                $table->decimal('base_amount', 15, 4); // Original amount
                $table->string('base_currency', 3); // Original currency
                $table->decimal('normalized_usd_amount', 15, 4)->nullable(); // Normalized to USD
                $table->decimal('regional_amount', 15, 2); // Converted regional amount
                $table->string('regional_currency', 3); // Regional currency
                $table->decimal('exchange_rate_used', 15, 6)->nullable();
                $table->foreignId('exchange_rate_id')->nullable()->constrained('currency_exchange_rates')->nullOnDelete();
                $table->string('exchange_rate_source')->nullable(); // Provider name
                $table->timestamp('conversion_timestamp')->nullable();
                $table->string('pricing_policy_used', 50)->nullable();
                $table->string('rounding_rule_used', 30)->nullable();
                $table->boolean('is_manual_override')->default(false);
                $table->text('override_reason')->nullable();
                $table->foreignId('override_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('price_valid_from')->nullable();
                $table->timestamp('price_valid_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->unique(['product_id', 'marketplace_id', 'product_variant_id', 'regional_currency', 'is_active'], 'regional_prices_unique_active');
                $table->index(['marketplace_id', 'regional_currency']);
                $table->index(['product_id', 'is_active']);
                $table->index('conversion_timestamp');
            });
        }

        // Regional price history for audit and rollback
        if (!Schema::hasTable('regional_price_histories')) {
            Schema::create('regional_price_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('regional_price_id')->constrained()->cascadeOnDelete();
                $table->string('change_type', 30); // created, updated, overridden, reverted, expired
                $table->decimal('old_amount', 15, 2)->nullable();
                $table->decimal('new_amount', 15, 2)->nullable();
                $table->decimal('old_exchange_rate', 15, 6)->nullable();
                $table->decimal('new_exchange_rate', 15, 6)->nullable();
                $table->string('reason')->nullable();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index('regional_price_id');
                $table->index('change_type');
                $table->index('created_at');
            });
        }

        // Currency conversion logs for debugging
        if (!Schema::hasTable('currency_conversion_logs')) {
            Schema::create('currency_conversion_logs', function (Blueprint $table) {
                $table->id();
                $table->string('context', 50); // cart, checkout, product_view, api
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('from_currency', 3);
                $table->string('to_currency', 3);
                $table->decimal('from_amount', 15, 4);
                $table->decimal('to_amount', 15, 4);
                $table->decimal('exchange_rate', 15, 6);
                $table->foreignId('exchange_rate_id')->nullable()->constrained('currency_exchange_rates')->nullOnDelete();
                $table->string('pricing_mode', 30)->nullable();
                $table->boolean('success')->default(true);
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable(); // Additional context
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
                
                $table->index(['context', 'created_at']);
                $table->index(['marketplace_id', 'created_at']);
                $table->index('success');
            });
        }

        // Price approval requests workflow
        if (!Schema::hasTable('price_approval_requests')) {
            Schema::create('price_approval_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
                $table->string('request_type', 30); // exchange_rate, regional_price, policy_change
                $table->foreignId('requestable_id')->nullable();
                $table->string('requestable_type')->nullable();
                $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
                $table->text('description')->nullable();
                $table->json('changes')->nullable(); // Before/after snapshot
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();
                
                $table->index(['marketplace_id', 'status']);
                $table->index(['request_type', 'status']);
                $table->index('created_at');
            });
        }

        // Email providers configuration
        if (!Schema::hasTable('email_providers')) {
            Schema::create('email_providers', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // resend, ses, smtp, mailgun, etc.
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->string('provider_type', 20); // api, smtp
                $table->json('config')->nullable(); // Encrypted or reference to env vars
                $table->string('from_email')->nullable();
                $table->string('from_name')->nullable();
                $table->string('reply_to_email')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('priority')->default(100); // Lower = higher priority
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_fallback')->default(false);
                $table->integer('max_retries')->default(3);
                $table->integer('retry_delay_seconds')->default(30);
                $table->integer('timeout_seconds')->default(30);
                $table->boolean('supports_attachments')->default(true);
                $table->boolean('supports_tracking')->default(true);
                $table->timestamp('last_successful_send_at')->nullable();
                $table->timestamp('last_failed_send_at')->nullable();
                $table->integer('consecutive_failures')->default(0);
                $table->integer('total_sent_count')->default(0);
                $table->integer('total_failed_count')->default(0);
                $table->timestamps();
                
                $table->index('is_active');
                $table->index('priority');
                $table->index('is_primary');
            });
        }

        // Email delivery logs
        if (!Schema::hasTable('email_delivery_logs')) {
            Schema::create('email_delivery_logs', function (Blueprint $table) {
                $table->id();
                $table->string('message_id')->nullable(); // Provider message ID
                $table->string('idempotency_key', 64)->unique(); // Prevent duplicates
                $table->foreignId('email_provider_id')->nullable()->constrained('email_providers')->nullOnDelete();
                $table->string('event_type', 30); // registration, order_confirmation, otp, etc.
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('to_email');
                $table->string('to_name')->nullable();
                $table->string('subject');
                $table->text('body_html')->nullable();
                $table->text('body_text')->nullable();
                $table->string('template_name')->nullable();
                $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();
                $table->json('headers')->nullable();
                $table->json('attachments')->nullable(); // Metadata only, not content
                $table->string('status', 20)->default('queued'); // queued, processing, sent, accepted, delivered, deferred, bounced, complained, rejected, failed, suppressed
                $table->text('error_message')->nullable();
                $table->integer('attempt_count')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('bounced_at')->nullable();
                $table->string('bounce_type')->nullable(); // hard, soft
                $table->timestamp('complained_at')->nullable();
                $table->boolean('is_transactional')->default(true);
                $table->boolean('is_marketing')->default(false);
                $table->json('metadata')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
                
                $table->index(['event_type', 'created_at']);
                $table->index(['marketplace_id', 'created_at']);
                $table->index(['user_id', 'created_at']);
                $table->index('status');
                $table->index('created_at');
            });
        }

        // Email suppression list
        if (!Schema::hasTable('email_suppressions')) {
            Schema::create('email_suppressions', function (Blueprint $table) {
                $table->id();
                $table->string('email')->unique();
                $table->string('suppression_type', 20); // hard_bounce, spam_complaint, invalid, manual_block, unsubscribed_marketing
                $table->text('reason')->nullable();
                $table->foreignId('email_delivery_log_id')->nullable()->constrained('email_delivery_logs')->nullOnDelete();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->boolean('is_global')->default(false); // Applies to all marketplaces
                $table->boolean('allow_transactional')->default(true); // Still allow transactional emails
                $table->timestamp('expires_at')->nullable(); // For temporary suppressions
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                $table->index('email');
                $table->index('suppression_type');
                $table->index('is_global');
            });
        }

        // Email templates
        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('event_key')->unique(); // Internal identifier
                $table->text('description')->nullable();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete(); // Null = global default
                $table->string('language', 10)->default('en');
                $table->string('country', 2)->nullable();
                $table->string('customer_type', 20)->nullable(); // b2b, b2c, null = all
                $table->string('subject');
                $table->longText('body_html');
                $table->longText('body_text')->nullable();
                $table->json('variables')->nullable(); // Available template variables
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->integer('version')->default(1);
                $table->foreignId('parent_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                
                $table->index(['event_key', 'marketplace_id']);
                $table->index(['language', 'is_active']);
                $table->index('is_default');
            });
        }

        // Firebase settings
        if (!Schema::hasTable('firebase_settings')) {
            Schema::create('firebase_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketplace_id')->nullable()->unique()->constrained()->nullOnDelete(); // Null = global default
                $table->boolean('is_enabled')->default(false);
                $table->string('project_id')->nullable();
                $table->json('service_account_config')->nullable(); // Encrypted or env reference
                $table->json('web_app_config')->nullable(); // Public config for frontend
                $table->string('web_push_public_key')->nullable();
                $table->string('sender_id')->nullable();
                $table->string('default_icon')->nullable();
                $table->string('default_click_action')->nullable();
                $table->string('default_sound')->default('default');
                $table->string('default_marketplace_topic')->nullable();
                $table->integer('token_expiration_days')->default(90);
                $table->timestamp('last_test_at')->nullable();
                $table->boolean('last_test_success')->default(false);
                $table->text('last_test_error')->nullable();
                $table->timestamps();
                
                $table->index('is_enabled');
            });
        }

        // Firebase device tokens
        if (!Schema::hasTable('firebase_device_tokens')) {
            Schema::create('firebase_device_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('guest_session_id')->nullable(); // For guest users
                $table->string('token'); // FCM token
                $table->string('device_type', 20)->nullable(); // web, android, ios
                $table->string('browser')->nullable();
                $table->string('os')->nullable();
                $table->string('os_version')->nullable();
                $table->string('app_version')->nullable();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->string('language', 10)->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->boolean('consent_given')->default(true);
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->string('revocation_reason')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'is_enabled']);
                $table->index(['marketplace_id', 'is_enabled']);
                $table->index('token');
                $table->index('expires_at');
            });
        }

        // Notification preferences
        if (!Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete(); // Null = global
                $table->string('notification_type', 50); // order_updates, promotions, price_alerts, back_in_stock, etc.
                $table->boolean('email_enabled')->default(true);
                $table->boolean('push_enabled')->default(true);
                $table->boolean('sms_enabled')->default(false); // For future SMS integration
                $table->boolean('whatsapp_enabled')->default(false); // For future WhatsApp integration
                $table->boolean('is_mandatory')->default(false); // Cannot be disabled (security, essential transactional)
                $table->timestamps();
                
                $table->unique(['user_id', 'marketplace_id', 'notification_type']);
                $table->index(['user_id', 'email_enabled']);
                $table->index(['user_id', 'push_enabled']);
            });
        }

        // Notification delivery logs
        if (!Schema::hasTable('notification_delivery_logs')) {
            Schema::create('notification_delivery_logs', function (Blueprint $table) {
                $table->id();
                $table->string('notification_type', 50);
                $table->string('channel', 20); // database, email, firebase, sms, whatsapp
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('firebase_device_token_id')->nullable()->constrained('firebase_device_tokens')->nullOnDelete();
                $table->foreignId('email_delivery_log_id')->nullable()->constrained('email_delivery_logs')->nullOnDelete();
                $table->string('title')->nullable();
                $table->text('body')->nullable();
                $table->string('action_url')->nullable();
                $table->json('data_payload')->nullable();
                $table->string('status', 20)->default('queued'); // queued, processing, sent, delivered, failed, skipped
                $table->text('error_message')->nullable();
                $table->integer('attempt_count')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->boolean('is_mandatory')->default(false);
                $table->string('deduplication_key', 64)->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['notification_type', 'created_at']);
                $table->index(['user_id', 'created_at']);
                $table->index(['marketplace_id', 'created_at']);
                $table->index('status');
                $table->index('channel');
            });
        }

        // Admin audit logs for currency and communication changes
        if (!Schema::hasTable('currency_communication_audit_logs')) {
            Schema::create('currency_communication_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('audit_type', 50); // exchange_rate_retrieved, exchange_rate_approved, email_provider_changed, firebase_config_updated, etc.
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->string('entity_type', 50)->nullable(); // exchange_rate, email_provider, firebase_setting, etc.
                $table->foreignId('entity_id')->nullable();
                $table->string('action', 50); // created, updated, approved, rejected, activated, deactivated
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->text('reason')->nullable();
                $table->string('correlation_id', 64)->nullable();
                $table->timestamps();
                
                $table->index(['audit_type', 'created_at']);
                $table->index(['marketplace_id', 'created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_communication_audit_logs');
        Schema::dropIfExists('notification_delivery_logs');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('firebase_device_tokens');
        Schema::dropIfExists('firebase_settings');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_suppressions');
        Schema::dropIfExists('email_delivery_logs');
        Schema::dropIfExists('email_providers');
        Schema::dropIfExists('price_approval_requests');
        Schema::dropIfExists('currency_conversion_logs');
        Schema::dropIfExists('regional_price_histories');
        Schema::dropIfExists('regional_prices');
        Schema::dropIfExists('regional_price_policies');
        Schema::dropIfExists('marketplace_currency_settings');
        Schema::dropIfExists('exchange_rate_histories');
        
        // Remove added columns from existing tables
        if (Schema::hasColumn('currency_exchange_rates', 'provider_id')) {
            Schema::table('currency_exchange_rates', function (Blueprint $table) {
                $table->dropForeign(['provider_id']);
                $table->dropColumn([
                    'provider_id', 'previous_rate', 'rate_change_percent',
                    'requires_approval', 'is_approved', 'approved_by', 'approved_at',
                    'activated_at', 'rejection_reason', 'rejected_by', 'rejected_at', 'status'
                ]);
            });
        }
        
        Schema::dropIfExists('exchange_rate_providers');
        
        if (Schema::hasColumn('currencies', 'native_symbol')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->dropColumn(['native_symbol', 'currency_position', 'thousands_separator', 'decimal_separator']);
            });
        }
    }
};
