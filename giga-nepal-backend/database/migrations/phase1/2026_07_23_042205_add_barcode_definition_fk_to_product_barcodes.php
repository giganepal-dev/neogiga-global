<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_barcodes') && !Schema::hasColumn('product_barcodes', 'barcode_definition_id')) {
            Schema::table('product_barcodes', function (Blueprint $table) {
                $table->unsignedBigInteger('barcode_definition_id')->nullable()->after('barcode_type')->index();
                $table->foreign('barcode_definition_id')->references('id')->on('barcode_definitions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_barcodes') && Schema::hasColumn('product_barcodes', 'barcode_definition_id')) {
            Schema::table('product_barcodes', function (Blueprint $table) {
                $table->dropForeign(['barcode_definition_id']);
                $table->dropColumn('barcode_definition_id');
            });
        }
    }
};
