<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_settings')) {
            Schema::create('admin_settings', function (Blueprint $table) {
                $table->id();
                $table->string('group')->default('general')->index();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type')->default('string');
                $table->boolean('is_public')->default(false)->index();
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('admin_media_assets')) {
            Schema::create('admin_media_assets', function (Blueprint $table) {
                $table->id();
                $table->string('disk')->default('public');
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->string('folder')->nullable()->index();
                $table->string('title')->nullable();
                $table->text('alt_text')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['disk', 'folder']);
            });
        }

        if (!Schema::hasTable('seo_pages')) {
            Schema::create('seo_pages', function (Blueprint $table) {
                $table->id();
                $table->string('url_path')->unique();
                $table->string('route_name')->nullable();
                $table->string('title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('canonical_url')->nullable();
                $table->string('robots')->default('index,follow');
                $table->string('og_image')->nullable();
                $table->json('schema_json')->nullable();
                $table->boolean('is_indexable')->default(true)->index();
                $table->string('source_name')->nullable();
                $table->text('source_url')->nullable();
                $table->string('source_file')->nullable();
                $table->text('source_page_url')->nullable();
                $table->timestamp('downloaded_at')->nullable();
                $table->timestamp('imported_at')->nullable();
                $table->string('data_year')->nullable();
                $table->text('license_note')->nullable();
                $table->string('confidence_level')->default('manual');
                $table->text('original_raw_value')->nullable();
                $table->text('normalized_value')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('seo_redirects')) {
            Schema::create('seo_redirects', function (Blueprint $table) {
                $table->id();
                $table->string('from_path')->unique();
                $table->string('to_url');
                $table->unsignedSmallInteger('status_code')->default(301);
                $table->boolean('is_active')->default(true)->index();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('product_seo_meta')) {
            Schema::table('product_seo_meta', function (Blueprint $table) {
                if (!Schema::hasColumn('product_seo_meta', 'product_id')) {
                    $table->foreignId('product_id')->nullable()->after('id')->constrained('products')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('product_seo_meta', 'title')) {
                    $table->string('title')->nullable()->after('product_id');
                }
                if (!Schema::hasColumn('product_seo_meta', 'meta_description')) {
                    $table->text('meta_description')->nullable()->after('title');
                }
                if (!Schema::hasColumn('product_seo_meta', 'canonical_url')) {
                    $table->string('canonical_url')->nullable()->after('meta_description');
                }
                if (!Schema::hasColumn('product_seo_meta', 'robots')) {
                    $table->string('robots')->default('index,follow')->after('canonical_url');
                }
                if (!Schema::hasColumn('product_seo_meta', 'schema_json')) {
                    $table->json('schema_json')->nullable()->after('robots');
                }
                if (!Schema::hasColumn('product_seo_meta', 'metadata')) {
                    $table->json('metadata')->nullable()->after('schema_json');
                }
            });
        }
    }

    public function down(): void
    {
        // Non-destructive by design. This migration is an additive upgrade layer.
    }
};

