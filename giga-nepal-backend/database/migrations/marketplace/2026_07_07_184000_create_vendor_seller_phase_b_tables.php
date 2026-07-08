<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'commerce_status')) {
                $table->string('commerce_status')->default('pending_verification')->after('status')->index();
            }
            if (! Schema::hasColumn('vendors', 'public_profile_enabled')) {
                $table->boolean('public_profile_enabled')->default(false)->after('is_verified')->index();
            }
            if (! Schema::hasColumn('vendors', 'seller_onboarding_completed_at')) {
                $table->timestamp('seller_onboarding_completed_at')->nullable()->after('verified_at');
            }
        });

        if (! Schema::hasTable('vendor_roles')) {
            Schema::create('vendor_roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('display_name');
                $table->json('permissions')->nullable();
                $table->boolean('is_system')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vendor_permissions')) {
            Schema::create('vendor_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('display_name');
                $table->string('group')->index();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vendor_branches')) {
            Schema::create('vendor_branches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('type')->default('branch')->index();
                $table->unsignedBigInteger('country_id')->nullable()->index();
                $table->unsignedBigInteger('region_id')->nullable()->index();
                $table->unsignedBigInteger('city_id')->nullable()->index();
                $table->text('address')->nullable();
                $table->string('contact_name')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['vendor_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('vendor_products')) {
            Schema::create('vendor_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('sku')->nullable()->index();
                $table->string('vendor_sku')->nullable()->index();
                $table->text('description')->nullable();
                $table->string('status')->default('draft')->index();
                $table->unsignedBigInteger('submitted_by')->nullable()->index();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
                $table->index(['vendor_id', 'status']);
            });
        }

        if (! Schema::hasTable('vendor_orders')) {
            Schema::create('vendor_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('vendor_order_number')->unique();
                $table->string('status')->default('pending')->index();
                $table->string('payment_status')->default('pending')->index();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('subtotal', 15, 4)->default(0);
                $table->decimal('tax_total', 15, 4)->default(0);
                $table->decimal('shipping_total', 15, 4)->default(0);
                $table->decimal('commission_total', 15, 4)->default(0);
                $table->decimal('vendor_net_total', 15, 4)->default(0);
                $table->timestamp('fulfilled_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['vendor_id', 'status']);
            });
        }

        if (! Schema::hasTable('vendor_order_items')) {
            Schema::create('vendor_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 15, 4)->default(0);
                $table->decimal('line_total', 15, 4)->default(0);
                $table->decimal('commission_amount', 15, 4)->default(0);
                $table->decimal('vendor_net_amount', 15, 4)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vendor_payouts')) {
            Schema::create('vendor_payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
                $table->string('payout_number')->unique();
                $table->string('status')->default('pending')->index();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('gross_amount', 15, 4)->default(0);
                $table->decimal('commission_amount', 15, 4)->default(0);
                $table->decimal('fee_amount', 15, 4)->default(0);
                $table->decimal('net_amount', 15, 4)->default(0);
                $table->foreignId('payout_method_id')->nullable()->constrained('vendor_payout_methods')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->unsignedBigInteger('marked_paid_by')->nullable()->index();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['vendor_id', 'status']);
            });
        }

        if (! Schema::hasTable('vendor_payout_items')) {
            Schema::create('vendor_payout_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_payout_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vendor_order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('description');
                $table->decimal('gross_amount', 15, 4)->default(0);
                $table->decimal('commission_amount', 15, 4)->default(0);
                $table->decimal('net_amount', 15, 4)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vendor_commission_rules')) {
            Schema::create('vendor_commission_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('marketplace_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
                $table->string('name');
                $table->string('type')->default('percentage')->index();
                $table->decimal('value', 10, 4)->default(0);
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vendor_reviews')) {
            Schema::create('vendor_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('rating_id')->nullable()->constrained('vendor_ratings')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('rating')->default(5);
                $table->text('title')->nullable();
                $table->text('body')->nullable();
                $table->string('status')->default('published')->index();
                $table->boolean('is_verified_purchase')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['vendor_id', 'status']);
            });
        }

        if (! Schema::hasTable('vendor_support_tickets')) {
            Schema::create('vendor_support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('ticket_number')->unique();
                $table->string('subject');
                $table->string('category')->default('general')->index();
                $table->string('priority')->default('normal')->index();
                $table->string('status')->default('open')->index();
                $table->text('message');
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['vendor_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'vendor_support_tickets',
            'vendor_reviews',
            'vendor_commission_rules',
            'vendor_payout_items',
            'vendor_payouts',
            'vendor_order_items',
            'vendor_orders',
            'vendor_products',
            'vendor_branches',
            'vendor_permissions',
            'vendor_roles',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('vendors', function (Blueprint $table) {
            foreach (['seller_onboarding_completed_at', 'public_profile_enabled', 'commerce_status'] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
