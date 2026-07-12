<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('incoming_quantity')->default(0);
            $table->date('last_restocked_at')->nullable();
            $table->string('bin_location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_warehouses');
    }
};
