<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('b2b_accounts')) {
            Schema::create('b2b_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('type')->default('corporate')->index();
                $table->string('status')->default('pending')->index();
                $table->string('email')->nullable()->index();
                $table->string('phone')->nullable();
                $table->string('pan_vat_number')->nullable();
                $table->unsignedBigInteger('country_id')->nullable()->index();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->unsignedBigInteger('account_manager_id')->nullable()->index();
                $table->unsignedBigInteger('distributor_id')->nullable()->index();
                $table->decimal('credit_limit', 15, 4)->default(0);
                $table->json('billing_address')->nullable();
                $table->json('shipping_address')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('b2b_account_users')) {
            Schema::create('b2b_account_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_account_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('role')->default('buyer')->index();
                $table->json('permissions')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->index(['b2b_account_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('b2b_price_lists')) {
            Schema::create('b2b_price_lists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_account_id')->nullable()->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->string('name');
                $table->string('currency_code', 3)->default('USD');
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('b2b_price_list_items')) {
            Schema::create('b2b_price_list_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_price_list_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('sku')->nullable();
                $table->decimal('min_quantity', 15, 3)->default(1);
                $table->decimal('unit_price', 15, 4)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('b2b_quote_requests')) {
            Schema::create('b2b_quote_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_account_id')->nullable()->constrained()->nullOnDelete();
                $table->string('rfq_number')->unique();
                $table->string('status')->default('open')->index();
                $table->string('contact_name')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('currency_code', 3)->default('USD');
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('b2b_quote_items')) {
            Schema::create('b2b_quote_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_quote_request_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('sku')->nullable();
                $table->string('name');
                $table->decimal('quantity', 15, 3)->default(1);
                $table->decimal('target_price', 15, 4)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('b2b_quotations')) {
            Schema::create('b2b_quotations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_account_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('b2b_quote_request_id')->nullable()->constrained()->nullOnDelete();
                $table->string('quotation_number')->unique();
                $table->string('status')->default('draft')->index();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('subtotal', 15, 4)->default(0);
                $table->decimal('tax_total', 15, 4)->default(0);
                $table->decimal('shipping_total', 15, 4)->default(0);
                $table->decimal('grand_total', 15, 4)->default(0);
                $table->date('valid_until');
                $table->timestamp('accepted_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->json('price_snapshot')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('b2b_quotation_items')) {
            Schema::create('b2b_quotation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_quotation_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('sku')->nullable();
                $table->string('name');
                $table->decimal('quantity', 15, 3)->default(1);
                $table->decimal('unit_price', 15, 4)->default(0);
                $table->decimal('tax_amount', 15, 4)->default(0);
                $table->decimal('line_total', 15, 4)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('b2b_purchase_orders')) {
            Schema::create('b2b_purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_account_id')->constrained()->cascadeOnDelete();
                $table->foreignId('b2b_quotation_id')->nullable()->constrained()->nullOnDelete();
                $table->string('po_number')->unique();
                $table->string('status')->default('draft')->index();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('grand_total', 15, 4)->default(0);
                $table->json('price_snapshot')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('b2b_purchase_order_items')) {
            Schema::create('b2b_purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_purchase_order_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('sku')->nullable();
                $table->string('name');
                $table->decimal('quantity', 15, 3)->default(1);
                $table->decimal('unit_price', 15, 4)->default(0);
                $table->decimal('line_total', 15, 4)->default(0);
                $table->timestamps();
            });
        }

        foreach (['b2b_credit_terms', 'b2b_approval_workflows', 'b2b_approval_steps', 'b2b_account_activity_logs'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table) use ($tableName) {
                    $table->id();
                    $table->foreignId('b2b_account_id')->nullable()->constrained()->nullOnDelete();
                    $table->string('name')->nullable();
                    $table->string('status')->default('active')->index();
                    $table->json('metadata')->nullable();
                    $table->timestamps();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ([
            'b2b_account_activity_logs',
            'b2b_approval_steps',
            'b2b_approval_workflows',
            'b2b_credit_terms',
            'b2b_purchase_order_items',
            'b2b_purchase_orders',
            'b2b_quotation_items',
            'b2b_quotations',
            'b2b_quote_items',
            'b2b_quote_requests',
            'b2b_price_list_items',
            'b2b_price_lists',
            'b2b_account_users',
            'b2b_accounts',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
