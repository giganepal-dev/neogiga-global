<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global commerce Stage 1: adds the fields needed to support 25 path-prefixed
 * marketplace storefronts alongside the existing 3 domain-based ones.
 * Additive only — no existing marketplace row's meaning changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplaces', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplaces', 'url_prefix')) {
                $table->string('url_prefix', 8)->nullable()->unique()->after('code');
            }
            if (! Schema::hasColumn('marketplaces', 'regional_brand_name')) {
                $table->string('regional_brand_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('marketplaces', 'default_language')) {
                $table->string('default_language', 8)->nullable()->after('locale');
            }
            if (! Schema::hasColumn('marketplaces', 'launch_status')) {
                // preview: not publicly linked yet. active: live. retired: kept for redirects only.
                $table->string('launch_status', 20)->default('preview')->after('is_active');
            }
            if (! Schema::hasColumn('marketplaces', 'global_fallback')) {
                $table->boolean('global_fallback')->default(false)->after('launch_status');
            }
            if (! Schema::hasColumn('marketplaces', 'checkout_enabled')) {
                $table->boolean('checkout_enabled')->default(false)->after('global_fallback');
            }
            if (! Schema::hasColumn('marketplaces', 'redirect_enabled')) {
                // Master kill switch: even if marketplace_redirect_rules has rows, no redirect
                // fires unless this is explicitly true. Defaults false everywhere.
                $table->boolean('redirect_enabled')->default(false)->after('checkout_enabled');
            }
            if (! Schema::hasColumn('marketplaces', 'local_seller_support')) {
                $table->boolean('local_seller_support')->default(false)->after('redirect_enabled');
            }
            if (! Schema::hasColumn('marketplaces', 'local_warehouse_support')) {
                $table->boolean('local_warehouse_support')->default(false)->after('local_seller_support');
            }
            if (! Schema::hasColumn('marketplaces', 'local_payment_support')) {
                $table->boolean('local_payment_support')->default(false)->after('local_warehouse_support');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplaces', function (Blueprint $table) {
            foreach ([
                'url_prefix', 'regional_brand_name', 'default_language', 'launch_status',
                'global_fallback', 'checkout_enabled', 'redirect_enabled',
                'local_seller_support', 'local_warehouse_support', 'local_payment_support',
            ] as $column) {
                if (Schema::hasColumn('marketplaces', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
