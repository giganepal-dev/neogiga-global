<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_synonyms')) {
            Schema::create('category_synonyms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('product_categories')->restrictOnDelete();
                $table->string('synonym', 190);
                $table->string('normalized_synonym', 190)->unique();
                $table->string('source', 80)->default('manual');
                $table->decimal('confidence', 5, 4)->default(1);
                $table->timestamps();
                $table->index('category_id');
            });
        }

        if (! Schema::hasTable('category_import_reviews')) {
            Schema::create('category_import_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('catalog_source_id')->nullable()->constrained('catalog_sources')->nullOnDelete();
                $table->foreignId('proposed_parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
                $table->foreignId('proposed_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
                $table->uuid('import_batch_id')->nullable()->index();
                $table->string('source_name', 190)->nullable();
                $table->string('source_key', 191)->nullable()->index();
                $table->string('source_category_name', 500)->nullable();
                $table->text('source_category_path')->nullable();
                $table->string('manufacturer_name', 190)->nullable();
                $table->string('mpn', 190)->nullable();
                $table->decimal('confidence', 5, 4)->default(0);
                $table->string('matched_by', 100)->default('unresolved');
                $table->json('reasons')->nullable();
                $table->json('context')->nullable();
                $table->string('status', 40)->default('pending_review')->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['catalog_source_id', 'source_key'], 'category_import_reviews_source_key_unique');
            });
        }

        if (! Schema::hasTable('category_creation_audits')) {
            Schema::create('category_creation_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('product_categories')->restrictOnDelete();
                $table->foreignId('parent_category_id')->constrained('product_categories')->restrictOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->uuid('import_batch_id')->nullable()->index();
                $table->string('source_name', 190)->nullable();
                $table->text('source_url')->nullable();
                $table->string('source_file')->nullable();
                $table->text('source_page_url')->nullable();
                $table->timestamp('downloaded_at')->nullable();
                $table->timestamp('imported_at')->nullable();
                $table->string('data_year', 20)->nullable();
                $table->text('license_note')->nullable();
                $table->string('confidence_level', 80)->nullable();
                $table->text('original_raw_value')->nullable();
                $table->text('normalized_value')->nullable();
                $table->string('created_by_type', 40)->default('admin');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Governance history is intentionally retained during rollback.
    }
};
