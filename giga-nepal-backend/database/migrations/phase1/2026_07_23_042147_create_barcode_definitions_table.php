<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('barcode_definitions')) {
            Schema::create('barcode_definitions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->default('code128'); // ean13, code128, qr, datamatrix, upc
                $table->string('prefix')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_definitions');
    }
};
