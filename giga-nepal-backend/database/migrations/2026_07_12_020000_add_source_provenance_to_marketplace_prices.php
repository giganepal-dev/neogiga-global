<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_product_prices')) {
            return;
        }

        $columns = [
            'source_name' => fn (Blueprint $table) => $table->string('source_name')->nullable()->index(),
            'source_url' => fn (Blueprint $table) => $table->text('source_url')->nullable(),
            'source_offer_id' => fn (Blueprint $table) => $table->unsignedBigInteger('source_offer_id')->nullable()->index(),
            'source_fetched_at' => fn (Blueprint $table) => $table->timestamp('source_fetched_at')->nullable(),
            'source_unit_price' => fn (Blueprint $table) => $table->decimal('source_unit_price', 15, 6)->nullable(),
            'pricing_rule' => fn (Blueprint $table) => $table->string('pricing_rule')->nullable(),
            'source_review_status' => fn (Blueprint $table) => $table->string('source_review_status', 40)->nullable()->index(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('marketplace_product_prices', $column)) {
                Schema::table('marketplace_product_prices', $definition);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplace_product_prices')) {
            return;
        }

        foreach (['source_review_status', 'pricing_rule', 'source_unit_price', 'source_fetched_at', 'source_offer_id', 'source_url', 'source_name'] as $column) {
            if (Schema::hasColumn('marketplace_product_prices', $column)) {
                Schema::table('marketplace_product_prices', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
