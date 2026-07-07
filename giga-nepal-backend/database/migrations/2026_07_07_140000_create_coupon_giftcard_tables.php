<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coupons + gift cards (NEOGIGA_GIFTCARD_COUPON_ADAPTATION_COMMAND). Additive.
 * Discounts are validated/computed server-side; client-supplied discount is
 * never trusted. Gift-card balance is an append-only transaction ledger.
 * Not wired into cart/checkout yet — provides validate/check + redeem APIs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percentage'); // percentage|fixed|free_shipping
            $table->decimal('value', 18, 2)->default(0);
            $table->string('currency', 3)->nullable();
            $table->string('scope')->default('cart');       // cart|product|category
            $table->json('applies_to')->nullable();         // [product_id...] or [category_id...]
            $table->decimal('min_order_total', 18, 2)->nullable();
            $table->decimal('max_discount', 18, 2)->nullable();
            $table->unsignedBigInteger('usage_limit')->nullable();          // total redemptions
            $table->unsignedBigInteger('usage_limit_per_user')->nullable();
            $table->unsignedBigInteger('used_count')->default(0);
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('redeemed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['coupon_id', 'order_id'], 'coupon_redemption_order_unique');
        });

        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('initial_balance', 18, 2)->default(0);
            $table->decimal('current_balance', 18, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('active')->index(); // active|redeemed|disabled|expired
            $table->string('issued_to_email')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('gift_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained('gift_cards')->cascadeOnDelete();
            $table->string('type');                          // issue|redeem|refund|adjust
            $table->decimal('amount', 18, 2);                // signed: +credit / -debit
            $table->decimal('balance_after', 18, 2);
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();     // append-only ledger (no updated_at)
            $table->index(['gift_card_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_transactions');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
