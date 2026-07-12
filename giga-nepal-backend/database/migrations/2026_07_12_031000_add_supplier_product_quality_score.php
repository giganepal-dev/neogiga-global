<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_products') && ! Schema::hasColumn('supplier_products', 'data_quality_score')) {
            Schema::table('supplier_products', function (Blueprint $table): void {
                $table->decimal('data_quality_score', 5, 2)->default(0)->index()->after('review_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_products') && Schema::hasColumn('supplier_products', 'data_quality_score')) {
            Schema::table('supplier_products', function (Blueprint $table): void {
                $table->dropColumn('data_quality_score');
            });
        }
    }
};
