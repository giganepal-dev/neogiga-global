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
        Schema::create('regional_inventory_visibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_sellable')->default(true);
            $table->integer('min_stock_threshold')->default(5);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'product_variant_id', 'marketplace_id'], 'regional_inventory_visibility_unique');
            $table->index(['marketplace_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regional_inventory_visibility');
    }
};
