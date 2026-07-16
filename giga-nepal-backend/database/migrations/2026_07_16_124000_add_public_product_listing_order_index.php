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
                "CREATE INDEX CONCURRENTLY IF NOT EXISTS products_public_listing_order_idx\n"
                . "ON products (is_featured DESC, name ASC, id ASC)\n"
                . "WHERE status IN ('active', 'approved', 'published')\n"
                . "  AND visibility_status IN ('public', 'marketplace_only', 'quote_only')"
            );

            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_featured', 'name', 'id'], 'products_public_listing_order_idx');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS products_public_listing_order_idx');

            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_public_listing_order_idx');
        });
    }
};
