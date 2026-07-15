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
            if (Schema::hasColumn('marketplace_product_prices', 'base_price')) {
                $table->decimal('base_price', 20, 8)->change();
            }
            if (Schema::hasColumn('marketplace_product_prices', 'sale_price')) {
                $table->decimal('sale_price', 20, 8)->nullable()->change();
            }
            if (Schema::hasColumn('marketplace_product_prices', 'cost_price')) {
                $table->decimal('cost_price', 20, 8)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Upgrade-only precision: automatic narrowing could round or destroy
        // valid sub-cent source costs and is therefore deliberately refused.
    }
};
