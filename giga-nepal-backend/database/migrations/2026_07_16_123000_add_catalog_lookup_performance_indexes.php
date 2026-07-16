<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE INDEX CONCURRENTLY IF NOT EXISTS catalog_product_sources_unreviewed_product_idx\n"
                . "ON catalog_product_sources (product_id)\n"
                . "WHERE review_status IS NULL OR review_status <> 'approved'"
            );
            DB::statement(
                "CREATE INDEX CONCURRENTLY IF NOT EXISTS products_source_name_mpn_idx\n"
                . "ON products (source_name, mpn)\n"
                . "WHERE mpn IS NOT NULL AND mpn <> ''"
            );

            return;
        }

        Schema::table('catalog_product_sources', function (Blueprint $table) {
            $table->index('product_id', 'catalog_product_sources_unreviewed_product_idx');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->index(['source_name', 'mpn'], 'products_source_name_mpn_idx');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS catalog_product_sources_unreviewed_product_idx');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS products_source_name_mpn_idx');

            return;
        }

        Schema::table('catalog_product_sources', function (Blueprint $table) {
            $table->dropIndex('catalog_product_sources_unreviewed_product_idx');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_source_name_mpn_idx');
        });
    }
};
