<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('distributors')) {
            Schema::create('distributors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('distributors')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('type')->default('reseller')->index();
                $table->string('status')->default('pending')->index();
                $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['parent_id', 'status']);
            });
        }

        if (! Schema::hasTable('distributor_profiles')) {
            Schema::create('distributor_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->string('business_name')->nullable();
                $table->string('tax_number')->nullable();
                $table->string('registration_number')->nullable();
                $table->text('address')->nullable();
                $table->json('documents')->nullable();
                $table->json('capabilities')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('distributor_territories')) {
            Schema::create('distributor_territories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('region_id')->nullable()->index();
                $table->unsignedBigInteger('city_id')->nullable()->index();
                $table->string('territory_name');
                $table->boolean('exclusive')->default(false);
                $table->boolean('can_manage_downlines')->default(false);
                $table->timestamps();
                $table->index(['distributor_id', 'country_id']);
            });
        }

        if (! Schema::hasTable('distributor_staff')) {
            Schema::create('distributor_staff', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('role')->default('staff');
                $table->json('permissions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('distributor_downlines')) {
            Schema::create('distributor_downlines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_distributor_id')->constrained('distributors')->cascadeOnDelete();
                $table->foreignId('child_distributor_id')->constrained('distributors')->cascadeOnDelete();
                $table->string('relationship_type')->default('downline');
                $table->timestamps();
                $table->unique(['parent_distributor_id', 'child_distributor_id'], 'distributor_downline_unique');
            });
        }

        if (! Schema::hasTable('distributor_leads')) {
            Schema::create('distributor_leads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('company')->nullable();
                $table->string('status')->default('new')->index();
                $table->decimal('estimated_value', 15, 4)->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('distributor_customers')) {
            Schema::create('distributor_customers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('type')->default('retail')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('distributor_orders')) {
            Schema::create('distributor_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('order_reference')->unique();
                $table->string('status')->default('pending')->index();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('gross_amount', 15, 4)->default(0);
                $table->decimal('commission_amount', 15, 4)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('distributor_commission_rules')) {
            Schema::create('distributor_commission_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('type')->default('percentage');
                $table->decimal('value', 10, 4)->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('distributor_commissions')) {
            Schema::create('distributor_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('distributor_order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status')->default('pending')->index();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('base_amount', 15, 4)->default(0);
                $table->decimal('commission_amount', 15, 4)->default(0);
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('distributor_payouts')) {
            Schema::create('distributor_payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
                $table->string('payout_number')->unique();
                $table->string('status')->default('pending')->index();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('gross_amount', 15, 4)->default(0);
                $table->decimal('net_amount', 15, 4)->default(0);
                $table->timestamp('paid_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('distributor_activity_logs')) {
            Schema::create('distributor_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action')->index();
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'distributor_activity_logs',
            'distributor_payouts',
            'distributor_commissions',
            'distributor_commission_rules',
            'distributor_orders',
            'distributor_customers',
            'distributor_leads',
            'distributor_downlines',
            'distributor_staff',
            'distributor_territories',
            'distributor_profiles',
            'distributors',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
