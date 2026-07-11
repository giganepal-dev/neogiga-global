<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public bool $withinTransaction = false;

    public function up(): void
    {
        DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS products_brand_normalized_mpn_idx ON products (brand_id, upper(regexp_replace(coalesce(mpn, ''), '\\s+', '', 'g')))");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS products_brand_normalized_mpn_idx');
    }
};
