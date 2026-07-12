<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('catalog_sources')) {
            Schema::table('catalog_sources', function (Blueprint $table): void {
                foreach ([
                    'source_type' => fn () => $table->string('source_type', 40)->default('supplier')->index(),
                    'base_url' => fn () => $table->text('base_url')->nullable(),
                    'country_code' => fn () => $table->string('country_code', 2)->nullable(),
                    'contact_url' => fn () => $table->text('contact_url')->nullable(),
                    'terms_url' => fn () => $table->text('terms_url')->nullable(),
                    'robots_url' => fn () => $table->text('robots_url')->nullable(),
                    'catalogue_policy' => fn () => $table->json('catalogue_policy')->nullable(),
                    'user_agent' => fn () => $table->string('user_agent')->nullable(),
                    'crawl_delay_ms' => fn () => $table->unsignedInteger('crawl_delay_ms')->default(6000),
                    'maximum_requests_per_minute' => fn () => $table->unsignedSmallInteger('maximum_requests_per_minute')->default(10),
                    'import_enabled' => fn () => $table->boolean('import_enabled')->default(false)->index(),
                    'media_download_enabled' => fn () => $table->boolean('media_download_enabled')->default(false),
                    'description_reuse_status' => fn () => $table->string('description_reuse_status', 40)->default('unknown'),
                    'last_successful_sync_at' => fn () => $table->timestamp('last_successful_sync_at')->nullable(),
                    'last_failed_sync_at' => fn () => $table->timestamp('last_failed_sync_at')->nullable(),
                    'status' => fn () => $table->string('status', 40)->default('pending_policy_review')->index(),
                ] as $column => $add) {
                    if (! Schema::hasColumn('catalog_sources', $column)) {
                        $add();
                    }
                }
            });
        }

        $create = function (string $name, Closure $callback): void {
            if (! Schema::hasTable($name)) {
                Schema::create($name, $callback);
            }
        };

        $create('supplier_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('name');
            $table->string('source_kind', 40);
            $table->text('source_url');
            $table->unsignedSmallInteger('priority')->default(100);
            $table->string('parser_class')->nullable();
            $table->boolean('enabled')->default(false);
            $table->json('configuration_json')->nullable();
            $table->timestamps();
            $table->unique(['catalog_source_id', 'source_url']);
        });

        $create('supplier_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('source_product_id')->nullable();
            $table->string('supplier_sku')->nullable()->index();
            $table->string('manufacturer_part_number')->nullable()->index();
            $table->string('source_name')->nullable();
            $table->string('source_slug')->nullable();
            $table->text('source_url')->nullable();
            $table->text('canonical_url')->nullable();
            $table->json('source_category_path_json')->nullable();
            $table->string('source_brand')->nullable();
            $table->string('source_manufacturer')->nullable();
            $table->string('source_status', 60)->nullable();
            $table->string('source_currency', 3)->nullable();
            $table->decimal('source_price', 18, 6)->nullable();
            $table->decimal('source_compare_price', 18, 6)->nullable();
            $table->string('source_stock_state', 60)->nullable();
            $table->unsignedInteger('source_moq')->nullable();
            $table->string('source_lead_time')->nullable();
            $table->json('raw_payload_json')->nullable();
            $table->string('content_hash', 64)->index();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_changed_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->string('review_status', 40)->default('pending_review')->index();
            $table->timestamps();
            $table->unique(['catalog_source_id', 'source_product_id']);
            $table->index(['catalog_source_id', 'manufacturer_part_number']);
            $table->index(['product_id', 'review_status']);
        });

        $create('supplier_category_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('source_category_key');
            $table->string('source_category_name');
            $table->text('source_category_path')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->decimal('confidence', 5, 4)->default(0);
            $table->string('mapping_status', 40)->default('pending_review')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['catalog_source_id', 'source_category_key']);
        });

        $create('specification_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('group_name')->nullable();
            $table->string('value_type', 30)->default('text');
            $table->string('canonical_unit', 30)->nullable();
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_comparable')->default(false);
            $table->timestamps();
        });

        $create('specification_aliases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('specification_definition_id')->constrained('specification_definitions')->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
            $table->string('alias');
            $table->timestamps();
            $table->unique(['specification_definition_id', 'catalog_source_id', 'alias'], 'spec_alias_source_unique');
        });

        $create('product_specification_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('specification_definition_id')->constrained('specification_definitions')->cascadeOnDelete();
            $table->foreignId('catalog_source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
            $table->string('original_label')->nullable();
            $table->text('original_value')->nullable();
            $table->text('normalized_value')->nullable();
            $table->decimal('numeric_value', 20, 8)->nullable();
            $table->decimal('numeric_max_value', 20, 8)->nullable();
            $table->string('normalized_unit', 30)->nullable();
            $table->decimal('parsing_confidence', 5, 4)->default(0);
            $table->text('source_url')->nullable();
            $table->timestamp('retrieved_at')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'specification_definition_id', 'catalog_source_id'], 'product_spec_source_unique');
        });

        $create('compatibility_platforms', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        $create('supplier_product_compatibilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_product_id')->constrained('supplier_products')->cascadeOnDelete();
            $table->foreignId('compatibility_platform_id')->constrained('compatibility_platforms')->cascadeOnDelete();
            $table->string('evidence_type', 30)->default('source');
            $table->text('evidence_url')->nullable();
            $table->decimal('confidence', 5, 4)->default(0);
            $table->string('review_status', 40)->default('pending_review');
            $table->timestamps();
            $table->unique(['supplier_product_id', 'compatibility_platform_id']);
        });

        $create('supplier_product_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_product_id')->constrained('supplier_products')->cascadeOnDelete();
            $table->string('asset_type', 40);
            $table->text('original_url')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('local_path')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->string('checksum', 64)->nullable()->index();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('rights_status', 40)->default('unknown');
            $table->string('download_status', 40)->default('not_requested');
            $table->timestamp('retrieved_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('alt_text')->nullable();
            $table->timestamps();
            $table->index(['supplier_product_id', 'asset_type']);
        });

        $create('catalog_import_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('mode', 30);
            $table->string('status', 40)->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('pages_discovered')->default(0);
            $table->unsignedBigInteger('pages_fetched')->default(0);
            $table->unsignedBigInteger('pages_skipped')->default(0);
            $table->unsignedBigInteger('products_discovered')->default(0);
            $table->unsignedBigInteger('products_created')->default(0);
            $table->unsignedBigInteger('products_updated')->default(0);
            $table->unsignedBigInteger('products_unchanged')->default(0);
            $table->unsignedBigInteger('products_rejected')->default(0);
            $table->unsignedBigInteger('products_queued_for_review')->default(0);
            $table->unsignedBigInteger('images_downloaded')->default(0);
            $table->unsignedBigInteger('documents_downloaded')->default(0);
            $table->json('warnings')->nullable();
            $table->json('failures')->nullable();
            $table->json('command_options')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestamps();
            $table->index(['catalog_source_id', 'started_at']);
        });

        $create('catalog_import_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('catalog_import_run_id');
            $table->string('idempotency_key', 64);
            $table->text('source_url')->nullable();
            $table->string('source_product_id')->nullable();
            $table->string('status', 40)->index();
            $table->foreignId('supplier_product_id')->nullable()->constrained('supplier_products')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('result_json')->nullable();
            $table->timestamps();
            $table->unique(['catalog_import_run_id', 'idempotency_key'], 'catalog_import_item_idem_unique');
            $table->foreign('catalog_import_run_id')->references('id')->on('catalog_import_runs')->cascadeOnDelete();
        });

        $create('catalog_import_checkpoints', function (Blueprint $table): void {
            $table->id();
            $table->uuid('catalog_import_run_id');
            $table->string('checkpoint_key');
            $table->json('checkpoint_json')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['catalog_import_run_id', 'checkpoint_key']);
            $table->foreign('catalog_import_run_id')->references('id')->on('catalog_import_runs')->cascadeOnDelete();
        });

        $create('catalog_source_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->uuid('catalog_import_run_id')->nullable();
            $table->text('source_url');
            $table->string('content_hash', 64)->nullable()->index();
            $table->string('etag')->nullable();
            $table->timestamp('last_modified_at')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->timestamp('retrieved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('catalog_import_run_id')->references('id')->on('catalog_import_runs')->nullOnDelete();
        });

        $create('catalog_change_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->foreignId('supplier_product_id')->nullable()->constrained('supplier_products')->nullOnDelete();
            $table->uuid('catalog_import_run_id')->nullable();
            $table->string('event_type', 40);
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->timestamps();
            $table->foreign('catalog_import_run_id')->references('id')->on('catalog_import_runs')->nullOnDelete();
        });

        $create('catalog_review_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
            $table->foreignId('supplier_product_id')->nullable()->constrained('supplier_products')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('task_type', 50)->index();
            $table->string('status', 40)->default('open')->index();
            $table->decimal('confidence', 5, 4)->default(0);
            $table->json('evidence_json')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        $create('country_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('publication_status', 40)->default('pending_review')->index();
            $table->decimal('regular_price', 18, 4)->nullable();
            $table->decimal('sale_price', 18, 4)->nullable();
            $table->string('tax_class')->nullable();
            $table->string('customs_code')->nullable();
            $table->text('warranty')->nullable();
            $table->unsignedInteger('minimum_order_quantity')->nullable();
            $table->string('lead_time')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'country_id']);
        });
    }

    public function down(): void
    {
        foreach ([
            'country_products', 'catalog_review_tasks', 'catalog_change_events', 'catalog_source_snapshots',
            'catalog_import_checkpoints', 'catalog_import_items', 'catalog_import_runs', 'supplier_product_assets',
            'supplier_product_compatibilities', 'compatibility_platforms', 'product_specification_values',
            'specification_aliases', 'specification_definitions', 'supplier_category_mappings', 'supplier_products', 'supplier_sources',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('catalog_sources')) {
            Schema::table('catalog_sources', function (Blueprint $table): void {
                foreach (['status', 'last_failed_sync_at', 'last_successful_sync_at', 'description_reuse_status', 'media_download_enabled', 'import_enabled', 'maximum_requests_per_minute', 'crawl_delay_ms', 'user_agent', 'catalogue_policy', 'robots_url', 'terms_url', 'contact_url', 'country_code', 'base_url', 'source_type'] as $column) {
                    if (Schema::hasColumn('catalog_sources', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
