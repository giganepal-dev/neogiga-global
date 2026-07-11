<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');
        }

        Schema::create('catalog_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('source_url')->nullable();
            $table->text('license_notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('catalog_import_batches', function (Blueprint $table) use ($driver) {
            $id = $table->uuid('id')->primary();
            if ($driver === 'pgsql') {
                $id->default(DB::raw('gen_random_uuid()'));
            }
            $table->foreignId('source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('checksum')->nullable()->index();
            $table->string('status')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('rows_read')->default(0);
            $table->unsignedBigInteger('rows_inserted')->default(0);
            $table->unsignedBigInteger('rows_updated')->default(0);
            $table->unsignedBigInteger('rows_skipped')->default(0);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('catalog_product_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('source_part_id');
            $table->uuid('import_batch_id')->nullable();
            $table->text('source_url')->nullable();
            $table->string('source_payload_hash', 64)->index();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->decimal('data_quality_score', 5, 2)->default(0);
            $table->string('review_status')->default('pending_review')->index();
            $table->jsonb('raw_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['source_id', 'source_part_id']);
            $table->unique(['product_id', 'source_id']);
            $table->foreign('import_batch_id')->references('id')->on('catalog_import_batches')->nullOnDelete();
        });

        Schema::create('catalog_import_errors', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id');
            $table->string('source_part_id')->nullable()->index();
            $table->text('reason');
            $table->jsonb('raw_record')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('batch_id')->references('id')->on('catalog_import_batches')->cascadeOnDelete();
        });

        Schema::create('catalog_distributor_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->uuid('import_batch_id')->nullable();
            $table->string('distributor');
            $table->string('sku');
            $table->jsonb('price_breaks')->nullable();
            $table->bigInteger('stock')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamp('fetched_at')->nullable();
            $table->jsonb('marketplace_visibility')->nullable();
            $table->string('review_status')->default('pending_review')->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->unique(['distributor', 'sku']);
            $table->index(['product_id', 'review_status']);
            $table->foreign('import_batch_id')->references('id')->on('catalog_import_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_distributor_offers');
        Schema::dropIfExists('catalog_import_errors');
        Schema::dropIfExists('catalog_product_sources');
        Schema::dropIfExists('catalog_import_batches');
        Schema::dropIfExists('catalog_sources');
    }
};
