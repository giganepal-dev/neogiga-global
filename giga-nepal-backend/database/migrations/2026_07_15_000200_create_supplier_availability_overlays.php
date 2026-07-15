<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_availabilities')) {
            return;
        }

        Schema::create('supplier_availabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->restrictOnDelete();
            $table->foreignId('catalog_distributor_offer_id')->constrained('catalog_distributor_offers')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('source_part_id')->index();
            $table->string('supplier_name');
            $table->unsignedBigInteger('observed_offer_stock');
            $table->unsignedInteger('desired_quantity');
            $table->unsignedInteger('total_available_quantity');
            $table->unsignedInteger('allocated_quantity');
            $table->decimal('allocation_percent', 5, 2);
            $table->string('stock_type', 40)->default('supplier_virtual');
            $table->string('availability_status', 40)->default('available_for_quote');
            $table->boolean('quote_only')->default(true);
            $table->boolean('is_reservable')->default(false);
            $table->boolean('is_fulfillable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('allocation_policy', 160);
            $table->timestamp('source_observed_at')->nullable();

            // Required source lineage. These are deliberately first-class
            // fields so an availability overlay can always be audited without
            // dereferencing mutable product or import records.
            $table->string('source_name');
            $table->text('source_url');
            $table->text('source_file');
            $table->text('source_page_url');
            $table->timestamp('downloaded_at');
            $table->timestamp('imported_at');
            $table->string('data_year', 10);
            $table->text('license_note');
            $table->string('confidence_level', 120);
            $table->jsonb('original_raw_value');
            $table->jsonb('normalized_value');
            $table->timestamps();

            $table->unique(
                ['catalog_distributor_offer_id', 'warehouse_id'],
                'supplier_availability_offer_warehouse_unique'
            );
            $table->index(
                ['product_id', 'is_active', 'quote_only'],
                'supplier_availability_product_state_idx'
            );
            $table->index(
                ['warehouse_id', 'availability_status', 'is_active'],
                'supplier_availability_warehouse_state_idx'
            );
        });
    }

    public function down(): void
    {
        // Upgrade-only migration: automatic rollback must not discard
        // provenance or supplier availability audit history.
    }
};
