<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates complete seller and distributor marketplace tables:
     * - Seller onboarding & management
     * - Distributor network & territories
     * - Commission tracking & payouts
     * - Vendor products & orders
     * - Performance metrics & reviews
     */
    public function up(): void
    {
        // =====================
        // SELLER/VENDOR TABLES
        // =====================
        if (!Schema::hasTable('sellers')) {
            Schema::create('sellers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('seller_number')->unique()->index();
                $table->string('business_name');
                $table->string('slug')->unique();
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('status')->default('pending')->index(); // pending, under_review, approved, rejected, suspended, deactivated
                $table->string('type')->default('individual'); // individual, company, manufacturer, distributor
                $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
                $table->string('tax_number')->nullable();
                $table->string('registration_number')->nullable();
                $table->text('business_address')->nullable();
                $table->json('documents')->nullable(); // business_license, tax_cert, id_proof
                $table->decimal('commission_rate', 5, 2)->default(0);
                $table->string('payout_schedule')->default('weekly'); // daily, weekly, monthly
                $table->decimal('minimum_payout', 15, 2)->default(50);
                $table->json('bank_details')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('rejection_reason')->nullable();
                $table->text('suspension_reason')->nullable();
                $table->timestamp('suspended_at')->nullable();
                $table->integer('total_orders')->default(0);
                $table->decimal('total_sales', 15, 2)->default(0);
                $table->decimal('average_rating', 3, 2)->default(0);
                $table->integer('total_reviews')->default(0);
                $table->boolean('is_verified')->default(false)->index();
                $table->boolean('can_sell')->default(false)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['status', 'is_verified']);
                $table->index(['country_id', 'status']);
            });
        }

        if (!Schema::hasTable('seller_staff')) {
            Schema::create('seller_staff', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('role')->default('staff'); // admin, manager, staff, viewer
                $table->json('permissions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();

                $table->index(['seller_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('seller_products')) {
            Schema::create('seller_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('marketplace_id')->nullable()->constrained()->onDelete('set null');
                $table->string('seller_sku')->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 15, 4);
                $table->decimal('compare_at_price', 15, 4)->nullable();
                $table->integer('quantity_available')->default(0);
                $table->integer('quantity_reserved')->default(0);
                $table->integer('low_stock_threshold')->default(10);
                $table->string('status')->default('draft')->index(); // draft, pending_review, approved, rejected, inactive
                $table->boolean('is_featured')->default(false);
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->json('images')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['seller_id', 'status']);
                $table->index(['product_id', 'status']);
                $table->unique(['seller_id', 'seller_sku'], 'seller_product_unique');
            });
        }

        if (!Schema::hasTable('seller_orders')) {
            Schema::create('seller_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                $table->string('seller_order_number')->unique()->index();
                $table->string('status')->default('pending')->index(); // pending, confirmed, processing, shipped, delivered, cancelled, refunded
                $table->string('payment_status')->default('pending')->index(); // pending, paid, partial, refunded
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('subtotal', 15, 4)->default(0);
                $table->decimal('tax_total', 15, 4)->default(0);
                $table->decimal('shipping_total', 15, 4)->default(0);
                $table->decimal('commission_amount', 15, 4)->default(0);
                $table->decimal('net_amount', 15, 4)->default(0);
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['seller_id', 'status']);
                $table->index(['order_id']);
            });
        }

        if (!Schema::hasTable('seller_order_items')) {
            Schema::create('seller_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('seller_product_id')->nullable()->constrained()->onDelete('set null');
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 15, 4);
                $table->decimal('tax_amount', 15, 4)->default(0);
                $table->decimal('shipping_amount', 15, 4)->default(0);
                $table->decimal('commission_amount', 15, 4)->default(0);
                $table->decimal('net_amount', 15, 4);
                $table->string('fulfillment_status')->default('pending')->index();
                $table->timestamps();

                $table->index(['seller_order_id']);
            });
        }

        // =====================
        // COMMISSION & PAYOUTS
        // =====================
        if (!Schema::hasTable('seller_commission_rules')) {
            Schema::create('seller_commission_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('marketplace_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
                $table->string('name');
                $table->string('type')->default('percentage'); // percentage, fixed, tiered
                $table->decimal('value', 10, 4)->default(0);
                $table->json('tiers')->nullable(); // for tiered commissions
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                $table->index(['seller_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('seller_commissions')) {
            Schema::create('seller_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->foreignId('seller_order_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('seller_order_item_id')->nullable()->constrained()->onDelete('set null');
                $table->string('commission_number')->unique()->index();
                $table->string('type')->default('sale'); // sale, refund_adjustment, bonus, penalty
                $table->decimal('base_amount', 15, 4);
                $table->decimal('commission_rate', 5, 4);
                $table->decimal('commission_amount', 15, 4);
                $table->string('currency_code', 3)->default('USD');
                $table->string('status')->default('pending')->index(); // pending, approved, processing, paid, cancelled
                $table->timestamp('earned_at');
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('paid_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['seller_id', 'status']);
                $table->index(['earned_at']);
            });
        }

        if (!Schema::hasTable('seller_payouts')) {
            Schema::create('seller_payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->string('payout_number')->unique()->index();
                $table->string('status')->default('pending')->index(); // pending, processing, paid, failed, cancelled
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('gross_amount', 15, 4)->default(0);
                $table->decimal('fee_amount', 15, 4)->default(0);
                $table->decimal('net_amount', 15, 4)->default(0);
                $table->string('payout_method')->default('bank_transfer'); // bank_transfer, paypal, check
                $table->json('payout_details')->nullable();
                $table->integer('commission_count')->default(0);
                $table->date('period_start');
                $table->date('period_end');
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['seller_id', 'status']);
                $table->index(['period_start', 'period_end']);
            });
        }

        if (!Schema::hasTable('seller_payout_items')) {
            Schema::create('seller_payout_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_payout_id')->constrained()->cascadeOnDelete();
                $table->foreignId('seller_commission_id')->nullable()->constrained()->onDelete('set null');
                $table->string('description');
                $table->decimal('amount', 15, 4);
                $table->string('type')->default('commission'); // commission, adjustment, bonus, penalty
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['seller_payout_id']);
            });
        }

        // =====================
        // SELLER PERFORMANCE
        // =====================
        if (!Schema::hasTable('seller_ratings')) {
            Schema::create('seller_ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                $table->integer('overall_rating')->default(5);
                $table->integer('communication_rating')->default(5);
                $table->integer('shipping_speed_rating')->default(5);
                $table->integer('item_as_described_rating')->default(5);
                $table->text('comment')->nullable();
                $table->boolean('is_verified_purchase')->default(false);
                $table->string('status')->default('published')->index(); // published, hidden, flagged
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['seller_id', 'status']);
                $table->index(['order_id']);
            });
        }

        if (!Schema::hasTable('seller_metrics')) {
            Schema::create('seller_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->date('metric_date');
                $table->integer('orders_count')->default(0);
                $table->decimal('sales_amount', 15, 2)->default(0);
                $table->decimal('commission_amount', 15, 2)->default(0);
                $table->integer('items_sold')->default(0);
                $table->integer('page_views')->default(0);
                $table->integer('cart_additions')->default(0);
                $table->decimal('conversion_rate', 5, 4)->default(0);
                $table->decimal('average_order_value', 15, 2)->default(0);
                $table->integer('cancellations_count')->default(0);
                $table->integer('returns_count')->default(0);
                $table->decimal('return_rate', 5, 4)->default(0);
                $table->integer('late_shipments_count')->default(0);
                $table->decimal('on_time_rate', 5, 4)->default(0);
                $table->decimal('average_rating', 3, 2)->default(0);
                $table->integer('new_reviews_count')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['seller_id', 'metric_date'], 'seller_metric_unique');
                $table->index(['seller_id', 'metric_date']);
            });
        }

        // =====================
        // DISTRIBUTOR TABLES
        // =====================
        if (!Schema::hasTable('distributors')) {
            Schema::create('distributors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('parent_id')->nullable()->constrained('distributors')->nullOnDelete();
                $table->string('distributor_number')->unique()->index();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('type')->default('reseller')->index(); // reseller, authorized, regional, national
                $table->string('status')->default('pending')->index(); // pending, under_review, approved, rejected, suspended
                $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
                $table->string('tax_number')->nullable();
                $table->string('registration_number')->nullable();
                $table->text('business_address')->nullable();
                $table->json('documents')->nullable();
                $table->json('authorized_brands')->nullable();
                $table->json('authorized_categories')->nullable();
                $table->decimal('credit_limit', 15, 2)->default(0);
                $table->decimal('outstanding_balance', 15, 2)->default(0);
                $table->string('payment_terms')->default('net_30'); // net_15, net_30, net_60, cod
                $table->decimal('discount_rate', 5, 2)->default(0);
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('rejection_reason')->nullable();
                $table->integer('total_orders')->default(0);
                $table->decimal('total_sales', 15, 2)->default(0);
                $table->boolean('can_manage_downlines')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['parent_id', 'status']);
                $table->index(['country_id', 'status']);
                $table->index(['type', 'status']);
            });
        }

        if (!Schema::hasTable('distributor_territories')) {
            Schema::create('distributor_territories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('region_id')->nullable()->index();
                $table->unsignedBigInteger('city_id')->nullable()->index();
                $table->string('territory_name');
                $table->boolean('exclusive')->default(false);
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['distributor_id', 'country_id']);
            });
        }

        if (!Schema::hasTable('distributor_downlines')) {
            Schema::create('distributor_downlines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_distributor_id')->constrained('distributors')->cascadeOnDelete();
                $table->foreignId('child_distributor_id')->constrained('distributors')->cascadeOnDelete();
                $table->string('relationship_type')->default('downline'); // downline, sub_distributor, affiliate
                $table->decimal('override_rate', 5, 4)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['parent_distributor_id', 'child_distributor_id'], 'distributor_downline_unique');
                $table->index(['parent_distributor_id']);
                $table->index(['child_distributor_id']);
            });
        }

        if (!Schema::hasTable('distributor_orders')) {
            Schema::create('distributor_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('distributor_order_number')->unique()->index();
                $table->string('status')->default('pending')->index();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('gross_amount', 15, 4)->default(0);
                $table->decimal('discount_amount', 15, 4)->default(0);
                $table->decimal('net_amount', 15, 4)->default(0);
                $table->decimal('commission_earned', 15, 4)->default(0);
                $table->string('payment_status')->default('pending')->index();
                $table->timestamp('paid_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['distributor_id', 'status']);
            });
        }

        if (!Schema::hasTable('distributor_commissions')) {
            Schema::create('distributor_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('distributor_order_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('downline_distributor_id')->nullable()->constrained('distributors')->nullOnDelete();
                $table->string('commission_number')->unique()->index();
                $table->string('type')->default('direct_sale'); // direct_sale, override, bonus, rebate
                $table->decimal('base_amount', 15, 4);
                $table->decimal('commission_rate', 5, 4);
                $table->decimal('commission_amount', 15, 4);
                $table->string('currency_code', 3)->default('USD');
                $table->string('status')->default('pending')->index();
                $table->timestamp('earned_at');
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['distributor_id', 'status']);
            });
        }

        if (!Schema::hasTable('distributor_payouts')) {
            Schema::create('distributor_payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->string('payout_number')->unique()->index();
                $table->string('status')->default('pending')->index();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('gross_amount', 15, 4)->default(0);
                $table->decimal('fee_amount', 15, 4)->default(0);
                $table->decimal('net_amount', 15, 4)->default(0);
                $table->string('payout_method')->default('bank_transfer');
                $table->json('payout_details')->nullable();
                $table->date('period_start');
                $table->date('period_end');
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['distributor_id', 'status']);
            });
        }

        if (!Schema::hasTable('distributor_leads')) {
            Schema::create('distributor_leads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->string('lead_number')->unique()->index();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('company')->nullable();
                $table->string('status')->default('new')->index(); // new, contacted, qualified, converted, lost
                $table->decimal('estimated_value', 15, 2)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('converted_at')->nullable();
                $table->foreignId('converted_order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['distributor_id', 'status']);
            });
        }

        if (!Schema::hasTable('distributor_customers')) {
            Schema::create('distributor_customers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('customer_number')->unique()->index();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('type')->default('retail')->index(); // retail, wholesale, vip, corporate
                $table->decimal('credit_limit', 15, 2)->default(0);
                $table->decimal('outstanding_balance', 15, 2)->default(0);
                $table->integer('total_orders')->default(0);
                $table->decimal('lifetime_value', 15, 2)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['distributor_id', 'type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor_customers');
        Schema::dropIfExists('distributor_leads');
        Schema::dropIfExists('distributor_payouts');
        Schema::dropIfExists('distributor_commissions');
        Schema::dropIfExists('distributor_orders');
        Schema::dropIfExists('distributor_downlines');
        Schema::dropIfExists('distributor_territories');
        Schema::dropIfExists('distributors');
        
        Schema::dropIfExists('seller_metrics');
        Schema::dropIfExists('seller_ratings');
        Schema::dropIfExists('seller_payout_items');
        Schema::dropIfExists('seller_payouts');
        Schema::dropIfExists('seller_commissions');
        Schema::dropIfExists('seller_commission_rules');
        Schema::dropIfExists('seller_order_items');
        Schema::dropIfExists('seller_orders');
        Schema::dropIfExists('seller_products');
        Schema::dropIfExists('seller_staff');
        Schema::dropIfExists('sellers');
    }
};
