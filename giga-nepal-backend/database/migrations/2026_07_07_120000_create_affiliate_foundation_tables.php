<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Affiliate / referral foundation (NEOGIGA_AFFILIATE_ADAPTATION_COMMAND).
 * Additive only — references users/vendors/orders read-only via nullable FKs.
 * Commission ledger is append-only in intent: `commission_amount` and
 * `order_total_snapshot` are never mutated after insert; only `status`
 * transitions and reversal entries are used. No auto-payout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('vendor_id')->nullable()->index(); // soft link to vendors
            $table->string('display_name');
            $table->string('email')->nullable()->index();
            $table->string('status')->default('pending')->index(); // pending|approved|suspended|rejected
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->string('default_currency', 3)->default('USD');
            $table->string('payout_method')->nullable();
            $table->json('payout_details')->nullable();
            $table->decimal('total_earned', 18, 2)->default(0);
            $table->decimal('total_paid', 18, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('landing_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('click_count')->default(0);
            $table->unsignedBigInteger('signup_count')->default(0);
            $table->unsignedBigInteger('order_count')->default(0);
            $table->timestamps();
        });

        Schema::create('referral_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_code_id')->nullable()->constrained('referral_codes')->nullOnDelete();
            $table->foreignId('affiliate_id')->nullable()->constrained('affiliates')->nullOnDelete();
            $table->string('visitor_token', 80)->index();          // first-party cookie id
            $table->unsignedBigInteger('user_id')->nullable()->index(); // set on signup/order
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('source_url', 1024)->nullable();
            $table->string('ip_hash', 64)->nullable();             // hashed — no raw IP
            $table->string('user_agent_hash', 64)->nullable();
            $table->string('status')->default('pending')->index(); // pending|converted|expired
            $table->unsignedBigInteger('converted_order_id')->nullable()->index();
            $table->timestamp('attributed_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scope')->default('global')->index();   // global|affiliate|category|product|marketplace
            $table->unsignedBigInteger('scope_id')->nullable()->index();
            $table->string('type')->default('percentage');          // percentage|fixed
            $table->decimal('rate', 12, 4)->default(0);             // percent (0-100) or fixed amount
            $table->string('currency', 3)->nullable();
            $table->decimal('min_order_total', 18, 2)->nullable();
            $table->decimal('max_commission', 18, 2)->nullable();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_payout_batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('open')->index();     // open|processing|paid|closed
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('affiliate_payout_batches')->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('requested')->index(); // requested|approved|processing|paid|rejected
            $table->string('method')->nullable();
            $table->json('details')->nullable();
            $table->string('admin_note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('commission_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();          // read-only link to orders
            $table->foreignId('referral_attribution_id')->nullable()->constrained('referral_attributions')->nullOnDelete();
            $table->foreignId('commission_rule_id')->nullable()->constrained('commission_rules')->nullOnDelete();
            $table->foreignId('payout_request_id')->nullable()->constrained('affiliate_payout_requests')->nullOnDelete();
            $table->string('currency', 3)->default('USD');
            $table->decimal('order_total_snapshot', 18, 2)->default(0); // immutable
            $table->decimal('commission_amount', 18, 2)->default(0);    // immutable
            $table->string('status')->default('pending')->index();      // pending|approved|reversed|paid
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['affiliate_id', 'status']);
            $table->unique(['order_id', 'affiliate_id'], 'commission_ledger_order_affiliate_unique');
        });
    }

    public function down(): void
    {
        // Reverse dependency order.
        Schema::dropIfExists('commission_ledger');
        Schema::dropIfExists('affiliate_payout_requests');
        Schema::dropIfExists('affiliate_payout_batches');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('referral_attributions');
        Schema::dropIfExists('referral_codes');
        Schema::dropIfExists('affiliates');
    }
};
