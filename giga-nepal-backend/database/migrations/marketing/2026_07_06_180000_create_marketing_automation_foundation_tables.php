<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->crm();
        $this->newsletter();
        $this->email();
        $this->abandonedCarts();
        $this->otp();
        $this->whatsapp();
        $this->analytics();
        $this->settings();
    }

    public function down(): void
    {
        // Intentionally no destructive rollback for production safety. Drop tables manually only after backup and approval.
    }

    private function crm(): void
    {
        $this->create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('whatsapp_number')->nullable()->index();
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->unsignedBigInteger('region_id')->nullable()->index();
            $table->unsignedBigInteger('city_id')->nullable()->index();
            $table->string('preferred_language', 12)->default('en');
            $table->unsignedBigInteger('preferred_currency_id')->nullable();
            $table->string('customer_type')->default('retail')->index();
            $table->string('lifecycle_stage')->default('lead')->index();
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('source')->nullable()->index();
            $table->boolean('marketing_opt_in')->default(false)->index();
            $table->boolean('whatsapp_opt_in')->default(false)->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('status')->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['email', 'phone', 'whatsapp_number'], 'customer_profiles_contact_unique');
        });
        $this->create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_profile_id')->index();
            $table->string('type')->default('shipping');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('address_line1')->nullable();
            $table->text('address_line2')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        $this->create('customer_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_profile_id')->index();
            $table->json('category_interests')->nullable();
            $table->json('brand_interests')->nullable();
            $table->json('channels')->nullable();
            $table->json('newsletter_categories')->nullable();
            $table->boolean('analytics_opt_out')->default(false);
            $table->timestamps();
        });
        $this->create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('rules')->nullable();
            $table->string('type')->default('dynamic')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();
        });
        $this->create('customer_segment_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_segment_id')->index();
            $table->unsignedBigInteger('customer_profile_id')->index();
            $table->timestamp('matched_at')->nullable();
            $table->json('match_context')->nullable();
            $table->timestamps();
            $table->unique(['customer_segment_id', 'customer_profile_id'], 'segment_member_unique');
        });
        $this->create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->timestamps();
        });
        $this->create('customer_tag_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_tag_id')->index();
            $table->unsignedBigInteger('customer_profile_id')->index();
            $table->timestamps();
            $table->unique(['customer_tag_id', 'customer_profile_id'], 'tag_member_unique');
        });
        $this->create('customer_consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('channel')->index();
            $table->string('purpose')->index();
            $table->boolean('granted')->default(false);
            $table->string('source')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
        $this->create('contact_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('channel')->default('email');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        $this->create('contact_list_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_list_id')->index();
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
        $this->create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('channel')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        $this->create('marketing_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_source_id')->nullable()->index();
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('company')->nullable();
            $table->string('status')->default('new')->index();
            $table->string('interest')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        $this->create('suppression_lists', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->index();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('reason')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('suppressed_at')->nullable();
            $table->timestamps();
        });
        $this->create('unsubscribes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('channel')->default('email')->index();
            $table->string('reason')->nullable();
            $table->string('token')->nullable()->unique();
            $table->string('ip_address')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    private function newsletter(): void
    {
        $this->create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->unsignedBigInteger('region_id')->nullable()->index();
            $table->unsignedBigInteger('city_id')->nullable()->index();
            $table->string('source')->nullable();
            $table->json('interests')->nullable();
            $table->json('subscribed_categories')->nullable();
            $table->string('status')->default('subscribed')->index();
            $table->string('double_opt_in_token')->nullable()->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
        $this->create('newsletter_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        $this->create('newsletter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subject')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        $this->create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_template_id')->nullable()->index();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->string('status')->default('draft')->index();
            $table->json('targeting_rules')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
        $this->create('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_campaign_id')->index();
            $table->unsignedBigInteger('newsletter_subscriber_id')->nullable()->index();
            $table->string('email')->index();
            $table->string('status')->default('queued')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
        $this->create('newsletter_campaign_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_campaign_id')->index();
            $table->unsignedBigInteger('newsletter_campaign_recipient_id')->nullable()->index();
            $table->string('event_type')->index();
            $table->string('url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
    }

    private function email(): void
    {
        $this->create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->index();
            $table->string('subject');
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_transactional')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        $this->create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_template_id')->nullable()->index();
            $table->string('name');
            $table->string('type')->default('marketing')->index();
            $table->string('status')->default('draft')->index();
            $table->json('targeting_rules')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
        $this->create('email_campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_campaign_id')->index();
            $table->unsignedBigInteger('email_template_id')->nullable()->index();
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        $this->create('email_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_campaign_id')->index();
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->string('email')->index();
            $table->string('status')->default('queued')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
        $this->create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_template_id')->nullable()->index();
            $table->unsignedBigInteger('email_campaign_id')->nullable()->index();
            $table->string('message_type')->default('transactional')->index();
            $table->string('provider')->default('log');
            $table->string('to_email')->index();
            $table->string('subject');
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->string('status')->default('queued')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        $this->create('email_message_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_message_id')->index();
            $table->string('event_type')->index();
            $table->string('provider_event_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
        $this->create('email_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger')->index();
            $table->unsignedBigInteger('email_template_id')->nullable()->index();
            $table->json('conditions')->nullable();
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        $this->create('email_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_automation_rule_id')->index();
            $table->string('status')->default('queued')->index();
            $table->json('context')->nullable();
            $table->timestamp('ran_at')->nullable();
            $table->timestamps();
        });
        $this->create('email_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('test_mode')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    private function abandonedCarts(): void
    {
        $this->create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('cart_total', 15, 2)->default(0);
            $table->string('status')->default('open')->index();
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->unsignedBigInteger('recovered_order_id')->nullable();
            $table->timestamps();
        });
        $this->create('abandoned_cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('abandoned_cart_id')->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->timestamps();
        });
        $this->create('abandoned_cart_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('abandoned_cart_id')->index();
            $table->string('channel')->default('email');
            $table->unsignedInteger('reminder_number')->default(1);
            $table->string('status')->default('queued')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
        $this->create('abandoned_cart_recoveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('abandoned_cart_id')->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->decimal('recovered_revenue', 15, 2)->default(0);
            $table->timestamp('recovered_at')->nullable();
            $table->timestamps();
        });
    }

    private function otp(): void
    {
        $this->create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('otp_hash');
            $table->string('purpose')->default('login');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
        $this->create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('method')->default('password');
            $table->boolean('successful')->default(false);
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        $this->create('account_verification_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email')->index();
            $table->string('token_hash');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    private function whatsapp(): void
    {
        foreach (['whatsapp_templates', 'whatsapp_campaigns', 'whatsapp_campaign_recipients', 'whatsapp_messages', 'whatsapp_message_events', 'whatsapp_provider_configs', 'whatsapp_opt_ins'] as $table) {
            $this->create($table, function (Blueprint $t) use ($table) {
                $t->id();
                if ($table === 'whatsapp_templates') {
                    $t->string('name');
                    $t->string('slug')->unique();
                    $t->string('provider_template_name')->nullable();
                    $t->string('approval_status')->default('draft');
                    $t->longText('body')->nullable();
                    $t->json('variables')->nullable();
                } elseif ($table === 'whatsapp_campaigns') {
                    $t->string('name');
                    $t->string('status')->default('draft')->index();
                    $t->unsignedBigInteger('whatsapp_template_id')->nullable()->index();
                    $t->json('targeting_rules')->nullable();
                    $t->timestamp('scheduled_at')->nullable();
                    $t->timestamp('sent_at')->nullable();
                } elseif ($table === 'whatsapp_campaign_recipients') {
                    $t->unsignedBigInteger('whatsapp_campaign_id')->index();
                    $t->unsignedBigInteger('customer_profile_id')->nullable()->index();
                    $t->string('phone')->index();
                    $t->string('status')->default('queued')->index();
                } elseif ($table === 'whatsapp_messages') {
                    $t->unsignedBigInteger('whatsapp_template_id')->nullable()->index();
                    $t->unsignedBigInteger('whatsapp_campaign_id')->nullable()->index();
                    $t->string('provider')->default('manual_export');
                    $t->string('to_phone')->index();
                    $t->longText('body')->nullable();
                    $t->string('status')->default('queued')->index();
                    $t->json('metadata')->nullable();
                } elseif ($table === 'whatsapp_message_events') {
                    $t->unsignedBigInteger('whatsapp_message_id')->index();
                    $t->string('event_type')->index();
                    $t->json('metadata')->nullable();
                    $t->timestamp('occurred_at')->nullable();
                } elseif ($table === 'whatsapp_provider_configs') {
                    $t->string('provider')->unique();
                    $t->boolean('is_enabled')->default(false);
                    $t->boolean('test_mode')->default(true);
                    $t->json('settings')->nullable();
                } elseif ($table === 'whatsapp_opt_ins') {
                    $t->string('phone')->index();
                    $t->unsignedBigInteger('customer_profile_id')->nullable()->index();
                    $t->boolean('opted_in')->default(false);
                    $t->string('source')->nullable();
                    $t->timestamp('opted_in_at')->nullable();
                    $t->timestamp('opted_out_at')->nullable();
                }
                $t->timestamps();
            });
        }
    }

    private function analytics(): void
    {
        $simple = [
            'analytics_events' => ['event_name', 'event_type'], 'product_views' => ['product_id', 'user_id'], 'product_searches' => ['query', 'user_id'],
            'category_views' => ['category_id', 'user_id'], 'add_to_cart_events' => ['product_id', 'user_id'], 'checkout_events' => ['checkout_step', 'user_id'],
            'order_analytics' => ['order_id', 'marketplace_id'], 'campaign_analytics' => ['campaign_type', 'campaign_id'], 'trending_products' => ['product_id', 'score'],
            'trending_categories' => ['category_id', 'score'], 'top_search_terms' => ['term', 'search_count'], 'regional_sales_reports' => ['country_id', 'region_id'],
            'customer_activity_logs' => ['customer_profile_id', 'activity_type'],
        ];
        foreach ($simple as $table => $cols) {
            $this->create($table, function (Blueprint $t) use ($table, $cols) {
                $t->id();
                foreach ($cols as $col) {
                    if (str_ends_with($col, '_id') || in_array($col, ['product_id', 'category_id', 'user_id', 'marketplace_id', 'campaign_id', 'customer_profile_id', 'country_id', 'region_id'], true)) {
                        $t->unsignedBigInteger($col)->nullable()->index();
                    } elseif (str_contains($col, 'count')) {
                        $t->unsignedInteger($col)->default(0);
                    } elseif ($col === 'score') {
                        $t->decimal($col, 12, 4)->default(0);
                    } else {
                        $t->string($col)->nullable()->index();
                    }
                }
                if (in_array($table, ['order_analytics', 'regional_sales_reports', 'campaign_analytics'], true)) {
                    $t->decimal('amount', 15, 2)->default(0);
                }
                $t->json('metadata')->nullable();
                $t->timestamp('occurred_at')->nullable()->index();
                $t->timestamps();
            });
        }
    }

    private function settings(): void
    {
        foreach (['marketing_settings', 'notification_settings', 'analytics_settings'] as $table) {
            $this->create($table, function (Blueprint $t) {
                $t->id();
                $t->string('key')->unique();
                $t->json('value')->nullable();
                $t->string('group')->nullable()->index();
                $t->boolean('is_public')->default(false);
                $t->timestamps();
            });
        }
    }

    private function create(string $name, callable $callback): void
    {
        if (! Schema::hasTable($name)) {
            Schema::create($name, $callback);
        }
    }
};
