<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 2: Complete Warehouse Management System
     * - Warehouse location hierarchy (Zone → Aisle → Rack → Shelf → Bin)
     * - Stock counting and adjustments
     * - Batch and serial number tracking
     * - Enhanced inventory movements with location tracking
     */
    public function up(): void
    {
        // Create warehouse location hierarchy tables
        if (!Schema::hasTable('warehouse_zones')) {
            Schema::create('warehouse_zones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->enum('type', ['storage', 'receiving', 'shipping', 'quarantine', 'cold_storage', 'hazmat'])->default('storage');
                $table->decimal('temperature_min', 5, 2)->nullable();
                $table->decimal('temperature_max', 5, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->unique(['warehouse_id', 'code']);
                $table->index(['warehouse_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('warehouse_aisles')) {
            Schema::create('warehouse_aisles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('zone_id')->constrained('warehouse_zones')->onDelete('cascade');
                $table->string('name');
                $table->string('code')->unique();
                $table->integer('sequence')->default(0);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->unique(['zone_id', 'code']);
                $table->index(['zone_id', 'sequence']);
            });
        }

        if (!Schema::hasTable('warehouse_racks')) {
            Schema::create('warehouse_racks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aisle_id')->constrained('warehouse_aisles')->onDelete('cascade');
                $table->string('name');
                $table->string('code')->unique();
                $table->integer('sequence')->default(0);
                $table->integer('levels')->default(1);
                $table->decimal('max_weight_kg', 10, 2)->nullable();
                $table->decimal('max_height_cm', 8, 2)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->unique(['aisle_id', 'code']);
                $table->index(['aisle_id', 'sequence']);
            });
        }

        if (!Schema::hasTable('warehouse_shelves')) {
            Schema::create('warehouse_shelves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rack_id')->constrained('warehouse_racks')->onDelete('cascade');
                $table->string('name');
                $table->string('code')->unique();
                $table->integer('level_number')->default(1);
                $table->integer('sequence')->default(0);
                $table->decimal('max_weight_kg', 10, 2)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->unique(['rack_id', 'code']);
                $table->index(['rack_id', 'level_number']);
            });
        }

        if (!Schema::hasTable('warehouse_bins')) {
            Schema::create('warehouse_bins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shelf_id')->constrained('warehouse_shelves')->onDelete('cascade');
                $table->string('name');
                $table->string('code')->unique();
                $table->integer('sequence')->default(0);
                $table->enum('type', ['standard', 'small_parts', 'pallet', 'bulk', 'cold', 'hazmat'])->default('standard');
                $table->decimal('capacity_volume_m3', 10, 4)->nullable();
                $table->decimal('max_weight_kg', 10, 2)->nullable();
                $table->integer('max_items')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->unique(['shelf_id', 'code']);
                $table->index(['shelf_id', 'is_active']);
                $table->index(['code']); // Fast bin lookup
            });
        }

        // Enhance product_warehouses table with bin tracking
        if (Schema::hasTable('product_warehouses') && !Schema::hasColumn('product_warehouses', 'bin_id')) {
            Schema::table('product_warehouses', function (Blueprint $table) {
                $table->foreignId('bin_id')->nullable()->after('warehouse_id')->constrained('warehouse_bins')->onDelete('set null');
                $table->string('bin_label')->nullable()->after('bin_id');
                $table->index('bin_id');
            });
        }

        // Create batch/lot tracking table
        if (!Schema::hasTable('inventory_batches')) {
            Schema::create('inventory_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
                $table->string('batch_number');
                $table->string('lot_number')->nullable();
                $table->date('manufacturing_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->date('best_before_date')->nullable();
                $table->string('date_code')->nullable();
                $table->string('country_of_origin')->nullable();
                $table->string('manufacturer_part_number')->nullable();
                $table->decimal('quantity_received', 15, 4)->default(0);
                $table->decimal('quantity_available', 15, 4)->default(0);
                $table->decimal('quantity_reserved', 15, 4)->default(0);
                $table->decimal('quantity_sold', 15, 4)->default(0);
                $table->decimal('quantity_returned', 15, 4)->default(0);
                $table->decimal('quantity_damaged', 15, 4)->default(0);
                $table->decimal('unit_cost', 15, 4)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->enum('status', ['active', 'quarantined', 'expired', 'recalled', 'consumed'])->default('active');
                $table->text('quality_notes')->nullable();
                $table->json('certifications')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->unique(['product_id', 'warehouse_id', 'batch_number']);
                $table->index(['batch_number']);
                $table->index(['lot_number']);
                $table->index(['expiry_date']);
                $table->index(['status']);
                $table->index(['product_id', 'status']);
            });
        }

        // Create serial number tracking table
        if (!Schema::hasTable('inventory_serials')) {
            Schema::create('inventory_serials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
                $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->onDelete('set null');
                $table->string('serial_number');
                $table->string('manufacturer_serial')->nullable();
                $table->date('manufacturing_date')->nullable();
                $table->date('warranty_start_date')->nullable();
                $table->date('warranty_end_date')->nullable();
                $table->integer('warranty_months')->nullable();
                $table->string('warranty_provider')->nullable();
                $table->enum('status', [
                    'in_stock',
                    'reserved',
                    'sold',
                    'returned',
                    'damaged',
                    'lost',
                    'in_repair',
                    'quarantined'
                ])->default('in_stock');
                $table->foreignId('assigned_customer_id')->nullable()->constrained('customers')->onDelete('set null');
                $table->foreignId('sale_id')->nullable()->constrained('orders')->onDelete('set null');
                $table->text('notes')->nullable();
                $table->json('test_results')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->unique(['product_id', 'warehouse_id', 'serial_number']);
                $table->index(['serial_number']);
                $table->index(['status']);
                $table->index(['product_id', 'status']);
                $table->index(['warranty_end_date']);
            });
        }

        // Create stock count/stocktake table
        if (!Schema::hasTable('stock_counts')) {
            Schema::create('stock_counts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
                $table->foreignId('zone_id')->nullable()->constrained('warehouse_zones')->onDelete('set null');
                $table->foreignId('conducted_by')->constrained('users')->onDelete('restrict');
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->string('reference_number')->unique();
                $table->enum('type', ['scheduled', 'cycle', 'spot', 'annual', 'adjustment'])->default('scheduled');
                $table->enum('status', ['draft', 'in_progress', 'review', 'approved', 'rejected', 'completed'])->default('draft');
                $table->datetime('started_at')->nullable();
                $table->datetime('completed_at')->nullable();
                $table->datetime('approved_at')->nullable();
                $table->text('reason')->nullable();
                $table->text('notes')->nullable();
                $table->decimal('total_variance_value', 15, 2)->default(0);
                $table->integer('items_counted')->default(0);
                $table->integer('items_with_variance')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['warehouse_id', 'status']);
                $table->index(['reference_number']);
                $table->index(['type', 'status']);
            });
        }

        // Create stock count items table
        if (!Schema::hasTable('stock_count_items')) {
            Schema::create('stock_count_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_count_id')->constrained('stock_counts')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('bin_id')->nullable()->constrained('warehouse_bins')->onDelete('set null');
                $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->onDelete('set null');
                $table->string('serial_number')->nullable();
                $table->decimal('system_quantity', 15, 4)->default(0);
                $table->decimal('counted_quantity', 15, 4)->default(0);
                $table->decimal('variance_quantity', 15, 4)->default(0);
                $table->decimal('unit_cost', 15, 4)->nullable();
                $table->decimal('variance_value', 15, 2)->default(0);
                $table->enum('variance_reason', [
                    'not_found',
                    'found_extra',
                    'damaged',
                    'expired',
                    'misplaced',
                    'data_error',
                    'theft',
                    'other'
                ])->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('counted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->datetime('counted_at')->nullable();
                $table->boolean('is_adjusted')->default(false);
                $table->foreignId('adjustment_movement_id')->nullable()->constrained('inventory_movements')->onDelete('set null');
                $table->timestamps();
                
                $table->unique(['stock_count_id', 'product_id', 'bin_id', 'batch_id', 'serial_number']);
                $table->index(['stock_count_id']);
                $table->index(['product_id']);
                $table->index(['is_adjusted']);
            });
        }

        // Enhance inventory_movements table with location tracking
        if (Schema::hasTable('inventory_movements')) {
            $connection = Schema::getConnection();
            $schemaBuilder = $connection->getSchemaBuilder();
            
            if (!$schemaBuilder->hasColumn('inventory_movements', 'from_bin_id')) {
                Schema::table('inventory_movements', function (Blueprint $table) {
                    $table->foreignId('from_bin_id')->nullable()->after('from_warehouse_id')->constrained('warehouse_bins')->onDelete('set null');
                    $table->foreignId('to_bin_id')->nullable()->after('to_warehouse_id')->constrained('warehouse_bins')->onDelete('set null');
                    $table->foreignId('batch_id')->nullable()->after('to_bin_id')->constrained('inventory_batches')->onDelete('set null');
                    $table->string('serial_number')->nullable()->after('batch_id');
                    $table->index('from_bin_id');
                    $table->index('to_bin_id');
                    $table->index('batch_id');
                    $table->index('serial_number');
                });
            }
        }

        // Add bin tracking to inventory_reservations
        if (Schema::hasTable('inventory_reservations') && !Schema::hasColumn('inventory_reservations', 'bin_id')) {
            Schema::table('inventory_reservations', function (Blueprint $table) {
                $table->foreignId('bin_id')->nullable()->after('warehouse_id')->constrained('warehouse_bins')->onDelete('set null');
                $table->foreignId('batch_id')->nullable()->after('bin_id')->constrained('inventory_batches')->onDelete('set null');
                $table->string('serial_number')->nullable()->after('batch_id');
                $table->index('bin_id');
                $table->index('batch_id');
                $table->index('serial_number');
            });
        }

        // Create warehouse activity log
        if (!Schema::hasTable('warehouse_activity_logs')) {
            Schema::create('warehouse_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('activity_type');
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('location_path')->nullable(); // Zone/Aisle/Rack/Shelf/Bin
                $table->text('description');
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                
                $table->index(['warehouse_id', 'activity_type']);
                $table->index(['user_id', 'created_at']);
                $table->index(['entity_type', 'entity_id']);
            });
        }

        // Seed default location hierarchy for existing warehouses
        DB::statement('
            INSERT INTO warehouse_zones (warehouse_id, name, code, type, created_at, updated_at)
            SELECT id, CONCAT("Main Storage - ", name), CONCAT("ZONE-", LPAD(id, 4, "0")), "storage", NOW(), NOW()
            FROM warehouses
            ON DUPLICATE KEY UPDATE updated_at=NOW()
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_activity_logs');
        
        if (Schema::hasTable('stock_count_items')) {
            Schema::table('stock_count_items', function (Blueprint $table) {
                $table->dropForeign(['adjustment_movement_id']);
            });
            Schema::dropIfExists('stock_count_items');
        }
        
        Schema::dropIfExists('stock_counts');
        Schema::dropIfExists('inventory_serials');
        Schema::dropIfExists('inventory_batches');
        
        if (Schema::hasTable('product_warehouses')) {
            Schema::table('product_warehouses', function (Blueprint $table) {
                $table->dropForeign(['bin_id']);
                $table->dropColumn(['bin_id', 'bin_label']);
            });
        }
        
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->dropForeign(['from_bin_id']);
                $table->dropForeign(['to_bin_id']);
                $table->dropForeign(['batch_id']);
                $table->dropColumn(['from_bin_id', 'to_bin_id', 'batch_id', 'serial_number']);
            });
        }
        
        if (Schema::hasTable('inventory_reservations')) {
            Schema::table('inventory_reservations', function (Blueprint $table) {
                $table->dropForeign(['bin_id']);
                $table->dropForeign(['batch_id']);
                $table->dropColumn(['bin_id', 'batch_id', 'serial_number']);
            });
        }
        
        Schema::dropIfExists('warehouse_bins');
        Schema::dropIfExists('warehouse_shelves');
        Schema::dropIfExists('warehouse_racks');
        Schema::dropIfExists('warehouse_aisles');
        Schema::dropIfExists('warehouse_zones');
    }
};
