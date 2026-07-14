<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATE_KEY = '2026_07_14_100000_create_elecforest_catalog_import_layer';

    public function up(): void
    {
        if (! DB::connection()->pretending() && (! Schema::hasTable('catalog_sources') || ! Schema::hasTable('products'))) {
            throw new RuntimeException('ElecForest import requires the existing catalog_sources and products tables.');
        }

        if (! Schema::hasTable('elecforest_schema_states')) {
            Schema::create('elecforest_schema_states', function (Blueprint $table): void {
                $table->string('migration_key')->primary();
                $table->json('created_resources');
                $table->timestamps();
            });
        }

        $created = ['tables' => [], 'columns' => []];
        $rememberTable = function (string $table) use (&$created): void {
            $created['tables'][] = $table;
        };
        $rememberColumn = function (string $table, string $column) use (&$created): void {
            $created['columns'][] = [$table, $column];
        };

        if (! Schema::hasTable('supplier_products')) {
            Schema::create('supplier_products', function (Blueprint $table): void {
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
                $table->decimal('data_quality_score', 5, 2)->default(0)->index();
                $table->timestamps();
                $table->unique(['catalog_source_id', 'source_product_id']);
                $table->index(['catalog_source_id', 'manufacturer_part_number']);
                $table->index(['product_id', 'review_status']);
            });
            $rememberTable('supplier_products');
        }

        if (! Schema::hasTable('catalog_import_runs')) {
            Schema::create('catalog_import_runs', function (Blueprint $table): void {
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
            $rememberTable('catalog_import_runs');
        }

        if (! Schema::hasTable('catalog_import_items')) {
            Schema::create('catalog_import_items', function (Blueprint $table): void {
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
            $rememberTable('catalog_import_items');
        }

        if (! Schema::hasTable('catalog_import_checkpoints')) {
            Schema::create('catalog_import_checkpoints', function (Blueprint $table): void {
                $table->id();
                $table->uuid('catalog_import_run_id');
                $table->string('checkpoint_key');
                $table->json('checkpoint_json')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique(['catalog_import_run_id', 'checkpoint_key']);
                $table->foreign('catalog_import_run_id')->references('id')->on('catalog_import_runs')->cascadeOnDelete();
            });
            $rememberTable('catalog_import_checkpoints');
        }

        if (! Schema::hasTable('supplier_category_mappings')) {
            Schema::create('supplier_category_mappings', function (Blueprint $table): void {
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
            $rememberTable('supplier_category_mappings');
        }

        if (! Schema::hasTable('supplier_product_assets')) {
            Schema::create('supplier_product_assets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('supplier_product_id')->constrained('supplier_products')->cascadeOnDelete();
                $table->string('asset_type', 40);
                $table->text('original_url')->nullable();
                $table->text('canonical_url')->nullable();
                $table->string('local_path')->nullable();
                $table->string('mime_type', 120)->nullable();
                $table->string('checksum', 64)->nullable()->index();
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->string('rights_status', 40)->default('pending_review');
                $table->string('download_status', 40)->default('not_requested');
                $table->timestamp('retrieved_at')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->string('alt_text')->nullable();
                $table->timestamps();
                $table->index(['supplier_product_id', 'asset_type']);
            });
            $rememberTable('supplier_product_assets');
        }

        if (! Schema::hasTable('catalog_review_tasks')) {
            Schema::create('catalog_review_tasks', function (Blueprint $table): void {
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
            $rememberTable('catalog_review_tasks');
        }

        if (! Schema::hasTable('catalog_change_events')) {
            Schema::create('catalog_change_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
                $table->foreignId('supplier_product_id')->nullable()->constrained('supplier_products')->nullOnDelete();
                $table->uuid('catalog_import_run_id')->nullable();
                $table->string('event_type', 40)->index();
                $table->json('before_json')->nullable();
                $table->json('after_json')->nullable();
                $table->timestamps();
                $table->foreign('catalog_import_run_id')->references('id')->on('catalog_import_runs')->nullOnDelete();
            });
            $rememberTable('catalog_change_events');
        }

        $this->extendColumn('catalog_import_runs', 'source_file', fn (Blueprint $table) => $table->text('source_file')->nullable(), $rememberColumn);
        $this->extendColumn('catalog_import_runs', 'file_checksum', fn (Blueprint $table) => $table->string('file_checksum', 64)->nullable()->index(), $rememberColumn);
        $this->extendColumn('catalog_import_runs', 'last_line', fn (Blueprint $table) => $table->unsignedBigInteger('last_line')->default(0), $rememberColumn);
        $this->extendColumn('catalog_import_runs', 'failed_records', fn (Blueprint $table) => $table->unsignedBigInteger('failed_records')->default(0), $rememberColumn);
        $this->extendColumn('catalog_import_runs', 'skipped_records', fn (Blueprint $table) => $table->unsignedBigInteger('skipped_records')->default(0), $rememberColumn);

        $this->extendColumn('products', 'visibility_status', fn (Blueprint $table) => $table->string('visibility_status', 40)->default('public')->index(), $rememberColumn);
        $this->extendColumn('products', 'search_keywords', fn (Blueprint $table) => $table->text('search_keywords')->nullable(), $rememberColumn);

        foreach ([
            'meta_keywords' => fn (Blueprint $table) => $table->text('meta_keywords')->nullable(),
            'og_title' => fn (Blueprint $table) => $table->string('og_title')->nullable(),
            'og_description' => fn (Blueprint $table) => $table->text('og_description')->nullable(),
            'og_image' => fn (Blueprint $table) => $table->text('og_image')->nullable(),
            'twitter_title' => fn (Blueprint $table) => $table->string('twitter_title')->nullable(),
            'twitter_description' => fn (Blueprint $table) => $table->text('twitter_description')->nullable(),
            'twitter_image' => fn (Blueprint $table) => $table->text('twitter_image')->nullable(),
            'breadcrumb_schema' => fn (Blueprint $table) => $table->json('breadcrumb_schema')->nullable(),
            'product_schema' => fn (Blueprint $table) => $table->json('product_schema')->nullable(),
            'source_notes' => fn (Blueprint $table) => $table->text('source_notes')->nullable(),
            'last_updated' => fn (Blueprint $table) => $table->timestamp('last_updated')->nullable(),
            'advisory_disclaimer' => fn (Blueprint $table) => $table->text('advisory_disclaimer')->nullable(),
        ] as $column => $callback) {
            $this->extendColumn('product_seo_meta', $column, $callback, $rememberColumn);
        }

        $this->createElecforestTables($rememberTable);

        DB::table('elecforest_schema_states')->updateOrInsert(
            ['migration_key' => self::STATE_KEY],
            ['created_resources' => json_encode($created), 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        $state = Schema::hasTable('elecforest_schema_states')
            ? DB::table('elecforest_schema_states')->where('migration_key', self::STATE_KEY)->value('created_resources')
            : null;
        $created = json_decode((string) $state, true) ?: ['tables' => [], 'columns' => []];

        foreach (array_reverse($created['columns'] ?? []) as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                if (($table === 'products' && $column === 'visibility_status')
                    || ($table === 'catalog_import_runs' && $column === 'file_checksum')) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex([$column]));
                }
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
            }
        }

        foreach (array_reverse($created['tables'] ?? []) as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('elecforest_schema_states')) {
            DB::table('elecforest_schema_states')->where('migration_key', self::STATE_KEY)->delete();
            if (DB::table('elecforest_schema_states')->count() === 0) {
                Schema::drop('elecforest_schema_states');
            }
        }
    }

    private function extendColumn(string $table, string $column, Closure $callback, Closure $remember): void
    {
        if (Schema::hasTable($table) && ! Schema::hasColumn($table, $column)) {
            Schema::table($table, $callback);
            $remember($table, $column);
        }
    }

    private function createElecforestTables(Closure $remember): void
    {
        if (! Schema::hasTable('catalog_import_failures')) {
            Schema::create('catalog_import_failures', function (Blueprint $table): void {
                $table->id();
                $table->uuid('catalog_import_run_id');
                $table->unsignedBigInteger('line_number')->nullable();
                $table->string('idempotency_key', 64)->nullable();
                $table->string('error_class')->nullable();
                $table->text('error_message');
                $table->json('raw_record')->nullable();
                $table->unsignedSmallInteger('attempts')->default(1);
                $table->string('retry_status', 30)->default('pending')->index();
                $table->timestamp('last_attempted_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['catalog_import_run_id', 'line_number']);
                $table->foreign('catalog_import_run_id')->references('id')->on('catalog_import_runs')->cascadeOnDelete();
            });
            $remember('catalog_import_failures');
        }

        if (! Schema::hasTable('product_identifiers')) {
            Schema::create('product_identifiers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('catalog_source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
                $table->string('identifier_type', 40);
                $table->string('identifier_value');
                $table->string('normalized_value')->index();
                $table->boolean('is_verified')->default(false);
                $table->string('confidence_level', 40)->default('source_unreviewed');
                $table->text('source_url')->nullable();
                $table->timestamps();
                $table->unique(['product_id', 'identifier_type', 'normalized_value'], 'product_identifier_unique');
                $table->index(['identifier_type', 'normalized_value', 'is_verified'], 'product_identifier_lookup');
            });
            $remember('product_identifiers');
        }

        if (! Schema::hasTable('product_applications')) {
            Schema::create('product_applications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('catalog_source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
                $table->string('application');
                $table->string('evidence_type', 30)->default('source');
                $table->text('source_notes')->nullable();
                $table->decimal('confidence', 5, 4)->default(0);
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
                $table->unique(['product_id', 'application']);
            });
            $remember('product_applications');
        }

        if (! Schema::hasTable('product_source_specifications')) {
            Schema::create('product_source_specifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
                $table->string('source_name');
                $table->string('normalized_name');
                $table->text('source_value')->nullable();
                $table->text('normalized_value')->nullable();
                $table->string('source_unit', 30)->nullable();
                $table->string('normalized_unit', 30)->nullable();
                $table->text('source_url')->nullable();
                $table->decimal('confidence', 5, 4)->default(0);
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
                $table->unique(['product_id', 'catalog_source_id', 'normalized_name'], 'product_source_spec_unique');
            });
            $remember('product_source_specifications');
        }

        if (! Schema::hasTable('supplier_product_offers')) {
            Schema::create('supplier_product_offers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('supplier_product_id')->constrained('supplier_products')->cascadeOnDelete();
                $table->decimal('source_price', 18, 6)->nullable();
                $table->decimal('source_compare_price', 18, 6)->nullable();
                $table->string('currency', 3)->nullable();
                $table->string('availability_status', 60)->nullable();
                $table->string('quantity_text')->nullable();
                $table->timestamp('observed_at')->nullable();
                $table->text('source_url')->nullable();
                $table->json('raw_value')->nullable();
                $table->timestamps();
                $table->index(['supplier_product_id', 'observed_at']);
            });
            $remember('supplier_product_offers');
        }

        if (! Schema::hasTable('product_category_assignments')) {
            Schema::create('product_category_assignments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();
                $table->foreignId('catalog_source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->decimal('confidence', 5, 4)->default(0);
                $table->string('mapping_status', 40)->default('pending_review');
                $table->timestamps();
                $table->unique(['product_id', 'category_id']);
                $table->index(['category_id', 'is_primary']);
            });
            $remember('product_category_assignments');
        }

        if (! Schema::hasTable('product_content_versions')) {
            Schema::create('product_content_versions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('catalog_source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
                $table->unsignedInteger('version');
                $table->string('content_method', 50)->default('deterministic_assisted');
                $table->string('status', 40)->default('pending_review')->index();
                $table->json('content_json');
                $table->text('source_notes');
                $table->string('confidence_level', 40);
                $table->timestamp('last_updated');
                $table->text('advisory_disclaimer');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->unique(['product_id', 'version']);
            });
            $remember('product_content_versions');
        }
    }
};
