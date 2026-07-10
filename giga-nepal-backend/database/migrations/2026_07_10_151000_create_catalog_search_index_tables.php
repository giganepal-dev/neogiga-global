<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('catalog_index_rebuild_jobs')) {
            Schema::create('catalog_index_rebuild_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('source_code', 80)->index();
                $table->string('scope', 80)->default('approved_imports')->index();
                $table->string('status', 40)->default('queued')->index();
                $table->unsignedBigInteger('queued_by')->nullable()->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('product_count')->default(0);
                $table->unsignedInteger('indexed_count')->default(0);
                $table->unsignedInteger('facet_count')->default(0);
                $table->text('error')->nullable();
                $table->jsonb('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_search_documents')) {
            Schema::create('product_search_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('source_code', 80)->index();
                $table->string('title');
                $table->string('sku')->nullable()->index();
                $table->string('mpn')->nullable()->index();
                $table->string('manufacturer')->nullable()->index();
                $table->string('category')->nullable()->index();
                $table->string('status', 40)->nullable()->index();
                $table->string('visibility_status', 40)->nullable()->index();
                $table->string('review_status', 40)->nullable()->index();
                $table->decimal('data_quality_score', 5, 2)->default(0)->index();
                $table->longText('searchable_text');
                $table->jsonb('facets')->nullable();
                $table->timestamp('indexed_at')->nullable()->index();
                $table->timestamps();
                $table->unique(['product_id', 'source_code']);
            });
        }

        if (! Schema::hasTable('product_facet_values')) {
            Schema::create('product_facet_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('source_code', 80)->index();
                $table->string('facet_name', 120)->index();
                $table->string('facet_value', 500);
                $table->timestamp('indexed_at')->nullable();
                $table->timestamps();
                $table->index(['facet_name', 'facet_value']);
                $table->index(['product_id', 'facet_name']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_facet_values');
        Schema::dropIfExists('product_search_documents');
        Schema::dropIfExists('catalog_index_rebuild_jobs');
    }
};
