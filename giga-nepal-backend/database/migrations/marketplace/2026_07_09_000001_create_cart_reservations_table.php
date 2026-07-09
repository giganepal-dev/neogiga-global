<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates cart_reservations table for 15-minute soft inventory reservation system.
     * This prevents overselling by temporarily reserving stock when items are added to cart.
     */
    public function up(): void
    {
        Schema::create('cart_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');
            $table->foreignId('cart_item_id')->constrained('cart_items')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->onDelete('set null');
            $table->integer('quantity_reserved')->default(1);
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->timestamp('released_at')->nullable();
            $table->enum('status', ['active', 'released', 'converted', 'expired'])->default('active');
            $table->string('release_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['status', 'expires_at']);
            $table->index(['product_id', 'variant_id', 'status']);
            $table->index(['cart_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_reservations');
    }
};
