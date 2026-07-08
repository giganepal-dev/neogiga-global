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
        Schema::create('distributor_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_application_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Commission details
            $table->decimal('order_amount', 15, 2);
            $table->decimal('commission_rate', 5, 2); // Percentage
            $table->decimal('commission_amount', 15, 2);
            $table->string('currency', 3)->default('NPR');
            
            // Status tracking
            $table->string('status')->default('pending'); // pending, approved, paid, cancelled
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('payment_reference')->nullable();
            
            // Payment method
            $table->string('payment_method')->nullable(); // bank_transfer, eSewa, Khalti, etc.
            $table->string('payment_account_number')->nullable();
            $table->string('payment_account_holder_name')->nullable();
            
            // Period tracking
            $table->date('commission_period_start');
            $table->date('commission_period_end');
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->index(['distributor_application_id', 'status']);
            $table->index(['status', 'commission_period_end']);
            $table->index('created_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor_commissions');
    }
};
