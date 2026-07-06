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
        Schema::create('marketplace_product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');
            $table->decimal('base_price', 15, 4);
            $table->decimal('sale_price', 15, 4)->nullable();
            $table->decimal('cost_price', 15, 4)->nullable();
            $table->string('currency_code', 3);
            $table->boolean('is_tax_inclusive')->default(false);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->date('sale_start_date')->nullable();
            $table->date('sale_end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'product_variant_id', 'marketplace_id'], 'marketplace_product_prices_unique');
            $table->index(['marketplace_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_product_prices');
    }
};
