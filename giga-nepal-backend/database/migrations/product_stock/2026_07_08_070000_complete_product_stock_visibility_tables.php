<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                foreach ([
                    'approval_status' => fn () => $table->string('approval_status', 40)->default('draft')->index(),
                    'visibility_status' => fn () => $table->string('visibility_status', 40)->default('public')->index(),
                    'global_sku' => fn () => $table->string('global_sku')->nullable()->index(),
                    'model_number' => fn () => $table->string('model_number')->nullable()->index(),
                    'gtin' => fn () => $table->string('gtin')->nullable()->index(),
                    'country_of_origin' => fn () => $table->string('country_of_origin')->nullable()->index(),
                    'manufacturer_name' => fn () => $table->string('manufacturer_name')->nullable(),
                    'importer_name' => fn () => $table->string('importer_name')->nullable(),
                    'warranty_type' => fn () => $table->string('warranty_type')->nullable(),
                    'warranty_period' => fn () => $table->string('warranty_period')->nullable(),
                    'warranty_terms' => fn () => $table->text('warranty_terms')->nullable(),
                    'return_policy' => fn () => $table->text('return_policy')->nullable(),
                    'safety_certification' => fn () => $table->string('safety_certification')->nullable(),
                    'compliance_certification' => fn () => $table->string('compliance_certification')->nullable(),
                    'package_includes' => fn () => $table->json('package_includes')->nullable(),
                    'use_cases' => fn () => $table->json('use_cases')->nullable(),
                    'tags' => fn () => $table->json('tags')->nullable(),
                    'search_keywords' => fn () => $table->text('search_keywords')->nullable(),
                    'meta_title' => fn () => $table->string('meta_title')->nullable(),
                    'meta_description' => fn () => $table->text('meta_description')->nullable(),
                    'is_generic' => fn () => $table->boolean('is_generic')->default(false)->index(),
                    'generic_group_id' => fn () => $table->unsignedBigInteger('generic_group_id')->nullable()->index(),
                ] as $column => $definition) {
                    if (! Schema::hasColumn('products', $column)) {
                        $definition();
                    }
                }
            });
        }

        if (Schema::hasTable('inventory_stocks')) {
            Schema::table('inventory_stocks', function (Blueprint $table) {
                foreach ([
                    'country_id' => fn () => $table->unsignedBigInteger('country_id')->nullable()->index(),
                    'region_id' => fn () => $table->unsignedBigInteger('region_id')->nullable()->index(),
                    'city_id' => fn () => $table->unsignedBigInteger('city_id')->nullable()->index(),
                    'backorder_allowed' => fn () => $table->boolean('backorder_allowed')->default(false)->index(),
                    'quote_only' => fn () => $table->boolean('quote_only')->default(false)->index(),
                    'status' => fn () => $table->string('status', 40)->default('active')->index(),
                ] as $column => $definition) {
                    if (! Schema::hasColumn('inventory_stocks', $column)) {
                        $definition();
                    }
                }
            });
        }

        if (! Schema::hasTable('product_warranties')) {
            Schema::create('product_warranties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('warranty_type')->nullable();
                $table->string('warranty_period')->nullable();
                $table->text('terms')->nullable();
                $table->text('claim_requirements')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
            });
        }

        foreach ([
            'product_datasheets' => 'datasheet',
            'product_certificates' => 'certificate',
            'product_manuals' => 'manual',
        ] as $tableName => $type) {
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table) use ($type) {
                    $table->id();
                    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                    $table->string('title');
                    $table->string('document_type')->default($type)->index();
                    $table->string('file_path')->nullable();
                    $table->string('source_url')->nullable();
                    $table->string('mime_type')->nullable();
                    $table->unsignedBigInteger('file_size')->nullable();
                    $table->string('status', 40)->default('pending_review')->index();
                    $table->unsignedBigInteger('uploaded_by')->nullable()->index();
                    $table->json('metadata')->nullable();
                    $table->timestamps();
                });
            }
        }

        if (! Schema::hasTable('product_generic_groups')) {
            Schema::create('product_generic_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_generic_suggestions')) {
            Schema::create('product_generic_suggestions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('source_product_id')->nullable()->index();
                $table->unsignedBigInteger('suggested_product_id')->nullable()->index();
                $table->string('suggested_name')->nullable();
                $table->string('suggestion_type', 40)->default('compatible')->index();
                $table->integer('priority')->default(100)->index();
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('marketplace_inventory_visibility')) {
            Schema::create('marketplace_inventory_visibility', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('marketplace_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('product_variant_id')->nullable()->index();
                $table->unsignedBigInteger('vendor_id')->nullable()->index();
                $table->unsignedBigInteger('warehouse_id')->nullable()->index();
                $table->boolean('is_visible')->default(true)->index();
                $table->boolean('quote_only')->default(false)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('low_stock_alerts')) {
            Schema::create('low_stock_alerts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('inventory_stock_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('vendor_id')->nullable()->index();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->decimal('available_quantity', 15, 3)->default(0);
                $table->decimal('threshold', 15, 3)->default(0);
                $table->string('status', 40)->default('open')->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'low_stock_alerts',
            'marketplace_inventory_visibility',
            'product_generic_suggestions',
            'product_generic_groups',
            'product_manuals',
            'product_certificates',
            'product_datasheets',
            'product_warranties',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
