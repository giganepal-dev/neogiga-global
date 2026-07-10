<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_reservations')) {
            return;
        }

        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_stock_id')->constrained('inventory_stocks')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->integer('quantity');
            $table->string('status')->default('active')->index();
            $table->string('reference_type')->nullable()->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
