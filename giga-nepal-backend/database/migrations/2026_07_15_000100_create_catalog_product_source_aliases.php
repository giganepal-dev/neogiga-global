<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('catalog_product_source_aliases')) {
            return;
        }

        Schema::create('catalog_product_source_aliases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('canonical_product_source_id')
                ->constrained('catalog_product_sources')
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('source_part_id');
            $table->uuid('import_batch_id')->nullable();
            $table->string('source_payload_hash', 64)->index();
            $table->string('match_strategy', 80);

            // Complete source and normalization provenance is required for every alias.
            $table->string('source_name');
            $table->text('source_url');
            $table->text('source_file');
            $table->text('source_page_url');
            $table->timestampTz('downloaded_at');
            $table->timestampTz('imported_at');
            $table->string('data_year', 20);
            $table->text('license_note');
            $table->string('confidence_level', 80)->index();
            $table->jsonb('original_raw_value');
            $table->jsonb('normalized_value');
            $table->jsonb('raw_snapshot');
            $table->timestampTz('last_synced_at');
            $table->timestampsTz();

            $table->unique(['source_id', 'source_part_id'], 'catalog_product_source_aliases_source_part_unique');
            $table->index(['product_id', 'source_id'], 'catalog_product_source_aliases_product_source_idx');
            $table->foreign('import_batch_id')
                ->references('id')
                ->on('catalog_import_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Deliberately non-destructive: alias provenance must survive migration rollback.
    }
};
