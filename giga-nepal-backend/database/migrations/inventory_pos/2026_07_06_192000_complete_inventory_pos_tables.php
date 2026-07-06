<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->completeInventory();
        $this->createProcurement();
        $this->completePos();
    }

    public function down(): void
    {
        // Additive production migration. Do not drop inventory/POS data automatically.
    }

    private function completeInventory(): void
    {
        $this->table('inventory_stocks', [
            'quantity_on_hand' => fn (Blueprint $t) => $t->integer('quantity_on_hand')->default(0),
            'reorder_quantity' => fn (Blueprint $t) => $t->integer('reorder_quantity')->default(0),
            'unit_cost' => fn (Blueprint $t) => $t->decimal('unit_cost', 15, 4)->nullable(),
            'last_movement_at' => fn (Blueprint $t) => $t->timestamp('last_movement_at')->nullable()->index(),
        ]);

        $this->table('inventory_movements', [
            'inventory_stock_id' => fn (Blueprint $t) => $t->unsignedBigInteger('inventory_stock_id')->nullable()->index(),
            'marketplace_id' => fn (Blueprint $t) => $t->unsignedBigInteger('marketplace_id')->nullable()->index(),
            'unit_cost' => fn (Blueprint $t) => $t->decimal('unit_cost', 15, 4)->nullable(),
            'idempotency_key' => fn (Blueprint $t) => $t->string('idempotency_key')->nullable()->unique(),
        ]);

        $this->table('reserved_stocks', [
            'idempotency_key' => fn (Blueprint $t) => $t->string('idempotency_key')->nullable()->unique(),
            'used_at' => fn (Blueprint $t) => $t->timestamp('used_at')->nullable(),
            'released_at' => fn (Blueprint $t) => $t->timestamp('released_at')->nullable(),
            'metadata' => fn (Blueprint $t) => $t->json('metadata')->nullable(),
        ]);
    }

    private function createProcurement(): void
    {
        $this->create('inventory_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('status')->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $this->create('inventory_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $this->create('inventory_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_purchase_order_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->string('sku')->nullable()->index();
            $table->decimal('quantity_ordered', 12, 3)->default(0);
            $table->decimal('quantity_received', 12, 3)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    private function completePos(): void
    {
        $this->table('pos_terminals', [
            'terminal_name' => fn (Blueprint $t) => $t->string('terminal_name')->nullable(),
            'terminal_code' => fn (Blueprint $t) => $t->string('terminal_code')->nullable()->unique(),
            'marketplace_id' => fn (Blueprint $t) => $t->unsignedBigInteger('marketplace_id')->nullable()->index(),
            'vendor_id' => fn (Blueprint $t) => $t->unsignedBigInteger('vendor_id')->nullable()->index(),
            'warehouse_id' => fn (Blueprint $t) => $t->unsignedBigInteger('warehouse_id')->nullable()->index(),
            'status' => fn (Blueprint $t) => $t->string('status')->default('active')->index(),
            'location' => fn (Blueprint $t) => $t->string('location')->nullable(),
            'metadata' => fn (Blueprint $t) => $t->json('metadata')->nullable(),
        ]);

        $this->table('pos_sessions', [
            'pos_terminal_id' => fn (Blueprint $t) => $t->unsignedBigInteger('pos_terminal_id')->nullable()->index(),
            'marketplace_id' => fn (Blueprint $t) => $t->unsignedBigInteger('marketplace_id')->nullable()->index(),
            'vendor_id' => fn (Blueprint $t) => $t->unsignedBigInteger('vendor_id')->nullable()->index(),
            'warehouse_id' => fn (Blueprint $t) => $t->unsignedBigInteger('warehouse_id')->nullable()->index(),
            'user_id' => fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->index(),
            'session_number' => fn (Blueprint $t) => $t->string('session_number')->nullable()->unique(),
            'status' => fn (Blueprint $t) => $t->string('status')->default('open')->index(),
            'opening_cash' => fn (Blueprint $t) => $t->decimal('opening_cash', 15, 4)->default(0),
            'closing_cash' => fn (Blueprint $t) => $t->decimal('closing_cash', 15, 4)->nullable(),
            'opened_at' => fn (Blueprint $t) => $t->timestamp('opened_at')->nullable()->index(),
            'closed_at' => fn (Blueprint $t) => $t->timestamp('closed_at')->nullable()->index(),
            'notes' => fn (Blueprint $t) => $t->text('notes')->nullable(),
            'metadata' => fn (Blueprint $t) => $t->json('metadata')->nullable(),
        ]);

        $this->table('pos_sales', [
            'pos_session_id' => fn (Blueprint $t) => $t->unsignedBigInteger('pos_session_id')->nullable()->index(),
            'marketplace_id' => fn (Blueprint $t) => $t->unsignedBigInteger('marketplace_id')->nullable()->index(),
            'vendor_id' => fn (Blueprint $t) => $t->unsignedBigInteger('vendor_id')->nullable()->index(),
            'warehouse_id' => fn (Blueprint $t) => $t->unsignedBigInteger('warehouse_id')->nullable()->index(),
            'user_id' => fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->index(),
            'sale_reference' => fn (Blueprint $t) => $t->string('sale_reference')->nullable()->unique(),
            'subtotal' => fn (Blueprint $t) => $t->decimal('subtotal', 15, 4)->default(0),
            'tax_amount' => fn (Blueprint $t) => $t->decimal('tax_amount', 15, 4)->default(0),
            'discount_amount' => fn (Blueprint $t) => $t->decimal('discount_amount', 15, 4)->default(0),
            'total_amount' => fn (Blueprint $t) => $t->decimal('total_amount', 15, 4)->default(0),
            'currency_code' => fn (Blueprint $t) => $t->string('currency_code', 3)->default('USD'),
            'payment_status' => fn (Blueprint $t) => $t->string('payment_status')->default('pending')->index(),
            'status' => fn (Blueprint $t) => $t->string('status')->default('draft')->index(),
            'customer_name' => fn (Blueprint $t) => $t->string('customer_name')->nullable(),
            'customer_email' => fn (Blueprint $t) => $t->string('customer_email')->nullable()->index(),
            'customer_phone' => fn (Blueprint $t) => $t->string('customer_phone')->nullable(),
            'notes' => fn (Blueprint $t) => $t->text('notes')->nullable(),
            'metadata' => fn (Blueprint $t) => $t->json('metadata')->nullable(),
            'completed_at' => fn (Blueprint $t) => $t->timestamp('completed_at')->nullable()->index(),
        ]);

        $this->table('pos_sale_items', [
            'pos_sale_id' => fn (Blueprint $t) => $t->unsignedBigInteger('pos_sale_id')->nullable()->index(),
            'product_id' => fn (Blueprint $t) => $t->unsignedBigInteger('product_id')->nullable()->index(),
            'product_variant_id' => fn (Blueprint $t) => $t->unsignedBigInteger('product_variant_id')->nullable()->index(),
            'product_name' => fn (Blueprint $t) => $t->string('product_name')->nullable(),
            'product_sku' => fn (Blueprint $t) => $t->string('product_sku')->nullable()->index(),
            'quantity' => fn (Blueprint $t) => $t->decimal('quantity', 12, 3)->default(1),
            'unit_price' => fn (Blueprint $t) => $t->decimal('unit_price', 15, 4)->default(0),
            'tax_amount' => fn (Blueprint $t) => $t->decimal('tax_amount', 15, 4)->default(0),
            'discount_amount' => fn (Blueprint $t) => $t->decimal('discount_amount', 15, 4)->default(0),
            'total_amount' => fn (Blueprint $t) => $t->decimal('total_amount', 15, 4)->default(0),
            'metadata' => fn (Blueprint $t) => $t->json('metadata')->nullable(),
        ]);

        $this->table('pos_payments', [
            'pos_sale_id' => fn (Blueprint $t) => $t->unsignedBigInteger('pos_sale_id')->nullable()->index(),
            'amount' => fn (Blueprint $t) => $t->decimal('amount', 15, 4)->default(0),
            'currency_code' => fn (Blueprint $t) => $t->string('currency_code', 3)->default('USD'),
            'payment_method' => fn (Blueprint $t) => $t->string('payment_method')->default('cash')->index(),
            'payment_reference' => fn (Blueprint $t) => $t->string('payment_reference')->nullable()->index(),
            'status' => fn (Blueprint $t) => $t->string('status')->default('completed')->index(),
            'notes' => fn (Blueprint $t) => $t->text('notes')->nullable(),
            'processed_at' => fn (Blueprint $t) => $t->timestamp('processed_at')->nullable()->index(),
            'metadata' => fn (Blueprint $t) => $t->json('metadata')->nullable(),
        ]);
    }

    private function create(string $table, callable $callback): void
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, $callback);
        }
    }

    private function table(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column => $callback) {
            if (!Schema::hasColumn($table, $column)) {
                Schema::table($table, fn (Blueprint $t) => $callback($t));
            }
        }
    }
};
