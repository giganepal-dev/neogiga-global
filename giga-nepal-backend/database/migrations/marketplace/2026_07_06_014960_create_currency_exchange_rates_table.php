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
        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3);
            $table->string('target_currency', 3);
            $table->decimal('rate', 15, 6);
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->string('source')->nullable(); // manual, api, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['base_currency', 'target_currency', 'effective_date'], 'currency_exchange_rates_unique');
            $table->index(['base_currency']);
            $table->index(['target_currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_exchange_rates');
    }
};
