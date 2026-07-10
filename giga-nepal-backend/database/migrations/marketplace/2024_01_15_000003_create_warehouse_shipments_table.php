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
        Schema::create('warehouse_shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('shipment_number')->unique();
            $table->uuid('from_warehouse_id');
            $table->uuid('to_warehouse_id');
            $table->enum('type', ['transfer', 'inbound', 'outbound', 'return']);
            $table->enum('status', ['pending', 'in_transit', 'delivered', 'cancelled'])->default('pending');
            $table->integer('total_items')->default(0);
            $table->decimal('total_weight', 10, 2)->nullable();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->date('expected_departure_date')->nullable();
            $table->date('expected_arrival_date')->nullable();
            $table->timestamp('actual_departure_at')->nullable();
            $table->timestamp('actual_arrival_at')->nullable();
            $table->json('customs_documents')->nullable(); // For cross-border shipments
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('shipment_number');
            $table->index('from_warehouse_id');
            $table->index('to_warehouse_id');
            $table->index('type');
            $table->index('status');
            
            // Foreign keys
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_shipments');
    }
};
