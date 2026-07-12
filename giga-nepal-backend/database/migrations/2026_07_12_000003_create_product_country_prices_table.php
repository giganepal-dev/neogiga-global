<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_country_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_price', 12, 2)->default(0.00);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('bulk_price', 12, 2)->nullable();
            $table->integer('bulk_min_quantity')->default(10);
            $table->string('currency')->default('USD');
            $table->boolean('is_available')->default(true);
            $table->date('price_valid_from')->nullable();
            $table->date('price_valid_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'country_id']);
            $table->index(['country_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_country_prices');
    }
};
