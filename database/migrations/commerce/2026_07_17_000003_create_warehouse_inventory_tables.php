<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates complete warehouse and inventory management tables:
     * - Multi-warehouse support
     * - Bin/location tracking
     * - Stock movements & adjustments
     * - Receiving & put-away
     * - Pick lists & packing
     * - Stock transfers
     * - Cycle counts & audits
     */
    public function up(): void
    {
        // =====================
        // WAREHOUSE CORE TABLES
        // =====================
        if (!Schema::hasTable('warehouses')) {
            Schema::table('warehouses', function (Blueprint $table) {
                if (!Schema::hasColumn('warehouses', 'warehouse_type')) {
                    $table->string('warehouse_type')->default('fulfillment')->after('name'); // fulfillment, returns, damaged, staging
                }
                if (!Schema::hasColumn('warehouses', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('warehouse_type')->index();
                }
                if (!Schema::hasColumn('warehouses', 'manager_id')) {
                    $table->foreignId('manager_id')->nullable()->after('is_active')->constrained('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('warehouses', 'phone')) {
                    $table->string('phone')->nullable()->after('email');
                }
                if (!Schema::hasColumn('warehouses', 'timezone')) {
                    $table->string('timezone')->default('UTC')->after('country_id');
                }
                if (!Schema::hasColumn('warehouses', 'operating_hours')) {
                    $table->json('operating_hours')->nullable()->after('timezone');
                }
            });
        }

        if (!Schema::hasTable('warehouse_locations')) {
            Schema::table('warehouse_locations', function (Blueprint $table) {
                if (!Schema::hasColumn('warehouse_locations', 'location_type')) {
                    $table->string('location_type')->default('bin')->after('name'); // bin, rack, shelf, zone, aisle
                }
                if (!Schema::hasColumn('warehouse_locations', 'parent_id')) {
                    $table->foreignId('parent_id')->nullable()->after('warehouse_id')->constrained('warehouse_locations')->onDelete('cascade');
                }
                if (!Schema::hasColumn('warehouse_locations', 'coordinates')) {
                    $table->json('coordinates')->nullable()->after('parent_id'); // {aisle, rack, shelf, bin}
                }
                if (!Schema::hasColumn('warehouse_locations', 'max_capacity')) {
                    $table->integer('max_capacity')->nullable()->after('coordinates');
                }
                if (!Schema::hasColumn('warehouse_locations', 'current_capacity')) {
                    $table->integer('current_capacity')->default(0)->after('max_capacity');
                }
                if (!Schema::hasColumn('warehouse_locations', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('current_capacity')->index();
                }
            });
        }

        // =====================
        // INVENTORY STOCK TABLES
        // =====================
        if (!Schema::hasTable('inventory_batches')) {
            Schema::create('inventory_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
                $table->string('batch_number')->index();
                $table->string('lot_number')->nullable()->index();
                $table->string('serial_number')->nullable()->unique();
                $table->date('manufactured_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->date('received_date');
                $table->integer('quantity_received')->default(0);
                $table->integer('quantity_available')->default(0);
                $table->integer('quantity_reserved')->default(0);
                $table->integer('quantity_damaged')->default(0);
                $table->decimal('unit_cost', 15, 4)->default(0);
                $table->string('currency_code', 3)->default('USD');
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status')->default('active')->index(); // active, quarantined, expired, depleted
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'warehouse_id']);
                $table->index(['warehouse_id', 'status']);
                $table->unique(['batch_number', 'warehouse_id'], 'batch_warehouse_unique');
            });
        }

        if (!Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_movements', 'warehouse_location_id')) {
                    $table->foreignId('warehouse_location_id')->nullable()->after('warehouse_id')->constrained('warehouse_locations')->nullOnDelete();
                }
                if (!Schema::hasColumn('inventory_movements', 'to_warehouse_location_id')) {
                    $table->foreignId('to_warehouse_location_id')->nullable()->after('warehouse_location_id')->constrained('warehouse_locations')->nullOnDelete();
                }
                if (!Schema::hasColumn('inventory_movements', 'batch_id')) {
                    $table->foreignId('batch_id')->nullable()->after('to_warehouse_location_id')->constrained('inventory_batches')->nullOnDelete();
                }
                if (!Schema::hasColumn('inventory_movements', 'reference_type')) {
                    $table->string('reference_type')->nullable()->after('reference_id'); // order, transfer, adjustment, receiving
                }
                if (!Schema::hasColumn('inventory_movements', 'unit_cost')) {
                    $table->decimal('unit_cost', 15, 4)->default(0)->after('quantity');
                }
                if (!Schema::hasColumn('inventory_movements', 'total_value')) {
                    $table->decimal('total_value', 15, 4)->default(0)->after('unit_cost');
                }
                if (!Schema::hasColumn('inventory_movements', 'running_balance')) {
                    $table->integer('running_balance')->default(0)->after('total_value');
                }
            });
        }

        // =====================
        // RECEIVING TABLES
        // =====================
        if (!Schema::hasTable('receiving_shipments')) {
            Schema::create('receiving_shipments', function (Blueprint $table) {
                $table->id();
                $table->string('receiving_number')->unique()->index();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('carrier')->nullable();
                $table->string('tracking_number')->nullable();
                $table->date('expected_date')->nullable();
                $table->date('received_date')->nullable();
                $table->string('status')->default('expected')->index(); // expected, in_progress, completed, cancelled
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['warehouse_id', 'status']);
            });
        }

        if (!Schema::hasTable('receiving_items')) {
            Schema::create('receiving_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('receiving_shipment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->integer('quantity_expected')->default(0);
                $table->integer('quantity_received')->default(0);
                $table->integer('quantity_rejected')->default(0);
                $table->decimal('unit_cost', 15, 4)->default(0);
                $table->string('rejection_reason')->nullable();
                $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
                $table->string('batch_number')->nullable();
                $table->string('lot_number')->nullable();
                $table->date('expiry_date')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['receiving_shipment_id']);
                $table->index(['product_id']);
            });
        }

        // =====================
        // PICKING & PACKING TABLES
        // =====================
        if (!Schema::hasTable('pick_lists')) {
            Schema::create('pick_lists', function (Blueprint $table) {
                $table->id();
                $table->string('pick_list_number')->unique()->index();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('priority')->default('normal')->index(); // urgent, high, normal, low
                $table->string('status')->default('pending')->index(); // pending, in_progress, completed, cancelled
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['warehouse_id', 'status']);
                $table->index(['assigned_to', 'status']);
            });
        }

        if (!Schema::hasTable('pick_list_items')) {
            Schema::create('pick_list_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pick_list_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
                $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->integer('quantity_to_pick');
                $table->integer('quantity_picked')->default(0);
                $table->integer('quantity_short')->default(0);
                $table->string('status')->default('pending')->index();
                $table->timestamp('picked_at')->nullable();
                $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['pick_list_id', 'status']);
            });
        }

        if (!Schema::hasTable('packing_stations')) {
            Schema::create('packing_stations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->string('station_name');
                $table->string('station_code')->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['warehouse_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('packings')) {
            Schema::create('packings', function (Blueprint $table) {
                $table->id();
                $table->string('packing_number')->unique()->index();
                $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('pick_list_id')->nullable()->constrained('pick_lists')->nullOnDelete();
                $table->foreignId('packing_station_id')->nullable()->constrained('packing_stations')->nullOnDelete();
                $table->string('status')->default('pending')->index(); // pending, in_progress, completed
                $table->string('box_type')->nullable();
                $table->decimal('weight_value', 10, 2)->nullable();
                $table->string('weight_unit')->default('kg');
                $table->json('dimensions')->nullable();
                $table->json('items')->nullable();
                $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('packed_at')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['shipment_id', 'status']);
            });
        }

        // =====================
        // STOCK TRANSFER TABLES
        // =====================
        if (!Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_number')->unique()->index();
                $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('status')->default('pending')->index(); // pending, approved, in_transit, received, completed, cancelled
                $table->text('reason')->nullable();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('requested_at');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('shipped_at')->nullable();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('received_at')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['from_warehouse_id', 'status']);
                $table->index(['to_warehouse_id', 'status']);
            });
        }

        if (!Schema::hasTable('stock_transfer_items')) {
            Schema::create('stock_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('from_warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
                $table->foreignId('to_warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
                $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->integer('quantity_to_transfer');
                $table->integer('quantity_transferred')->default(0);
                $table->integer('quantity_lost')->default(0);
                $table->string('loss_reason')->nullable();
                $table->string('status')->default('pending')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['stock_transfer_id', 'status']);
            });
        }

        // =====================
        // CYCLE COUNT & AUDIT TABLES
        // =====================
        if (!Schema::hasTable('cycle_counts')) {
            Schema::create('cycle_counts', function (Blueprint $table) {
                $table->id();
                $table->string('count_number')->unique()->index();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
                $table->string('count_type')->default('scheduled')->index(); // scheduled, random, discrepancy, annual
                $table->string('status')->default('scheduled')->index(); // scheduled, in_progress, completed, reviewed
                $table->date('scheduled_date');
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->integer('total_items')->default(0);
                $table->integer('counted_items')->default(0);
                $table->integer('discrepancy_count')->default(0);
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['warehouse_id', 'status']);
                $table->index(['scheduled_date']);
            });
        }

        if (!Schema::hasTable('cycle_count_items')) {
            Schema::create('cycle_count_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cycle_count_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
                $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->integer('system_quantity');
                $table->integer('counted_quantity');
                $table->integer('variance');
                $table->string('variance_reason')->nullable();
                $table->boolean('variance_approved')->default(false);
                $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('counted_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['cycle_count_id']);
                $table->index(['product_id']);
            });
        }

        // =====================
        // STOCK ADJUSTMENTS
        // =====================
        if (!Schema::hasTable('stock_adjustments')) {
            Schema::create('stock_adjustments', function (Blueprint $table) {
                $table->id();
                $table->string('adjustment_number')->unique()->index();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->string('adjustment_type')->index(); // increase, decrease, correction
                $table->string('reason')->index(); // damage, loss, found, expiry, return, other
                $table->string('status')->default('pending')->index(); // pending, approved, completed
                $table->text('notes')->nullable();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('requested_at');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['warehouse_id', 'status']);
            });
        }

        if (!Schema::hasTable('stock_adjustment_items')) {
            Schema::create('stock_adjustment_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
                $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->integer('quantity_before');
                $table->integer('quantity_adjusted');
                $table->integer('quantity_after');
                $table->decimal('unit_cost', 15, 4)->default(0);
                $table->decimal('total_value_change', 15, 4)->default(0);
                $table->text('reason_notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['stock_adjustment_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('cycle_count_items');
        Schema::dropIfExists('cycle_counts');
        Schema::dropIfExists('packings');
        Schema::dropIfExists('packing_stations');
        Schema::dropIfExists('pick_list_items');
        Schema::dropIfExists('pick_lists');
        Schema::dropIfExists('receiving_items');
        Schema::dropIfExists('receiving_shipments');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('inventory_batches');
        
        // Remove added columns from existing tables
        Schema::table('inventory_movements', function (Blueprint $table) {
            $columns = ['running_balance', 'total_value', 'unit_cost', 'reference_type', 'batch_id', 'to_warehouse_location_id', 'warehouse_location_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('inventory_movements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('warehouse_locations', function (Blueprint $table) {
            $columns = ['is_active', 'current_capacity', 'max_capacity', 'coordinates', 'parent_id', 'location_type'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('warehouse_locations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $columns = ['operating_hours', 'timezone', 'phone', 'manager_id', 'is_active', 'warehouse_type'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('warehouses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
