<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Register history — every register action (open, close, cash-in, cash-out, sale, refund)
        if (!Schema::hasTable('pos_register_history')) {
            Schema::create('pos_register_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('register_id');
                $table->unsignedBigInteger('shift_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action'); // open, close, cash_in, cash_out, sale, refund, expense
                $table->string('payment_type')->nullable(); // cash, card, wallet, transfer
                $table->decimal('amount', 14, 2)->default(0);
                $table->decimal('balance_before', 14, 2)->default(0);
                $table->decimal('balance_after', 14, 2)->default(0);
                $table->string('description')->nullable();
                $table->string('reference_type')->nullable(); // sale, refund, expense, manual
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('register_id')->references('id')->on('pos_registers')->cascadeOnDelete();
                $table->index(['register_id', 'created_at']);
                $table->index(['action', 'created_at']);
            });
        }

        // Z-reports — end-of-day summaries per register
        if (!Schema::hasTable('pos_z_reports')) {
            Schema::create('pos_z_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('register_id');
                $table->unsignedBigInteger('shift_id')->nullable();
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->timestamp('report_date');
                $table->decimal('opening_balance', 14, 2)->default(0);
                $table->decimal('closing_balance', 14, 2)->default(0);
                $table->decimal('expected_balance', 14, 2)->default(0);
                $table->decimal('cash_sales', 14, 2)->default(0);
                $table->decimal('card_sales', 14, 2)->default(0);
                $table->decimal('wallet_sales', 14, 2)->default(0);
                $table->decimal('total_sales', 14, 2)->default(0);
                $table->decimal('total_refunds', 14, 2)->default(0);
                $table->decimal('total_expenses', 14, 2)->default(0);
                $table->decimal('cash_in', 14, 2)->default(0);
                $table->decimal('cash_out', 14, 2)->default(0);
                $table->decimal('difference', 14, 2)->default(0);
                $table->integer('sale_count')->default(0);
                $table->integer('refund_count')->default(0);
                $table->text('notes')->nullable();
                $table->json('payment_breakdown')->nullable();
                $table->timestamps();

                $table->foreign('register_id')->references('id')->on('pos_registers')->cascadeOnDelete();
                $table->index('report_date');
            });
        }

        // Customer rewards / loyalty points
        if (!Schema::hasTable('pos_reward_systems')) {
            Schema::create('pos_reward_systems', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->default('points'); // points, cashback, discount
                $table->decimal('target', 10, 2)->default(100); // spend amount to earn reward
                $table->decimal('reward_value', 10, 2)->default(1); // points/cashback per target
                $table->decimal('min_order', 10, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pos_customer_rewards')) {
            Schema::create('pos_customer_rewards', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('reward_system_id');
                $table->decimal('points_earned', 10, 2)->default(0);
                $table->decimal('points_redeemed', 10, 2)->default(0);
                $table->decimal('current_balance', 10, 2)->default(0);
                $table->timestamp('last_earned_at')->nullable();
                $table->timestamps();

                $table->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
                $table->unique(['customer_id', 'reward_system_id']);
            });
        }

        // Order instalments / payment plans
        if (!Schema::hasTable('pos_order_instalments')) {
            Schema::create('pos_order_instalments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->decimal('amount', 14, 2);
                $table->date('due_date');
                $table->string('status')->default('pending'); // pending, paid, overdue, cancelled
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
                $table->index(['order_id', 'status']);
                $table->index('due_date');
            });
        }

        // Enhanced coupons — per-customer, per-product, per-category targeting
        if (!Schema::hasTable('coupon_customer_targets')) {
            Schema::create('coupon_customer_targets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('coupon_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('customer_group_id')->nullable();
                $table->timestamps();

                $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
                $table->index(['coupon_id', 'customer_id']);
            });
        }

        if (!Schema::hasTable('coupon_product_targets')) {
            Schema::create('coupon_product_targets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('coupon_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->timestamps();

                $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
                $table->index(['coupon_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_product_targets');
        Schema::dropIfExists('coupon_customer_targets');
        Schema::dropIfExists('pos_order_instalments');
        Schema::dropIfExists('pos_customer_rewards');
        Schema::dropIfExists('pos_reward_systems');
        Schema::dropIfExists('pos_z_reports');
        Schema::dropIfExists('pos_register_history');
    }
};
