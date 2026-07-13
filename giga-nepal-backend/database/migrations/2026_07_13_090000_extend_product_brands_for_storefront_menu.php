<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_brands', function (Blueprint $table) {
            if (! Schema::hasColumn('product_brands', 'short_description')) {
                $table->string('short_description', 500)->nullable()->after('description');
            }
            if (! Schema::hasColumn('product_brands', 'banner_path')) {
                $table->string('banner_path')->nullable()->after('logo_path');
            }
            if (! Schema::hasColumn('product_brands', 'is_menu_visible')) {
                $table->boolean('is_menu_visible')->default(false)->after('is_featured');
            }
            if (! Schema::hasColumn('product_brands', 'display_desktop')) {
                $table->boolean('display_desktop')->default(true)->after('is_menu_visible');
            }
            if (! Schema::hasColumn('product_brands', 'display_mobile')) {
                $table->boolean('display_mobile')->default(true)->after('display_desktop');
            }
            if (! Schema::hasColumn('product_brands', 'hide_when_unavailable')) {
                $table->boolean('hide_when_unavailable')->default(false)->after('display_mobile');
            }
            if (! Schema::hasColumn('product_brands', 'landing_page_enabled')) {
                $table->boolean('landing_page_enabled')->default(true)->after('hide_when_unavailable');
            }
            if (! Schema::hasColumn('product_brands', 'menu_placement')) {
                $table->string('menu_placement', 40)->default('primary')->after('sort_order');
            }
            if (! Schema::hasColumn('product_brands', 'publication_starts_at')) {
                $table->timestamp('publication_starts_at')->nullable()->after('menu_placement');
            }
            if (! Schema::hasColumn('product_brands', 'publication_ends_at')) {
                $table->timestamp('publication_ends_at')->nullable()->after('publication_starts_at');
            }
            if (! Schema::hasColumn('product_brands', 'country_visibility')) {
                $table->json('country_visibility')->nullable()->after('marketplace_visibility');
            }
            if (! Schema::hasColumn('product_brands', 'category_visibility')) {
                $table->json('category_visibility')->nullable()->after('country_visibility');
            }
            if (! Schema::hasColumn('product_brands', 'seo_title')) {
                $table->string('seo_title')->nullable()->after('category_visibility');
            }
            if (! Schema::hasColumn('product_brands', 'seo_description')) {
                $table->string('seo_description', 500)->nullable()->after('seo_title');
            }
            if (! Schema::hasColumn('product_brands', 'seo_keywords')) {
                $table->string('seo_keywords', 1000)->nullable()->after('seo_description');
            }
            if (! Schema::hasColumn('product_brands', 'canonical_url')) {
                $table->string('canonical_url', 1000)->nullable()->after('seo_keywords');
            }
        });

        Schema::table('product_brands', function (Blueprint $table) {
            $table->index(['is_active', 'is_menu_visible', 'sort_order'], 'product_brands_menu_visibility_index');
        });
    }

    public function down(): void
    {
        Schema::table('product_brands', function (Blueprint $table) {
            $table->dropIndex('product_brands_menu_visibility_index');
            foreach ([
                'short_description', 'banner_path', 'is_menu_visible', 'display_desktop', 'display_mobile',
                'hide_when_unavailable', 'landing_page_enabled', 'menu_placement', 'publication_starts_at',
                'publication_ends_at', 'country_visibility', 'category_visibility', 'seo_title',
                'seo_description', 'seo_keywords', 'canonical_url',
            ] as $column) {
                if (Schema::hasColumn('product_brands', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
