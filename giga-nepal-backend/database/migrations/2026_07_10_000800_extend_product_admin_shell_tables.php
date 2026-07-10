<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->productDocuments();
        $this->productRelatedItems();
        $this->productLmsLinks();
        $this->productSeoMeta();
    }

    private function productDocuments(): void
    {
        if (! Schema::hasTable('product_documents')) {
            return;
        }

        Schema::table('product_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('product_documents', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->index();
            }
            if (! Schema::hasColumn('product_documents', 'title')) {
                $table->string('title')->nullable();
            }
            if (! Schema::hasColumn('product_documents', 'document_type')) {
                $table->string('document_type')->default('datasheet')->index();
            }
            if (! Schema::hasColumn('product_documents', 'file_url')) {
                $table->string('file_url')->nullable();
            }
            if (! Schema::hasColumn('product_documents', 'source_url')) {
                $table->string('source_url')->nullable();
            }
            if (! Schema::hasColumn('product_documents', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (! Schema::hasColumn('product_documents', 'is_active')) {
                $table->boolean('is_active')->default(true)->index();
            }
        });
    }

    private function productRelatedItems(): void
    {
        if (! Schema::hasTable('product_related_items')) {
            return;
        }

        Schema::table('product_related_items', function (Blueprint $table) {
            if (! Schema::hasColumn('product_related_items', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->index();
            }
            if (! Schema::hasColumn('product_related_items', 'related_product_id')) {
                $table->unsignedBigInteger('related_product_id')->nullable()->index();
            }
            if (! Schema::hasColumn('product_related_items', 'relation_type')) {
                $table->string('relation_type')->default('alternative')->index();
            }
            if (! Schema::hasColumn('product_related_items', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (! Schema::hasColumn('product_related_items', 'sort_order')) {
                $table->integer('sort_order')->default(100);
            }
            if (! Schema::hasColumn('product_related_items', 'is_active')) {
                $table->boolean('is_active')->default(true)->index();
            }
        });
    }

    private function productLmsLinks(): void
    {
        if (! Schema::hasTable('product_lms_links')) {
            return;
        }

        Schema::table('product_lms_links', function (Blueprint $table) {
            if (! Schema::hasColumn('product_lms_links', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->index();
            }
            if (! Schema::hasColumn('product_lms_links', 'lms_course_id')) {
                $table->unsignedBigInteger('lms_course_id')->nullable()->index();
            }
            if (! Schema::hasColumn('product_lms_links', 'lms_lesson_id')) {
                $table->unsignedBigInteger('lms_lesson_id')->nullable()->index();
            }
            if (! Schema::hasColumn('product_lms_links', 'title')) {
                $table->string('title')->nullable();
            }
            if (! Schema::hasColumn('product_lms_links', 'url')) {
                $table->string('url')->nullable();
            }
            if (! Schema::hasColumn('product_lms_links', 'relation_type')) {
                $table->string('relation_type')->default('tutorial')->index();
            }
            if (! Schema::hasColumn('product_lms_links', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (! Schema::hasColumn('product_lms_links', 'is_active')) {
                $table->boolean('is_active')->default(true)->index();
            }
        });
    }

    private function productSeoMeta(): void
    {
        if (! Schema::hasTable('product_seo_meta')) {
            return;
        }

        Schema::table('product_seo_meta', function (Blueprint $table) {
            if (! Schema::hasColumn('product_seo_meta', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->index();
            }
            if (! Schema::hasColumn('product_seo_meta', 'meta_title')) {
                $table->string('meta_title')->nullable();
            }
            if (! Schema::hasColumn('product_seo_meta', 'meta_description')) {
                $table->text('meta_description')->nullable();
            }
            if (! Schema::hasColumn('product_seo_meta', 'canonical_url')) {
                $table->string('canonical_url')->nullable();
            }
            if (! Schema::hasColumn('product_seo_meta', 'robots')) {
                $table->string('robots')->default('index,follow');
            }
            if (! Schema::hasColumn('product_seo_meta', 'schema_type')) {
                $table->string('schema_type')->default('Product');
            }
            if (! Schema::hasColumn('product_seo_meta', 'confidence_level')) {
                $table->string('confidence_level')->default('manual');
            }
            if (! Schema::hasColumn('product_seo_meta', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Additive live upgrade: keep admin data.
    }
};
