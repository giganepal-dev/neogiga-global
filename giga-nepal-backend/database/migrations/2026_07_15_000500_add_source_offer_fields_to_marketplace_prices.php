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

        Schema::table('marketplace_product_prices', function (Blueprint $table): void {
            if (! Schema::hasColumn('marketplace_product_prices', 'source_offer_id')) {
                $table->unsignedBigInteger('source_offer_id')->nullable();
            }
            if (! Schema::hasColumn('marketplace_product_prices', 'source_fetched_at')) {
                $table->timestamp('source_fetched_at')->nullable();
            }
            if (! Schema::hasColumn('marketplace_product_prices', 'source_unit_price')) {
                $table->decimal('source_unit_price', 15, 6)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Upgrade-only provenance: automatic rollback must not discard source
        // offer identity, observation time, or source-unit-price evidence.
    }
};
