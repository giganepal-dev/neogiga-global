<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->cascadeOnDelete();
            $table->string('tracking_number')->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('carrier_service')->nullable();
            $table->string('shipping_method')->nullable(); // standard, express, overnight
            $table->decimal('weight_value', 10, 2)->nullable();
            $table->string('weight_unit')->default('kg');
            $table->decimal('length_value', 10, 2)->nullable();
            $table->decimal('width_value', 10, 2)->nullable();
            $table->decimal('height_value', 10, 2)->nullable();
            $table->string('dimension_unit')->default('cm');
            $table->json('packages')->nullable(); // multiple package details
            $table->string('commercial_invoice_path')->nullable();
            $table->string('packing_list_path')->nullable();
            $table->string('certificate_of_origin_path')->nullable();
            $table->json('customs_documents')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('status')->default('pending'); // pending, picked_up, in_transit, out_for_delivery, delivered, returned, failed
            $table->text('delivery_notes')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('tracking_events')->nullable(); // carrier tracking events
            $table->boolean('is_partial')->default(false);
            $table->foreignId('parent_shipment_id')->nullable()->constrained('seller_shipments')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['vendor_id', 'status']);
            $table->index('tracking_number');
            $table->index('status');
            $table->index('shipped_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_shipments');
    }
};
