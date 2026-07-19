<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->hasIndex('products', 'products_mpn_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('mpn', 'products_mpn_index');
            });
        }

        if (Schema::hasTable('inventory_stocks')) {
            Schema::table('inventory_stocks', function (Blueprint $table) {
                if (Schema::hasColumn('inventory_stocks', 'product_id')
                    && ! $this->hasIndex('inventory_stocks', 'inventory_stocks_product_id_index')) {
                    $table->index('product_id', 'inventory_stocks_product_id_index');
                }
                if (Schema::hasColumn('inventory_stocks', 'marketplace_id')
                    && ! $this->hasIndex('inventory_stocks', 'inventory_stocks_marketplace_id_index')) {
                    $table->index('marketplace_id', 'inventory_stocks_marketplace_id_index');
                }
                if (Schema::hasColumn('inventory_stocks', 'vendor_id')
                    && ! $this->hasIndex('inventory_stocks', 'inventory_stocks_vendor_id_index')) {
                    $table->index('vendor_id', 'inventory_stocks_vendor_id_index');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_mpn_index');
        });

        if (Schema::hasTable('inventory_stocks')) {
            Schema::table('inventory_stocks', function (Blueprint $table) {
                $table->dropIndex('inventory_stocks_product_id_index');
                $table->dropIndex('inventory_stocks_marketplace_id_index');
                $table->dropIndex('inventory_stocks_vendor_id_index');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        try {
            return Schema::hasIndex($table, $index);
        } catch (\Throwable) {
            return true; // Assume exists on failure — don't block deployment
        }
    }
};
