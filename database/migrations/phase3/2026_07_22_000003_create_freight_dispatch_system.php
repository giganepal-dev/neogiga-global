<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 3: Freight, Dispatch & Delivery Management
     */
    public function up(): void
    {
        // Freight Shipments (Inbound)
        Schema::create('freight_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('shipment_type'); // inbound, outbound
            $table->string('shipment_number')->unique();
            $table->string('awb_number')->nullable();
            $table->string('bl_number')->nullable();
            $table->string('container_number')->nullable();
            $table->string('tracking_number')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->nullOnDelete();
            $table->foreignId('freight_forwarder_id')->nullable()->constrained('carriers')->nullOnDelete();
            $table->string('origin_country')->nullable();
            $table->string('origin_port')->nullable();
            $table->string('destination_country')->nullable();
            $table->string('destination_port')->nullable();
            $table->string('incoterm')->nullable(); // FOB, CIF, EXW, etc.
            $table->decimal('gross_weight', 12, 3)->default(0);
            $table->decimal('volumetric_weight', 12, 3)->default(0);
            $table->decimal('chargeable_weight', 12, 3)->default(0);
            $table->decimal('volume_cbm', 12, 3)->default(0);
            $table->integer('package_count')->default(0);
            $table->decimal('freight_cost', 15, 4)->default(0);
            $table->decimal('insurance_cost', 15, 4)->default(0);
            $table->decimal('customs_duty', 15, 4)->default(0);
            $table->decimal('other_charges', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('expected_departure_date')->nullable();
            $table->date('expected_arrival_date')->nullable();
            $table->date('actual_departure_date')->nullable();
            $table->date('actual_arrival_date')->nullable();
            $table->string('status')->default('pending'); // pending, in_transit, arrived, cleared, delivered
            $table->json('documents')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['shipment_type', 'status']);
            $table->index(['awb_number', 'bl_number', 'tracking_number']);
            $table->index(['expected_arrival_date', 'status']);
        });

        // Freight Expenses
        Schema::create('freight_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freight_shipment_id')->constrained()->cascadeOnDelete();
            $table->string('expense_type'); // freight, insurance, customs, clearing, transport, etc.
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3);
            $table->date('expense_date');
            $table->string('invoice_number')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->boolean('is_paid')->default(false);
            $table->date('paid_date')->nullable();
            $table->timestamps();
            
            $table->index(['freight_shipment_id', 'expense_type']);
        });

        // Landed Cost Allocations
        Schema::create('landed_cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freight_shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('allocated_cost', 15, 4);
            $table->string('allocation_method'); // weight, volume, value, quantity
            $table->decimal('original_cost', 15, 4);
            $table->decimal('total_landed_cost', 15, 4);
            $table->decimal('cost_per_unit', 15, 4);
            $table->string('currency', 3);
            $table->boolean('posted_to_inventory')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            
            $table->unique(['freight_shipment_id', 'product_id', 'warehouse_id']);
            $table->index(['posted_to_inventory', 'posted_at']);
        });

        // Carriers
        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('courier'); // courier, freight, airline, shipping_line
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('tracking_url_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('service_areas')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['type', 'is_active']);
        });

        // Dispatch Batches
        Schema::create('dispatch_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->string('status')->default('pending'); // pending, picking, packed, ready, dispatched, completed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->nullOnDelete();
            $table->string('route_code')->nullable();
            $table->integer('total_orders')->default(0);
            $table->integer('total_items')->default(0);
            $table->decimal('total_weight', 12, 3)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'scheduled_date']);
            $table->index(['warehouse_id', 'status']);
        });

        // Dispatch Items
        Schema::create('dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bin_id')->nullable()->constrained('warehouse_bins')->nullOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->string('status')->default('pending'); // pending, picked, packed, dispatched
            $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('picked_at')->nullable();
            $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('packed_at')->nullable();
            $table->timestamps();
            
            $table->index(['dispatch_batch_id', 'status']);
            $table->index(['order_id', 'status']);
        });

        // Packages
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('package_number')->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('package_type')->nullable(); // box, envelope, pallet
            $table->string('tracking_number')->nullable();
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->nullOnDelete();
            $table->json('contents')->nullable();
            $table->timestamps();
            
            $table->index(['dispatch_batch_id', 'tracking_number']);
        });

        // Drivers
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('vehicle_type')->nullable(); // bike, van, truck
            $table->string('vehicle_number')->nullable();
            $table->string('status')->default('available'); // available, on_route, off_duty
            $table->decimal('cod_limit', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'marketplace_id']);
        });

        // Vehicles
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vehicle_number')->unique();
            $table->string('vehicle_type')->default('van');
            $table->string('make_model')->nullable();
            $table->integer('capacity_kg')->default(0);
            $table->integer('capacity_cbm')->default(0);
            $table->string('fuel_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Delivery Routes
        Schema::create('delivery_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->string('route_code')->unique();
            $table->string('route_name');
            $table->string('region')->nullable();
            $table->json('stops')->nullable();
            $table->decimal('estimated_distance_km', 10, 2)->default(0);
            $table->integer('estimated_duration_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Proof of Deliveries
        Schema::create('proof_of_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dispatch_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status')->default('delivered'); // delivered, failed, returned
            $table->timestamp('delivered_at')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_signature')->nullable(); // base64 or file path
            $table->json('photos')->nullable();
            $table->string('otp_verified')->default(false);
            $table->string('failure_reason')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->decimal('cod_amount', 15, 2)->default(0);
            $table->boolean('cod_collected')->default(false);
            $table->timestamp('cod_collected_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'delivered_at']);
            $table->index(['cod_collected', 'cod_amount']);
        });

        // COD Collections
        Schema::create('cod_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('proof_of_delivery_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('collection_date');
            $table->string('status')->default('pending'); // pending, reconciled, deposited
            $table->date('reconciled_date')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('deposited_date')->nullable();
            $table->string('deposit_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['driver_id', 'status']);
            $table->index(['status', 'collection_date']);
        });

        // Add carrier_id to orders table if not exists
        if (!Schema::hasColumn('orders', 'carrier_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('carrier_id')->nullable()->after('shipping_method')->constrained('carriers')->nullOnDelete();
                $table->string('tracking_number')->nullable()->after('carrier_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cod_collections');
        Schema::dropIfExists('proof_of_deliveries');
        Schema::dropIfExists('delivery_routes');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('dispatch_items');
        Schema::dropIfExists('dispatch_batches');
        Schema::dropIfExists('carriers');
        Schema::dropIfExists('landed_cost_allocations');
        Schema::dropIfExists('freight_expenses');
        Schema::dropIfExists('freight_shipments');
        
        // Remove columns from orders
        if (Schema::hasColumn('orders', 'carrier_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['carrier_id']);
                $table->dropColumn(['carrier_id', 'tracking_number']);
            });
        }
    }
};
