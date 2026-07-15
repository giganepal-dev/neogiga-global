<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pcb_orders')) {
            Schema::create('pcb_orders', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('project_id')->constrained('pcb_projects')->cascadeOnDelete();
                $table->foreignUuid('quote_id')->constrained('pcb_quote_configurations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('order_number')->unique();
                $table->enum('status', ['pending_payment', 'paid', 'manufacturing', 'shipped', 'completed', 'cancelled'])->default('pending_payment');
                $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'refunded'])->default('unpaid');
                $table->string('currency', 3)->default('USD');
                $table->decimal('total_amount', 15, 2);
                $table->text('customer_notes')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pcb_orders');
    }
};
