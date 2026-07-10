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
        Schema::create('warehouse_shipment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipment_id');
            $table->uuid('product_id');
            $table->uuid('product_variant_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('shipment_id');
            $table->index('product_id');
            $table->index('product_variant_id');
            
            // Foreign keys
            $table->foreign('shipment_id')->references('id')->on('warehouse_shipments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_shipment_items');
    }
};
