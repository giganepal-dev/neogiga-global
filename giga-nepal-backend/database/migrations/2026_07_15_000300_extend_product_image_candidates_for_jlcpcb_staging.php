<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_image_candidates')) {
            return;
        }

        Schema::table('product_image_candidates', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_image_candidates', 'source_name')) {
                $table->string('source_name')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'source_url')) {
                $table->text('source_url')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'source_file')) {
                $table->text('source_file')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'source_page_url')) {
                $table->text('source_page_url')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'source_part_id')) {
                $table->string('source_part_id')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'downloaded_at')) {
                $table->timestamp('downloaded_at')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'imported_at')) {
                $table->timestamp('imported_at')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'data_year')) {
                $table->unsignedSmallInteger('data_year')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'license_note')) {
                $table->text('license_note')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'confidence_level')) {
                $table->string('confidence_level', 120)->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'original_raw_value')) {
                $table->json('original_raw_value')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'normalized_value')) {
                $table->text('normalized_value')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'source_checksum')) {
                $table->string('source_checksum', 64)->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'rights_basis')) {
                $table->text('rights_basis')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'rights_status')) {
                $table->string('rights_status')->default('pending_review');
            }
            if (! Schema::hasColumn('product_image_candidates', 'rights_review_required')) {
                $table->boolean('rights_review_required')->default(true);
            }
            if (! Schema::hasColumn('product_image_candidates', 'is_active')) {
                $table->boolean('is_active')->default(false);
            }
            if (! Schema::hasColumn('product_image_candidates', 'image_role')) {
                $table->string('image_role', 40)->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'pixel_width')) {
                $table->unsignedSmallInteger('pixel_width')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'pixel_height')) {
                $table->unsignedSmallInteger('pixel_height')->nullable();
            }
            if (! Schema::hasColumn('product_image_candidates', 'asset_fetch_status')) {
                $table->string('asset_fetch_status', 40)->default('not_requested');
            }
        });

        Schema::table('product_image_candidates', function (Blueprint $table): void {
            if (! Schema::hasIndex('product_image_candidates', 'pic_rights_active_idx')) {
                $table->index(['rights_status', 'is_active'], 'pic_rights_active_idx');
            }
            if (! Schema::hasIndex('product_image_candidates', 'pic_source_part_idx')) {
                $table->index(['source_name', 'source_part_id'], 'pic_source_part_idx');
            }
            if (! Schema::hasIndex('product_image_candidates', 'pic_product_active_idx')) {
                $table->index(['product_id', 'is_active'], 'pic_product_active_idx');
            }
            if (! Schema::hasIndex('product_image_candidates', 'pic_fetch_status_idx')) {
                $table->index('asset_fetch_status', 'pic_fetch_status_idx');
            }
        });
    }

    public function down(): void
    {
        // Deliberately non-destructive: candidate provenance and rights-review
        // history must survive application rollback.
    }
};
