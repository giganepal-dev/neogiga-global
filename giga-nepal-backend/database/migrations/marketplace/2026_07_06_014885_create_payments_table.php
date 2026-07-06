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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method'); // card, bank_transfer, cash, e_wallet, cod, etc.
            $table->string('payment_gateway')->nullable(); // stripe, paypal, razorpay, etc.
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 15, 4);
            $table->string('currency_code', 3);
            $table->string('status')->default('pending'); // pending, processing, completed, failed, refunded, cancelled
            $table->json('payment_details')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['invoice_id']);
            $table->index(['payment_number']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
