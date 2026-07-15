<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_index_rebuild_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('catalog_index_rebuild_jobs', 'last_processed_product_id')) {
                $table->bigInteger('last_processed_product_id')->nullable()->after('indexed_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_index_rebuild_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('catalog_index_rebuild_jobs', 'last_processed_product_id')) {
                $table->dropColumn('last_processed_product_id');
            }
        });
    }
};
