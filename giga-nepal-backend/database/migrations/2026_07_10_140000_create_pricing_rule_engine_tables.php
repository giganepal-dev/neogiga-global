<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurable multi-country pricing-rule engine — schema only. Data-driven,
 * versioned, marketplace-scoped, auditable. No rules are seeded and nothing is
 * wired into storefront/cart, so this is entirely inert until an operator
 * creates and approves rules. See PRICING_RULE_ENGINE_AUDIT.md.
 *
 * Scope target ids are nullable, indexed unsignedBigInteger rather than hard
 * FKs: the referenced tables (products, categories, brands, vendors,
 * warehouses, ...) vary across environments and this migration must stay
 * additive and non-breaking. marketplace_id and rule_set_id are constrained
 * because those tables are always present.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pricing_rule_sets')) {
            Schema::create('pricing_rule_sets', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 100)->unique();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->string('owner_type', 40)->default('global_admin');
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->boolean('active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->index(['marketplace_id', 'active']);
            });
        }

        if (! Schema::hasTable('pricing_rules')) {
            Schema::create('pricing_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rule_set_id')->nullable()->constrained('pricing_rule_sets')->nullOnDelete();
                $table->string('name');
                $table->string('code', 120)->unique();

                // Ownership
                $table->string('owner_type', 40)->default('global_admin'); // global_admin|marketplace_admin|seller|reseller|manufacturer
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();

                // Scope (most-specific target wins in precedence)
                $table->string('scope_type', 40)->default('global'); // global|marketplace|country|region|state|city|postal_group|warehouse|seller|reseller|manufacturer|brand|category|subcategory|generic_product|product|customer_segment|b2b_account|quantity_tier
                $table->unsignedBigInteger('scope_product_id')->nullable();
                $table->unsignedBigInteger('scope_category_id')->nullable();
                $table->unsignedBigInteger('scope_brand_id')->nullable();
                $table->unsignedBigInteger('scope_manufacturer_id')->nullable();
                $table->unsignedBigInteger('scope_seller_id')->nullable();
                $table->unsignedBigInteger('scope_warehouse_id')->nullable();
                $table->unsignedBigInteger('scope_country_id')->nullable();
                $table->unsignedBigInteger('scope_region_id')->nullable();
                $table->string('scope_city', 120)->nullable();
                $table->string('scope_postal_group', 120)->nullable();
                $table->string('customer_segment', 60)->nullable();
                $table->unsignedInteger('min_quantity')->nullable();
                $table->unsignedInteger('max_quantity')->nullable();

                // Cost basis + action
                $table->string('cost_basis', 40)->default('landed_unit'); // supplier_purchase|landed_unit|moving_average|fifo|standard|global_usd|reseller_submitted|manual_approved
                $table->string('action_type', 40); // percentage_markup|fixed_markup|fixed_selling_price|minimum_price|maximum_price|margin_target|price_floor|price_ceiling|currency_adjustment|exchange_rate_buffer|freight_markup|payment_fee_markup|rounding
                $table->decimal('action_value', 18, 6)->nullable();
                $table->string('action_currency', 3)->nullable();

                // Evaluation controls
                $table->integer('priority')->default(0); // higher wins within a scope
                $table->string('condition_operator', 4)->default('and'); // and|or
                $table->boolean('stackable')->default(false);
                $table->boolean('stop_processing')->default(false);

                // Schedule (UTC stored; timezone for display/evaluation)
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('timezone', 64)->nullable();

                // Lifecycle / audit
                $table->boolean('active')->default(true);
                $table->string('approval_status', 20)->default('draft'); // draft|pending|approved|rejected|suspended
                $table->unsignedInteger('version')->default(1);
                $table->string('reason')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamps();

                $table->index(['marketplace_id', 'scope_type', 'active', 'approval_status'], 'pricing_rules_resolve_idx');
                $table->index(['scope_product_id']);
                $table->index(['scope_category_id']);
                $table->index(['scope_seller_id']);
                $table->index(['priority']);
            });
        }

        if (! Schema::hasTable('pricing_rule_conditions')) {
            Schema::create('pricing_rule_conditions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pricing_rule_id')->constrained('pricing_rules')->cascadeOnDelete();
                $table->string('field', 80);       // e.g. quantity, customer_segment, payment_method, channel
                $table->string('operator', 20);    // eq|neq|gt|gte|lt|lte|in|not_in|between
                $table->text('value')->nullable(); // scalar or JSON list
                $table->timestamps();
                $table->index(['pricing_rule_id']);
            });
        }

        if (! Schema::hasTable('pricing_rule_actions')) {
            Schema::create('pricing_rule_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pricing_rule_id')->constrained('pricing_rules')->cascadeOnDelete();
                $table->unsignedInteger('sequence')->default(0);
                $table->string('action_type', 40);
                $table->decimal('action_value', 18, 6)->nullable();
                $table->string('action_currency', 3)->nullable();
                $table->timestamps();
                $table->index(['pricing_rule_id', 'sequence']);
            });
        }

        if (! Schema::hasTable('pricing_rule_scopes')) {
            Schema::create('pricing_rule_scopes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pricing_rule_id')->constrained('pricing_rules')->cascadeOnDelete();
                $table->string('scope_type', 40);
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->string('scope_value', 120)->nullable();
                $table->timestamps();
                $table->index(['pricing_rule_id', 'scope_type']);
            });
        }

        if (! Schema::hasTable('pricing_rule_versions')) {
            Schema::create('pricing_rule_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pricing_rule_id')->constrained('pricing_rules')->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->json('snapshot');           // full rule state at this version
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->string('reason')->nullable();
                $table->timestamps();
                $table->unique(['pricing_rule_id', 'version']);
            });
        }

        if (! Schema::hasTable('pricing_rule_approvals')) {
            Schema::create('pricing_rule_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pricing_rule_id')->constrained('pricing_rules')->cascadeOnDelete();
                $table->unsignedInteger('version')->default(1);
                $table->string('status', 20)->default('pending'); // pending|approved|rejected
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->unsignedBigInteger('decided_by')->nullable();
                $table->text('note')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();
                $table->index(['pricing_rule_id', 'status']);
            });
        }

        if (! Schema::hasTable('price_rounding_rules')) {
            Schema::create('price_rounding_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->string('currency_code', 3)->nullable();
                $table->string('strategy', 20)->default('nearest'); // nearest|up|down|charm
                $table->decimal('increment', 12, 4)->default(0.01); // e.g. 1.00, 0.50, 0.05
                $table->decimal('charm_ending', 12, 4)->nullable();  // e.g. 0.99
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['marketplace_id', 'currency_code']);
            });
        }

        if (! Schema::hasTable('price_floor_rules')) {
            Schema::create('price_floor_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->string('scope_type', 40)->default('global'); // global|marketplace|category|brand|seller|product
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->decimal('min_absolute_price', 18, 6)->nullable();
                $table->string('currency_code', 3)->nullable();
                $table->decimal('max_discount_percent', 5, 2)->nullable();
                $table->decimal('max_fixed_discount', 18, 6)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['marketplace_id', 'scope_type']);
            });
        }

        if (! Schema::hasTable('margin_floor_rules')) {
            Schema::create('margin_floor_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->string('scope_type', 40)->default('global');
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->decimal('min_gross_margin_percent', 5, 2)->nullable();
                $table->decimal('min_net_margin_percent', 5, 2)->nullable();
                $table->decimal('min_contribution_margin_percent', 5, 2)->nullable();
                $table->boolean('require_approval_below')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['marketplace_id', 'scope_type']);
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'margin_floor_rules',
            'price_floor_rules',
            'price_rounding_rules',
            'pricing_rule_approvals',
            'pricing_rule_versions',
            'pricing_rule_scopes',
            'pricing_rule_actions',
            'pricing_rule_conditions',
            'pricing_rules',
            'pricing_rule_sets',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
