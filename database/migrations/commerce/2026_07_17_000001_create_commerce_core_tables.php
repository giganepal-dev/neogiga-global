<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates the core commerce tables for:
     * - Order management (orders, order_items, order_status_history)
     * - Payment processing (payments, payment_transactions, refunds)
     * - Cart functionality (carts, cart_items)
     * - Invoicing (invoices, invoice_items)
     * - Returns & warranties (return_requests, return_items, warranty_claims)
     * - Shipments (shipments, shipment_tracking)
     */
    public function up(): void
    {
        // =====================
        // CART TABLES
        // =====================
        if (!Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->id();
                $table->string('cart_token')->unique()->index();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('marketplace_id')->nullable()->constrained()->onDelete('set null');
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('subtotal', 15, 4)->default(0);
                $table->decimal('tax_total', 15, 4)->default(0);
                $table->decimal('discount_total', 15, 4)->default(0);
                $table->decimal('shipping_total', 15, 4)->default(0);
                $table->decimal('grand_total', 15, 4)->default(0);
                $table->integer('item_count')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['cart_token', 'expires_at']);
            });
        }

        if (!Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('marketplace_price_id')->nullable()->constrained()->onDelete('set null');
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->string('product_mpn')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 15, 4);
                $table->decimal('tax_amount', 15, 4)->default(0);
                $table->decimal('discount_amount', 15, 4)->default(0);
                $table->decimal('line_total', 15, 4);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['cart_id']);
                $table->index(['product_id']);
            });
        }

        // =====================
        // ORDER TABLES
        // =====================
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique()->index();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('marketplace_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('cart_id')->nullable()->constrained()->onDelete('set null');
                $table->enum('status', [
                    'pending', 'confirmed', 'processing', 'shipped', 
                    'delivered', 'cancelled', 'refunded', 'failed'
                ])->default('pending')->index();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('subtotal', 15, 4)->default(0);
                $table->decimal('tax_total', 15, 4)->default(0);
                $table->decimal('discount_total', 15, 4)->default(0);
                $table->decimal('shipping_total', 15, 4)->default(0);
                $table->decimal('grand_total', 15, 4)->default(0);
                $table->decimal('amount_paid', 15, 4)->default(0);
                $table->decimal('amount_due', 15, 4)->default(0);
                $table->string('payment_method')->nullable();
                $table->string('payment_gateway')->nullable();
                $table->string('payment_status')->default('pending')->index();
                $table->json('billing_address')->nullable();
                $table->json('shipping_address')->nullable();
                $table->text('customer_notes')->nullable();
                $table->text('vendor_notes')->nullable();
                $table->string('tracking_number')->nullable();
                $table->string('carrier')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancellation_reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['marketplace_id', 'status']);
                $table->index(['payment_status', 'status']);
            });
        }

        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->string('product_mpn')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 15, 4);
                $table->decimal('tax_amount', 15, 4)->default(0);
                $table->decimal('discount_amount', 15, 4)->default(0);
                $table->decimal('shipping_amount', 15, 4)->default(0);
                $table->decimal('line_total', 15, 4);
                $table->decimal('commission_amount', 15, 4)->default(0);
                $table->decimal('vendor_net_amount', 15, 4)->default(0);
                $table->string('fulfillment_status')->default('pending')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['order_id']);
                $table->index(['vendor_id', 'fulfillment_status']);
            });
        }

        if (!Schema::hasTable('order_status_history')) {
            Schema::create('order_status_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('old_status')->nullable();
                $table->string('new_status');
                $table->text('notes')->nullable();
                $table->boolean('notify_customer')->default(false);
                $table->string('ip_address')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'created_at']);
            });
        }

        // =====================
        // PAYMENT TABLES
        // =====================
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->string('payment_number')->unique()->index();
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('marketplace_id')->nullable()->constrained()->onDelete('set null');
                $table->string('payment_method')->index(); // card, bank_transfer, cash, e_wallet, cod
                $table->string('payment_gateway')->nullable()->index(); // stripe, paypal, razorpay, etc.
                $table->string('transaction_id')->nullable()->unique();
                $table->decimal('amount', 15, 4);
                $table->string('currency_code', 3);
                $table->string('status')->default('pending')->index(); // pending, processing, completed, failed, refunded
                $table->json('payment_details')->nullable();
                $table->text('notes')->nullable();
                $table->string('failure_reason')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'status']);
            });
        }

        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->string('transaction_id')->unique()->index();
                $table->string('gateway')->index();
                $table->string('type')->default('capture'); // capture, refund, void
                $table->decimal('amount', 15, 4);
                $table->string('currency_code', 3);
                $table->string('status')->index();
                $table->json('payload')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['payment_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->id();
                $table->string('refund_number')->unique()->index();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->decimal('amount', 15, 4);
                $table->string('currency_code', 3);
                $table->string('reason')->index();
                $table->text('notes')->nullable();
                $table->string('status')->default('pending')->index(); // pending, approved, processing, completed, rejected
                $table->json('items')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'status']);
            });
        }

        // =====================
        // INVOICE TABLES
        // =====================
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique()->index();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('marketplace_id')->nullable()->constrained()->onDelete('set null');
                $table->string('invoice_type')->default('standard'); // standard, proforma, tax, credit
                $table->string('status')->default('draft')->index(); // draft, sent, paid, overdue, cancelled
                $table->date('issue_date');
                $table->date('due_date')->nullable();
                $table->decimal('subtotal', 15, 4)->default(0);
                $table->decimal('tax_total', 15, 4)->default(0);
                $table->decimal('discount_total', 15, 4)->default(0);
                $table->decimal('total_amount', 15, 4)->default(0);
                $table->decimal('amount_paid', 15, 4)->default(0);
                $table->decimal('amount_due', 15, 4)->default(0);
                $table->string('currency_code', 3);
                $table->json('billing_address')->nullable();
                $table->json('tax_breakdown')->nullable();
                $table->string('invoice_path')->nullable();
                $table->boolean('is_sent')->default(false);
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['order_id']);
                $table->index(['status', 'due_date']);
            });
        }

        if (!Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('set null');
                $table->string('description');
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 15, 4);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax_amount', 15, 4)->default(0);
                $table->decimal('discount_amount', 15, 4)->default(0);
                $table->decimal('line_total', 15, 4);
                $table->timestamps();

                $table->index(['invoice_id']);
            });
        }

        // =====================
        // SHIPMENT TABLES
        // =====================
        if (!Schema::hasTable('shipments')) {
            Schema::create('shipments', function (Blueprint $table) {
                $table->id();
                $table->string('shipment_number')->unique()->index();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('set null');
                $table->string('carrier')->nullable();
                $table->string('service_level')->nullable(); // express, standard, economy
                $table->string('tracking_number')->nullable()->index();
                $table->string('status')->default('pending')->index(); // pending, picked, packed, shipped, in_transit, delivered, returned
                $table->json('items')->nullable();
                $table->decimal('weight_value', 10, 2)->nullable();
                $table->string('weight_unit')->default('kg');
                $table->json('dimensions')->nullable(); // {length, width, height, unit}
                $table->json('shipping_address')->nullable();
                $table->timestamp('picked_at')->nullable();
                $table->timestamp('packed_at')->nullable();
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('in_transit_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('returned_at')->nullable();
                $table->text('return_reason')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'status']);
                $table->index(['tracking_number', 'status']);
            });
        }

        if (!Schema::hasTable('shipment_tracking')) {
            Schema::create('shipment_tracking', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
                $table->string('status')->index();
                $table->string('location')->nullable();
                $table->text('message');
                $table->timestamp('occurred_at');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['shipment_id', 'occurred_at']);
            });
        }

        // =====================
        // RETURN TABLES
        // =====================
        if (!Schema::hasTable('return_requests')) {
            Schema::create('return_requests', function (Blueprint $table) {
                $table->id();
                $table->string('return_number')->unique()->index();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('shipment_id')->nullable()->constrained()->onDelete('set null');
                $table->string('reason')->index();
                $table->text('description')->nullable();
                $table->string('status')->default('pending')->index(); // pending, approved, received, inspecting, refunded, rejected, closed
                $table->string('resolution')->nullable(); // refund, replacement, store_credit
                $table->decimal('refund_amount', 15, 4)->default(0);
                $table->string('currency_code', 3)->default('USD');
                $table->boolean('return_shipping_required')->default(true);
                $table->string('return_shipping_label')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('received_at')->nullable();
                $table->timestamp('inspected_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'status']);
                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('return_items')) {
            Schema::create('return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->integer('quantity_returned');
                $table->decimal('unit_price', 15, 4);
                $table->decimal('refund_amount', 15, 4)->default(0);
                $table->string('condition')->default('unopened'); // unopened, opened, damaged, defective
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['return_request_id']);
            });
        }

        // =====================
        // WARRANTY TABLES
        // =====================
        if (!Schema::hasTable('warranty_claims')) {
            Schema::create('warranty_claims', function (Blueprint $table) {
                $table->id();
                $table->string('claim_number')->unique()->index();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('product_name');
                $table->string('serial_number')->nullable();
                $table->date('purchase_date');
                $table->date('claim_date');
                $table->string('issue_type')->index(); // defective, damaged, not_working, wrong_item
                $table->text('description');
                $table->json('images')->nullable();
                $table->string('status')->default('submitted')->index(); // submitted, reviewing, approved, rejected, repaired, replaced, refunded, closed
                $table->string('resolution')->nullable(); // repair, replace, refund, store_credit
                $table->text('vendor_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'status']);
                $table->index(['product_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('shipment_tracking');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
