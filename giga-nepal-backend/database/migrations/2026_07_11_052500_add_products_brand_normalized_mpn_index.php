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
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support CONCURRENTLY or regexp_replace
            // Create a simple index on brand_id and mpn for SQLite
            Schema::table('products', function (Blueprint $table) {
                $table->index(['brand_id', 'mpn'], 'products_brand_normalized_mpn_idx');
            });
        } else {
            DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS products_brand_normalized_mpn_idx ON products (brand_id, upper(regexp_replace(coalesce(mpn, ''), '\\s+', '', 'g')))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_brand_normalized_mpn_idx');
            });
        } else {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS products_brand_normalized_mpn_idx');
        }
    }
};
