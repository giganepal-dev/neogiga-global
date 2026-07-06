<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reserved_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_stock_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_type')->nullable(); // order, cart, pos_sale, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->integer('quantity');
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active'); // active, released, expired, used
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['inventory_stock_id']);
            $table->index(['warehouse_id']);
            $table->index(['product_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserved_stocks');
    }
};
