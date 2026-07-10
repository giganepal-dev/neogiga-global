<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global commerce Stage 2 foundations: schema only, no live pricing formula
 * or scheduler wired to these tables yet. See GLOBAL_COMMERCE_IMPLEMENTATION_PLAN.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exchange_rates')) {
            Schema::create('exchange_rates', function (Blueprint $table) {
                $table->id();
                $table->string('from_currency_code', 3);
                $table->string('to_currency_code', 3);
                $table->decimal('rate', 20, 10);
                $table->string('source', 80)->nullable();
                $table->timestamp('fetched_at');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                // Append-only history: no unique constraint on the currency pair alone,
                // so every fetch is preserved. Fast "latest rate" lookup index:
                $table->index(['from_currency_code', 'to_currency_code', 'fetched_at']);
            });
        }

        if (! Schema::hasTable('regional_price_history')) {
            Schema::create('regional_price_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('marketplace_product_price_id')->nullable()->index();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
                $table->decimal('old_base_price', 12, 4)->nullable();
                $table->decimal('new_base_price', 12, 4)->nullable();
                $table->decimal('old_sale_price', 12, 4)->nullable();
                $table->decimal('new_sale_price', 12, 4)->nullable();
                $table->string('currency_code', 3);
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->string('reason')->nullable();
                $table->timestamps();
                $table->index(['product_id', 'marketplace_id']);
            });
        }

        if (! Schema::hasTable('price_calculation_logs')) {
            Schema::create('price_calculation_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
                $table->decimal('base_cost_usd', 14, 4)->nullable();
                $table->decimal('exchange_rate', 20, 10)->nullable();
                $table->decimal('duty_amount', 14, 4)->nullable();
                $table->decimal('tax_amount', 14, 4)->nullable();
                $table->decimal('freight_amount', 14, 4)->nullable();
                $table->decimal('margin_amount', 14, 4)->nullable();
                $table->decimal('final_price', 14, 4)->nullable();
                $table->string('currency_code', 3);
                $table->string('calculation_version', 20)->default('v0-schema-only');
                $table->timestamps();
                $table->index(['product_id', 'marketplace_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('price_calculation_logs');
        Schema::dropIfExists('regional_price_history');
        Schema::dropIfExists('exchange_rates');
    }
};
