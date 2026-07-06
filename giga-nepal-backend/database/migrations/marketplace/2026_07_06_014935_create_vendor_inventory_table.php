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
        Schema::create('vendor_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_damaged')->default(0);
            $table->integer('quantity_incoming')->default(0);
            $table->string('vendor_sku')->nullable();
            $table->decimal('cost_price', 15, 4)->nullable();
            $table->date('last_stock_count_date')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'warehouse_id', 'product_id', 'product_variant_id'], 'vendor_inventory_unique_stock');
            $table->index(['vendor_id']);
            $table->index(['warehouse_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_inventory');
    }
};
