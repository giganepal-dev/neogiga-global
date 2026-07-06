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
        Schema::create('shipping_fee_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('zone_type')->default('domestic'); // domestic, international
            $table->json('zone_ids')->nullable();
            $table->decimal('base_fee', 15, 4);
            $table->decimal('per_kg_fee', 15, 4)->default(0);
            $table->decimal('free_shipping_threshold', 15, 4)->nullable();
            $table->string('carrier')->nullable();
            $table->string('service_level')->nullable(); // standard, express, same_day
            $table->integer('min_delivery_days')->nullable();
            $table->integer('max_delivery_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['marketplace_id']);
            $table->index(['country_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_fee_rules');
    }
};
