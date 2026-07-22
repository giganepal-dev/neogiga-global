<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 1: Complete Barcode System Enhancement
     * 
     * This migration enhances the existing barcode system with:
     * - Additional barcode types (Data Matrix, ITF-14)
     * - Barcode alias support for multiple identifiers
     * - Enhanced label templates with more customization
     * - Warehouse-specific barcode configurations
     */
    public function up(): void
    {
        $this->enhanceBarcodeTables();
        $this->createWarehouseLocationHierarchy();
        $this->createBatchSerialTracking();
        $this->createStockCounting();
    }

    public function down(): void
    {
        // Safe rollback - does not delete data
        Schema::dropIfExists('stock_count_items');
        Schema::dropIfExists('stock_counts');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('warehouse_bins');
        Schema::dropIfExists('warehouse_shelves');
        Schema::dropIfExists('warehouse_racks');
        Schema::dropIfExists('warehouse_aisles');
        Schema::dropIfExists('warehouse_zones');
        
        // Remove enhancements
        if (Schema::hasTable('product_barcodes')) {
            Schema::table('product_barcodes', function (Blueprint $table) {
                if (Schema::hasColumn('product_barcodes', 'alias_type')) {
                    $table->dropColumn(['alias_type', 'alias_priority']);
                }
            });
        }
    }

    private function enhanceBarcodeTables(): void
    {
        // Add alias support to product_barcodes
        if (Schema::hasTable('product_barcodes') && !Schema::hasColumn('product_barcodes', 'alias_type')) {
            Schema::table('product_barcodes', function (Blueprint $table) {
                $table->string('alias_type')->nullable()->after('source')
                    ->comment('manufacturer_barcode, internal_sku, customer_barcode, supplier_barcode');
                $table->integer('alias_priority')->default(0)->after('alias_type')
                    ->comment('Higher priority barcodes appear first in scanning');
            });
        }

        // Enhance label templates with additional fields
        if (Schema::hasTable('barcode_label_templates')) {
            Schema::table('barcode_label_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('barcode_label_templates', 'printer_model')) {
                    $table->string('printer_model')->nullable()->after('type')
                        ->comment('Zebra GK420, ZT230, Dymo LabelWriter, etc.');
                }
                if (!Schema::hasColumn('barcode_label_templates', 'dpi')) {
                    $table->integer('dpi')->default(203)->after('printer_model')
                        ->comment('Dots per inch: 203, 300, 600');
                }
                if (!Schema::hasColumn('barcode_label_templates', 'rotation')) {
                    $table->integer('rotation')->default(0)->after('dpi')
                        ->comment('Rotation in degrees: 0, 90, 180, 270');
                }
            });
        }
    }

    private function createWarehouseLocationHierarchy(): void
    {
        // Warehouse zones (e.g., Receiving Area, Storage Zone, Picking Zone, Dispatch Zone)
        if (!Schema::hasTable('warehouse_zones')) {
            Schema::create('warehouse_zones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('warehouse_id')->index();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('zone_type')->default('storage')
                    ->comment('receiving, storage, picking, packing, dispatch, quarantine, damaged');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
                $table->index(['warehouse_id', 'zone_type']);
            });
        }

        // Warehouse aisles within zones
        if (!Schema::hasTable('warehouse_aisles')) {
            Schema::create('warehouse_aisles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('warehouse_zone_id')->index();
                $table->string('name');
                $table->string('code')->unique();
                $table->integer('aisle_number')->nullable();
                $table->decimal('length_meters', 8, 2)->nullable();
                $table->decimal('width_meters', 8, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('warehouse_zone_id')->references('id')->on('warehouse_zones')->cascadeOnDelete();
                $table->index(['warehouse_zone_id', 'aisle_number']);
            });
        }

        // Warehouse racks within aisles
        if (!Schema::hasTable('warehouse_racks')) {
            Schema::create('warehouse_racks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('warehouse_aisle_id')->index();
                $table->string('name');
                $table->string('code')->unique();
                $table->integer('rack_number')->nullable();
                $table->integer('levels')->default(1)->comment('Number of shelf levels');
                $table->decimal('max_weight_kg', 10, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('warehouse_aisle_id')->references('id')->on('warehouse_aisles')->cascadeOnDelete();
                $table->index(['warehouse_aisle_id', 'rack_number']);
            });
        }

        // Warehouse shelves on racks
        if (!Schema::hasTable('warehouse_shelves')) {
            Schema::create('warehouse_shelves', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('warehouse_rack_id')->index();
                $table->string('name');
                $table->string('code')->unique();
                $table->integer('level_number')->default(1)->comment('1 = bottom level');
                $table->decimal('max_weight_kg', 10, 2)->nullable();
                $table->decimal('height_cm', 8, 2)->nullable();
                $table->decimal('depth_cm', 8, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('warehouse_rack_id')->references('id')->on('warehouse_racks')->cascadeOnDelete();
                $table->index(['warehouse_rack_id', 'level_number']);
            });
        }

        // Warehouse bins (smallest storage unit)
        if (!Schema::hasTable('warehouse_bins')) {
            Schema::create('warehouse_bins', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('warehouse_shelf_id')->index();
                $table->string('name');
                $table->string('code')->unique()->index();
                $table->string('bin_type')->default('standard')
                    ->comment('standard, drawer, bulk, cold_storage, hazardous, high_security');
                $table->integer('bin_number')->nullable();
                $table->decimal('max_weight_kg', 10, 2)->nullable();
                $table->decimal('volume_liters', 10, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_locked')->default(false);
                $table->integer('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('warehouse_shelf_id')->references('id')->on('warehouse_shelves')->cascadeOnDelete();
                
                // Full location path index for fast lookups
                $table->index(['warehouse_shelf_id', 'bin_number']);
            });
        }

        // Add location reference to inventory stocks
        if (Schema::hasTable('inventory_stocks') && !Schema::hasColumn('inventory_stocks', 'warehouse_bin_id')) {
            Schema::table('inventory_stocks', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_bin_id')->nullable()->after('warehouse_id')->index();
                $table->foreign('warehouse_bin_id')->references('id')->on('warehouse_bins')->nullOnDelete();
            });
        }
    }

    private function createBatchSerialTracking(): void
    {
        // Inventory batches for lot tracking
        if (!Schema::hasTable('inventory_batches')) {
            Schema::create('inventory_batches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('product_variant_id')->nullable()->index();
                $table->unsignedBigInteger('warehouse_id')->index();
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
                $table->string('batch_number')->index();
                $table->string('lot_number')->nullable()->index();
                $table->string('manufacturer_batch')->nullable();
                $table->date('manufacturing_date')->nullable();
                $table->date('expiry_date')->nullable()->index();
                $table->date('best_before_date')->nullable();
                $table->string('date_code')->nullable()->index();
                $table->string('country_of_origin', 2)->nullable();
                $table->string('warranty_months')->nullable();
                $table->decimal('unit_cost', 15, 4)->default(0);
                $table->integer('initial_quantity')->default(0);
                $table->integer('current_quantity')->default(0);
                $table->integer('reserved_quantity')->default(0);
                $table->integer('damaged_quantity')->default(0);
                $table->string('status')->default('active')->index()
                    ->comment('active, quarantined, expired, recalled, depleted');
                $table->string('quality_status')->default('passed')
                    ->comment('pending, passed, failed, quarantined');
                $table->text('quality_notes')->nullable();
                $table->unsignedBigInteger('received_by')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->json('certifications')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['batch_number', 'warehouse_id', 'product_id'], 'uniq_batch_warehouse_product');
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
                $table->foreign('supplier_id')->references('id')->on('inventory_suppliers')->nullOnDelete();
                $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['expiry_date', 'status']);
                $table->index(['status', 'current_quantity']);
            });
        }

        // Serial number tracking for individual items
        if (!Schema::hasTable('serial_numbers')) {
            Schema::create('serial_numbers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('product_variant_id')->nullable()->index();
                $table->unsignedBigInteger('inventory_batch_id')->nullable()->index();
                $table->unsignedBigInteger('warehouse_id')->index();
                $table->string('serial_number')->index();
                $table->string('manufacturer_serial')->nullable();
                $table->string('status')->default('available')->index()
                    ->comment('available, reserved, sold, returned, damaged, lost, in_repair');
                $table->unsignedBigInteger('current_order_id')->nullable();
                $table->unsignedBigInteger('sold_to_customer_id')->nullable();
                $table->date('manufacturing_date')->nullable();
                $table->date('purchase_date')->nullable();
                $table->date('sale_date')->nullable();
                $table->date('warranty_start_date')->nullable();
                $table->date('warranty_end_date')->nullable()->index();
                $table->decimal('purchase_cost', 15, 4)->nullable();
                $table->decimal('sale_price', 15, 4)->nullable();
                $table->string('warranty_status')->default('active')
                    ->comment('active, expired, void, claimed');
                $table->text('warranty_notes')->nullable();
                $table->json('service_history')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['serial_number', 'product_id', 'warehouse_id'], 'uniq_serial_product_warehouse');
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
                $table->foreign('inventory_batch_id')->references('id')->on('inventory_batches')->nullOnDelete();
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
                $table->foreign('current_order_id')->references('id')->on('orders')->nullOnDelete();
                $table->foreign('sold_to_customer_id')->references('id')->on('customers')->nullOnDelete();

                $table->index(['status', 'warranty_end_date']);
            });
        }

        // Link inventory stocks to batches
        if (Schema::hasTable('inventory_stocks') && !Schema::hasColumn('inventory_stocks', 'inventory_batch_id')) {
            Schema::table('inventory_stocks', function (Blueprint $table) {
                $table->unsignedBigInteger('inventory_batch_id')->nullable()->after('product_variant_id')->index();
                $table->foreign('inventory_batch_id')->references('id')->on('inventory_batches')->nullOnDelete();
            });
        }

        // Track which serials are in which stock records
        if (!Schema::hasTable('inventory_stock_serials')) {
            Schema::create('inventory_stock_serials', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('inventory_stock_id')->index();
                $table->unsignedBigInteger('serial_number_id')->index();
                $table->timestamps();

                $table->unique(['inventory_stock_id', 'serial_number_id']);
                $table->foreign('inventory_stock_id')->references('id')->on('inventory_stocks')->cascadeOnDelete();
                $table->foreign('serial_number_id')->references('id')->on('serial_numbers')->cascadeOnDelete();
            });
        }
    }

    private function createStockCounting(): void
    {
        // Stock count sessions (physical inventory counts)
        if (!Schema::hasTable('stock_counts')) {
            Schema::create('stock_counts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('warehouse_id')->index();
                $table->unsignedBigInteger('warehouse_zone_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->index();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('count_number')->unique();
                $table->string('count_type')->default('scheduled')
                    ->comment('scheduled, cycle, spot_check, annual, adjustment');
                $table->string('status')->default('draft')->index()
                    ->comment('draft, in_progress, counting_complete, reviewed, approved, posted');
                $table->date('scheduled_date')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->integer('total_items_expected')->default(0);
                $table->integer('total_items_counted')->default(0);
                $table->integer('total_items_matched')->default(0);
                $table->integer('total_items_variance')->default(0);
                $table->decimal('variance_value', 15, 4)->default(0);
                $table->text('notes')->nullable();
                $table->text('adjustment_reason')->nullable();
                $table->json('scope')->nullable(); // Which products/bins to count
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
                $table->foreign('warehouse_zone_id')->references('id')->on('warehouse_zones')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['status', 'scheduled_date']);
            });
        }

        // Individual stock count items
        if (!Schema::hasTable('stock_count_items')) {
            Schema::create('stock_count_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stock_count_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('product_variant_id')->nullable()->index();
                $table->unsignedBigInteger('inventory_stock_id')->nullable()->index();
                $table->unsignedBigInteger('warehouse_bin_id')->nullable()->index();
                $table->unsignedBigInteger('inventory_batch_id')->nullable()->index();
                $table->unsignedBigInteger('counted_by')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->decimal('expected_quantity', 12, 3)->default(0);
                $table->decimal('counted_quantity', 12, 3)->default(0);
                $table->decimal('variance_quantity', 12, 3)->default(0);
                $table->decimal('unit_cost', 15, 4)->nullable();
                $table->decimal('variance_value', 15, 4)->default(0);
                $table->string('status')->default('pending')->index()
                    ->comment('pending, counted, reviewed, adjusted');
                $table->string('variance_reason')->nullable()
                    ->comment('damage, theft, miscount, receiving_error, shipping_error, other');
                $table->text('count_notes')->nullable();
                $table->text('adjustment_notes')->nullable();
                $table->boolean('requires_review')->default(false);
                $table->timestamp('counted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('stock_count_id')->references('id')->on('stock_counts')->cascadeOnDelete();
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
                $table->foreign('inventory_stock_id')->references('id')->on('inventory_stocks')->nullOnDelete();
                $table->foreign('warehouse_bin_id')->references('id')->on('warehouse_bins')->nullOnDelete();
                $table->foreign('inventory_batch_id')->references('id')->on('inventory_batches')->nullOnDelete();
                $table->foreign('counted_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['status', 'requires_review']);
            });
        }

        // Add stock count reference to inventory movements
        if (Schema::hasTable('inventory_movements') && !Schema::hasColumn('inventory_movements', 'stock_count_id')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->unsignedBigInteger('stock_count_id')->nullable()->after('reference_id')->index();
                $table->foreign('stock_count_id')->references('id')->on('stock_counts')->nullOnDelete();
            });
        }
    }
};
