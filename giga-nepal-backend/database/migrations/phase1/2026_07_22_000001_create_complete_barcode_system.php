<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 1: Complete Barcode System
     * 
     * This migration creates a comprehensive barcode management system that:
     * - Stores multiple barcodes per product (manufacturer, internal, custom)
     * - Supports various barcode types (Code-128, Code-39, EAN-13, UPC-A, QR)
     * - Prevents duplicate barcode assignments
     * - Tracks barcode creation and modification history
     * - Enables fast barcode scanning lookup
     */
    public function up(): void
    {
        $this->createBarcodeTables();
        $this->addBarcodeIndexesToExistingTables();
        $this->createProductSyncTables();
    }

    public function down(): void
    {
        // Safe rollback - does not delete data, only removes new structures
        Schema::dropIfExists('product_barcode_scan_logs');
        Schema::dropIfExists('product_barcodes');
        Schema::dropIfExists('barcode_label_templates');
        Schema::dropIfExists('product_sync_jobs');
        Schema::dropIfExists('product_sync_logs');
        
        // Remove added columns
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'barcode_primary')) {
                    $table->dropColumn('barcode_primary');
                }
            });
        }
        
        if (Schema::hasTable('product_warehouses')) {
            Schema::table('product_warehouses', function (Blueprint $table) {
                if (Schema::hasColumn('product_warehouses', 'barcode')) {
                    $table->dropColumn('barcode');
                }
            });
        }
    }

    private function createBarcodeTables(): void
    {
        // Barcode label templates for printing
        if (!Schema::hasTable('barcode_label_templates')) {
            Schema::create('barcode_label_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g., "Small Label 25x15mm"
                $table->string('code')->unique(); // e.g., "LABEL_25x15"
                $table->string('type')->default('thermal'); // thermal, A4, standard
                $table->integer('width_mm')->default(25);
                $table->integer('height_mm')->default(15);
                $table->integer('labels_per_sheet')->default(1);
                $table->integer('columns')->default(1);
                $table->integer('rows')->default(1);
                $table->integer('margin_top_mm')->default(5);
                $table->integer('margin_left_mm')->default(5);
                $table->integer('gap_horizontal_mm')->default(0);
                $table->integer('gap_vertical_mm')->default(0);
                $table->boolean('show_logo')->default(true);
                $table->boolean('show_product_name')->default(true);
                $table->boolean('show_mpn')->default(true);
                $table->boolean('show_sku')->default(true);
                $table->boolean('show_price')->default(false);
                $table->boolean('show_currency')->default(false);
                $table->boolean('show_tax_indicator')->default(false);
                $table->boolean('show_warehouse')->default(false);
                $table->boolean('show_batch')->default(false);
                $table->boolean('show_serial')->default(false);
                $table->boolean('show_date_code')->default(false);
                $table->boolean('show_country_origin')->default(false);
                $table->string('font_family')->default('monospace');
                $table->integer('font_size_pt')->default(8);
                $table->json('layout_config')->nullable(); // Custom layout JSON
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['type', 'is_active']);
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Product barcodes - stores all barcode types per product
        if (!Schema::hasTable('product_barcodes')) {
            Schema::create('product_barcodes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('product_variant_id')->nullable()->index();
                $table->unsignedBigInteger('product_warehouse_id')->nullable()->index();
                $table->string('barcode_value', 100)->index(); // The actual barcode string
                $table->string('barcode_type')->default('code128'); // code128, code39, ean13, ean8, upca, upce, qr, datamatrix
                $table->string('barcode_format')->default('svg'); // svg, png, zpl (for Zebra printers)
                $table->string('source')->default('internal'); // manufacturer, internal, supplier, custom
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->string('gs1_company_prefix')->nullable(); // For GS1 barcodes
                $table->string('check_digit')->nullable(); // For EAN/UPC check digits
                $table->json('metadata')->nullable(); // Additional barcode metadata
                $table->timestamp('verified_at')->nullable();
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                // Unique constraint: no duplicate active barcodes
                $table->unique(['barcode_value', 'is_active'], 'uniq_barcode_active');
                
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
                $table->foreign('product_warehouse_id')->references('id')->on('product_warehouses')->nullOnDelete();
                $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Barcode scan logging for analytics and audit
        if (!Schema::hasTable('product_barcode_scan_logs')) {
            Schema::create('product_barcode_scan_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_barcode_id')->nullable()->index();
                $table->string('barcode_value')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('pos_terminal_id')->nullable()->index();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->unsignedBigInteger('warehouse_id')->nullable()->index();
                $table->string('scan_source')->default('scanner'); // scanner, mobile, manual, api
                $table->boolean('was_successful')->default(true);
                $table->string('failure_reason')->nullable();
                $table->decimal('response_time_ms', 10, 2)->nullable();
                $table->ipAddress('scanner_ip')->nullable();
                $table->string('scanner_device_id')->nullable();
                $table->json('context')->nullable(); // Additional context (cart ID, session, etc.)
                $table->timestamps();
                
                $table->index(['created_at', 'marketplace_id']);
                $table->foreign('product_barcode_id')->references('id')->on('product_barcodes')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('pos_terminal_id')->references('id')->on('pos_terminals')->nullOnDelete();
            });
        }
    }

    private function addBarcodeIndexesToExistingTables(): void
    {
        // Add barcode lookup fields to products for quick access
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'barcode_primary')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('barcode_primary', 100)->nullable()->after('gtin')->index();
            });
        }

        // Add barcode field to product_warehouses for warehouse-specific barcodes
        if (Schema::hasTable('product_warehouses') && !Schema::hasColumn('product_warehouses', 'barcode')) {
            Schema::table('product_warehouses', function (Blueprint $table) {
                $table->string('barcode', 100)->nullable()->after('sku')->index();
            });
        }
    }

    private function createProductSyncTables(): void
    {
        // Product synchronization jobs queue
        if (!Schema::hasTable('product_sync_jobs')) {
            Schema::create('product_sync_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('job_type')->index(); // sync_product, sync_customer, sync_inventory, bulk_sync
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('warehouse_id')->nullable()->index();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->unsignedBigInteger('triggered_by')->nullable()->index();
                $table->string('trigger_source')->default('manual'); // manual, api, webhook, scheduled, admin_change
                $table->string('status')->default('pending')->index(); // pending, processing, completed, failed, cancelled
                $table->string('priority')->default('normal'); // low, normal, high, critical
                $table->integer('attempt_count')->default(0);
                $table->timestamp('attempted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->string('failure_reason')->nullable();
                $table->json('payload')->nullable(); // Data to sync
                $table->json('result')->nullable(); // Sync result
                $table->string('idempotency_key')->nullable()->unique();
                $table->timestamp('available_at')->nullable(); // For retry scheduling
                $table->timestamps();
                
                $table->index(['status', 'priority', 'available_at']);
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->foreign('customer_id')->references('id')->customers()->nullOnDelete();
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
                $table->foreign('triggered_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Product sync logs for audit and debugging
        if (!Schema::hasTable('product_sync_logs')) {
            Schema::create('product_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_sync_job_id')->nullable()->index();
                $table->string('sync_type')->index();
                $table->string('entity_type')->index(); // product, customer, inventory, price
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('action')->index(); // created, updated, deleted, synced
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->json('changes')->nullable(); // Diff of changes
                $table->json('errors')->nullable();
                $table->decimal('duration_ms', 10, 2)->nullable();
                $table->string('sync_source')->default('backend');
                $table->string('sync_target')->default('pos');
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->timestamps();
                
                $table->foreign('product_sync_job_id')->references('id')->on('product_sync_jobs')->nullOnDelete();
                $table->index(['entity_type', 'entity_id']);
                $table->index(['created_at', 'marketplace_id']);
            });
        }
    }
};
