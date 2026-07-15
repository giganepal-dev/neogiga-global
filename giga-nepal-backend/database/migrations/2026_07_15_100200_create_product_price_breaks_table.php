<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_breaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->integer('min_quantity');
            $table->integer('max_quantity')->nullable();
            $table->decimal('unit_price', 18, 6);
            $table->string('currency_code', 3)->default('USD');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index(['product_id', 'min_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_breaks');
    }
};
