<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('seller_offer_id')->nullable()->constrained('seller_offers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->string('movement_type'); // opening_balance, purchase_receipt, manual_increase, manual_decrease, reservation, reservation_release, fulfillment, return, damage, quarantine, transfer_in, transfer_out, correction
            $table->string('reference_type')->nullable(); // App\Models\Order, App\Models\ReturnRequest
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->integer('quantity_change'); // positive or negative
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('available_before')->default(0);
            $table->integer('available_after')->default(0);
            $table->integer('reserved_before')->default(0);
            $table->integer('reserved_after')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['vendor_id', 'product_id']);
            $table->index(['vendor_id', 'warehouse_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('movement_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_inventory_movements');
    }
};
