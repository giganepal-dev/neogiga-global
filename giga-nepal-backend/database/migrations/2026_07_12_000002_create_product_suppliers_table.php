<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_product_id')->nullable(); // Supplier's product ID
            $table->string('supplier_sku')->nullable(); // Supplier's SKU
            $table->string('mpn')->nullable(); // Manufacturer Part Number
            $table->string('upc_ean')->nullable(); // UPC/EAN barcode
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->integer('lead_time_days')->nullable(); // Days to ship
            $table->integer('min_order_quantity')->default(1);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('last_synced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'supplier_id']);
            $table->index(['supplier_id', 'is_active']);
            $table->index('mpn');
            $table->index('upc_ean');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_suppliers');
    }
};
