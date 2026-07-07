<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP procurement foundation (NEOGIGA_ERP_ADAPTATION_COMMAND) — Suppliers,
 * Purchase Orders, and atomic document numbering. Additive only; references
 * products/warehouses/marketplaces/users read-only via nullable soft links.
 * All monetary totals are computed server-side (never client-trusted).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // PO, RFQ, QUO, ...
            $table->string('prefix')->default('');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(5);
            $table->string('period')->nullable();     // null|yearly|monthly reset marker
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('contact_name')->nullable();
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->string('currency', 3)->default('USD');
            $table->string('tax_number')->nullable();
            $table->json('address')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('status')->default('active')->index(); // active|inactive
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->unsignedBigInteger('warehouse_id')->nullable()->index();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('draft')->index(); // draft|ordered|partially_received|received|cancelled
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('shipping_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->date('expected_at')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index(); // soft link to products
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->decimal('quantity_ordered', 15, 3)->default(0);
            $table->decimal('quantity_received', 15, 3)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0); // server-computed
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('document_number_sequences');
    }
};
