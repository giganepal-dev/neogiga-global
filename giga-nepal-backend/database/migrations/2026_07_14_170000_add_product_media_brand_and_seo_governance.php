<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->extendProductImages();
        $this->extendProductBrands();
        $this->extendMarketplaces();
        $this->extendProductSeoMeta();
        $this->createSeoVersions();
    }

    private function extendProductImages(): void
    {
        if (! Schema::hasTable('product_images')) {
            return;
        }

        Schema::table('product_images', function (Blueprint $table) {
            if (! Schema::hasColumn('product_images', 'storage_disk')) {
                $table->string('storage_disk', 80)->nullable();
            }
            if (! Schema::hasColumn('product_images', 'source_file')) {
                $table->text('source_file')->nullable();
            }
            if (! Schema::hasColumn('product_images', 'source_page_url')) {
                $table->text('source_page_url')->nullable();
            }
            if (! Schema::hasColumn('product_images', 'imported_at')) {
                $table->timestamp('imported_at')->nullable();
            }
            if (! Schema::hasColumn('product_images', 'data_year')) {
                $table->unsignedSmallInteger('data_year')->nullable();
            }
            if (! Schema::hasColumn('product_images', 'license_note')) {
                $table->text('license_note')->nullable();
            }
            if (! Schema::hasColumn('product_images', 'confidence_level')) {
                $table->string('confidence_level', 120)->nullable();
            }
            if (! Schema::hasColumn('product_images', 'original_raw_value')) {
                $table->text('original_raw_value')->nullable();
            }
            if (! Schema::hasColumn('product_images', 'normalized_value')) {
                $table->text('normalized_value')->nullable();
            }
            if (! Schema::hasColumn('product_images', 'region_visibility')) {
                $table->json('region_visibility')->nullable();
            }
        });
    }

    private function extendProductBrands(): void
    {
        if (! Schema::hasTable('product_brands')) {
            return;
        }

        Schema::table('product_brands', function (Blueprint $table) {
            if (! Schema::hasColumn('product_brands', 'short_description')) {
                $table->text('short_description')->nullable();
            }
            if (! Schema::hasColumn('product_brands', 'banner_path')) {
                $table->text('banner_path')->nullable();
            }
            if (! Schema::hasColumn('product_brands', 'country_visibility')) {
                $table->json('country_visibility')->nullable();
            }
            if (! Schema::hasColumn('product_brands', 'category_visibility')) {
                $table->json('category_visibility')->nullable();
            }
            if (! Schema::hasColumn('product_brands', 'menu_placement')) {
                $table->string('menu_placement', 40)->default('primary');
            }
            if (! Schema::hasColumn('product_brands', 'publication_starts_at')) {
                $table->timestamp('publication_starts_at')->nullable();
            }
            if (! Schema::hasColumn('product_brands', 'publication_ends_at')) {
                $table->timestamp('publication_ends_at')->nullable();
            }
            if (! Schema::hasColumn('product_brands', 'seo_title')) {
                $table->string('seo_title')->nullable();
            }
            if (! Schema::hasColumn('product_brands', 'seo_description')) {
                $table->text('seo_description')->nullable();
            }
            if (! Schema::hasColumn('product_brands', 'seo_keywords')) {
                $table->text('seo_keywords')->nullable();
            }
            if (! Schema::hasColumn('product_brands', 'canonical_url')) {
                $table->text('canonical_url')->nullable();
            }
            if (! Schema::hasColumn('product_brands', 'is_menu_visible')) {
                $table->boolean('is_menu_visible')->default(true);
            }
            if (! Schema::hasColumn('product_brands', 'display_desktop')) {
                $table->boolean('display_desktop')->default(true);
            }
            if (! Schema::hasColumn('product_brands', 'display_mobile')) {
                $table->boolean('display_mobile')->default(true);
            }
            if (! Schema::hasColumn('product_brands', 'hide_when_unavailable')) {
                $table->boolean('hide_when_unavailable')->default(false);
            }
            if (! Schema::hasColumn('product_brands', 'landing_page_enabled')) {
                $table->boolean('landing_page_enabled')->default(true);
            }
        });
    }

    private function extendMarketplaces(): void
    {
        if (! Schema::hasTable('marketplaces')) {
            return;
        }

        Schema::table('marketplaces', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplaces', 'seo_marketplace_name')) {
                $table->string('seo_marketplace_name')->nullable();
            }
            if (! Schema::hasColumn('marketplaces', 'has_local_warehouse')) {
                $table->boolean('has_local_warehouse')->default(false);
            }
            if (! Schema::hasColumn('marketplaces', 'warehouse_display_name')) {
                $table->string('warehouse_display_name')->nullable();
            }
            if (! Schema::hasColumn('marketplaces', 'seo_fulfilment_phrase')) {
                $table->string('seo_fulfilment_phrase')->nullable();
            }
            if (! Schema::hasColumn('marketplaces', 'seo_site_suffix')) {
                $table->string('seo_site_suffix')->nullable();
            }
        });
    }

    private function extendProductSeoMeta(): void
    {
        if (! Schema::hasTable('product_seo_meta')) {
            return;
        }

        Schema::table('product_seo_meta', function (Blueprint $table) {
            if (! Schema::hasColumn('product_seo_meta', 'generated_title')) {
                $table->string('generated_title')->nullable();
            }
            if (! Schema::hasColumn('product_seo_meta', 'generated_description')) {
                $table->text('generated_description')->nullable();
            }
            if (! Schema::hasColumn('product_seo_meta', 'generated_canonical_url')) {
                $table->text('generated_canonical_url')->nullable();
            }
            if (! Schema::hasColumn('product_seo_meta', 'generated_robots')) {
                $table->string('generated_robots', 80)->nullable();
            }
            if (! Schema::hasColumn('product_seo_meta', 'robots_reason')) {
                $table->text('robots_reason')->nullable();
            }
            if (! Schema::hasColumn('product_seo_meta', 'template_version')) {
                $table->string('template_version', 120)->nullable();
            }
            if (! Schema::hasColumn('product_seo_meta', 'is_manual_override')) {
                $table->boolean('is_manual_override')->default(false);
            }
            if (! Schema::hasColumn('product_seo_meta', 'is_locked')) {
                $table->boolean('is_locked')->default(false);
            }
            if (! Schema::hasColumn('product_seo_meta', 'active_source')) {
                $table->string('active_source', 40)->default('generated');
            }
            if (! Schema::hasColumn('product_seo_meta', 'modified_by')) {
                $table->unsignedBigInteger('modified_by')->nullable();
            }
            if (! Schema::hasColumn('product_seo_meta', 'generated_at')) {
                $table->timestamp('generated_at')->nullable();
            }
        });
    }

    private function createSeoVersions(): void
    {
        if (Schema::hasTable('catalog_seo_versions')) {
            return;
        }

        Schema::create('catalog_seo_versions', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 80);
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('marketplace_id')->nullable();
            $table->string('locale', 20)->default('en');
            $table->unsignedInteger('version')->default(1);
            $table->string('active_source', 40)->default('generated');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('robots', 80)->nullable();
            $table->text('robots_reason')->nullable();
            $table->string('template_version', 120)->nullable();
            $table->string('change_type', 80)->default('generated');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('source_notes')->nullable();
            $table->string('confidence_level', 120)->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->text('advisory_disclaimer')->default('Advisory only');
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['marketplace_id', 'locale']);
            $table->index(['entity_type', 'entity_id', 'version']);
        });
    }

    public function down(): void
    {
        // Upgrade-only migration: intentionally preserve governance fields and history.
    }
};
