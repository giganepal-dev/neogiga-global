<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PHASE 15 & 16: ORDER FULFILLMENT & PAYMENTS
        if (!Schema::hasTable('order_fulfillments')) {
            Schema::create('order_fulfillments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->onDelete('cascade');
                $table->foreignId('seller_id')->nullable()->constrained('users');
                $table->foreignId('warehouse_id')->nullable()->constrained();
                $table->string('status')->default('pending');
                $table->string('tracking_number')->nullable();
                $table->string('carrier')->nullable();
                $table->json('items');
                $table->decimal('subtotal', 15, 2);
                $table->decimal('shipping_cost', 15, 2)->default(0.00);
                $table->decimal('tax_amount', 15, 2)->default(0.00);
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->onDelete('cascade');
                $table->string('transaction_id')->unique();
                $table->string('gateway');
                $table->string('type')->default('capture');
                $table->decimal('amount', 15, 2);
                $table->string('currency');
                $table->string('status');
                $table->json('payload');
                $table->string('failure_reason')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tax_invoices')) {
            Schema::create('tax_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained();
                $table->string('invoice_number')->unique();
                $table->string('invoice_path');
                $table->json('tax_breakdown');
                $table->boolean('is_sent')->default(false);
                $table->timestamps();
            });
        }

        // PHASE 18: POS SYSTEM — create tables BEFORE adding FKs referencing them
        if (!Schema::hasTable('pos_registers')) {
            Schema::create('pos_registers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pos_shifts')) {
            Schema::create('pos_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('register_id')->constrained('pos_registers');
                $table->foreignId('user_id')->constrained();
                $table->decimal('opening_cash', 15, 2);
                $table->decimal('closing_cash', 15, 2)->nullable();
                $table->decimal('expected_cash', 15, 2)->nullable();
                $table->string('status')->default('open');
                $table->text('notes')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();
            });
        }

        // Add POS columns to orders AFTER pos_shifts exists
        if (!Schema::hasColumn('orders', 'is_pos_order')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->boolean('is_pos_order')->default(false)->after('status');
                $table->foreignId('pos_shift_id')->nullable()->after('is_pos_order');
                $table->string('customer_walkin_name')->nullable()->after('user_id');
                $table->string('customer_walkin_phone')->nullable()->after('customer_walkin_name');
            });

            if (Schema::hasTable('pos_shifts')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreign('pos_shift_id')->references('id')->on('pos_shifts');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_shifts');
        Schema::dropIfExists('pos_registers');
        
        if (Schema::hasColumn('orders', 'is_pos_order')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['pos_shift_id']);
                $table->dropColumn(['is_pos_order', 'pos_shift_id', 'customer_walkin_name', 'customer_walkin_phone']);
            });
        }
        
        Schema::dropIfExists('tax_invoices');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('order_fulfillments');
    }
};
