<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('manufacturer_global_inventory')) {
            Schema::create('manufacturer_global_inventory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('manufacturer_id')->constrained('manufacturers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('sku')->nullable()->index();
                $table->decimal('quantity_on_hand', 15, 4)->default(0);
                $table->decimal('quantity_reserved', 15, 4)->default(0);
                $table->decimal('unit_cost', 15, 4)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['manufacturer_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('manufacturer_regional_allocations')) {
            Schema::create('manufacturer_regional_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('manufacturer_id')->constrained('manufacturers')->cascadeOnDelete();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->unsignedBigInteger('warehouse_id')->nullable()->index();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('quantity_allocated', 15, 4)->default(0);
                $table->string('status')->default('pending')->index();
                $table->text('notes')->nullable();
                $table->timestamp('allocated_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['manufacturer_id', 'marketplace_id', 'warehouse_id']);
            });
        }

        if (! Schema::hasTable('pos_customer_accounts')) {
            Schema::create('pos_customer_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->string('account_number')->unique();
                $table->string('name');
                $table->string('email')->nullable()->index();
                $table->string('phone')->nullable()->index();
                $table->decimal('store_credit_balance', 15, 4)->default(0);
                $table->string('status')->default('active')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('pos_sales') && ! Schema::hasColumn('pos_sales', 'receipt_qr_token')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                $table->string('receipt_qr_token', 64)->nullable()->unique()->after('sale_reference');
            });
        }

        if (Schema::hasTable('pos_sales') && ! Schema::hasColumn('pos_sales', 'pos_customer_account_id')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                $table->unsignedBigInteger('pos_customer_account_id')->nullable()->index()->after('customer_phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                if (Schema::hasColumn('pos_sales', 'receipt_qr_token')) {
                    $table->dropColumn('receipt_qr_token');
                }
                if (Schema::hasColumn('pos_sales', 'pos_customer_account_id')) {
                    $table->dropColumn('pos_customer_account_id');
                }
            });
        }

        Schema::dropIfExists('pos_customer_accounts');
        Schema::dropIfExists('manufacturer_regional_allocations');
        Schema::dropIfExists('manufacturer_global_inventory');
    }
};
