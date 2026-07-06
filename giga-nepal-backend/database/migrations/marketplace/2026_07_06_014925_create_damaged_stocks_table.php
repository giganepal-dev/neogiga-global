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
        Schema::create('damaged_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_stock_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->string('damage_type'); // broken, defective, expired, lost, stolen, etc.
            $table->text('description')->nullable();
            $table->string('reported_by')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status')->default('pending'); // pending, approved, disposed
            $table->timestamp('disposed_at')->nullable();
            $table->timestamps();

            $table->index(['inventory_stock_id']);
            $table->index(['warehouse_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damaged_stocks');
    }
};
