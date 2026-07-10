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
        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->uuid('product_id');
            $table->uuid('product_variant_id')->nullable();
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_incoming')->default(0);
            $table->integer('reorder_level')->default(10);
            $table->integer('reorder_quantity')->default(50);
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->string('bin_location')->nullable(); // A-01-02-03
            $table->string('zone')->nullable(); // Zone A, B, C
            $table->date('last_counted_at')->nullable();
            $table->timestamp('last_restocked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate product in warehouse
            $table->unique(['warehouse_id', 'product_id', 'product_variant_id']);

            // Indexes
            $table->index('warehouse_id');
            $table->index('product_id');
            $table->index('product_variant_id');
            $table->index('quantity_available');
            $table->index('quantity_reserved');
            
            // Foreign keys
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            // $table->foreign('product_id')->references('id')->on('products');
            // $table->foreign('product_variant_id')->references('id')->on('product_variants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_products');
    }
};
