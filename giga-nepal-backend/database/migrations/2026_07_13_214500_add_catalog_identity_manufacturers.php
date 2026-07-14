<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('manufacturers')) {
            Schema::create('manufacturers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('legal_name')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('country_of_origin')->nullable()->index();
                $table->string('official_website')->nullable();
                $table->text('overview')->nullable();
                $table->string('source_name')->nullable();
                $table->string('source_url')->nullable();
                $table->string('source_file')->nullable();
                $table->string('source_page_url')->nullable();
                $table->timestamp('downloaded_at')->nullable();
                $table->timestamp('imported_at')->nullable();
                $table->string('data_year')->nullable();
                $table->text('license_note')->nullable();
                $table->string('confidence_level', 40)->default('baseline')->index();
                $table->text('original_raw_value')->nullable();
                $table->text('normalized_value')->nullable();
                $table->timestamp('last_verified_at')->nullable();
                $table->string('seo_title')->nullable();
                $table->text('seo_description')->nullable();
                $table->json('metadata')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                $table->index('name');
            });
        }

        if (! Schema::hasTable('manufacturer_aliases')) {
            Schema::create('manufacturer_aliases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('manufacturer_id')->constrained('manufacturers')->cascadeOnDelete();
                $table->string('alias');
                $table->string('normalized_alias');
                $table->string('source_name')->nullable();
                $table->string('source_url')->nullable();
                $table->unsignedTinyInteger('confidence_score')->default(60);
                $table->timestamps();

                $table->unique(['manufacturer_id', 'normalized_alias']);
                $table->index('normalized_alias');
            });
        }

        if (Schema::hasTable('product_brands') && ! Schema::hasColumn('product_brands', 'manufacturer_id')) {
            Schema::table('product_brands', function (Blueprint $table) {
                $table->foreignId('manufacturer_id')->nullable()->after('country_id')->constrained('manufacturers')->nullOnDelete();
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'manufacturer_id')) {
                    $table->foreignId('manufacturer_id')->nullable()->after('brand_id')->constrained('manufacturers')->nullOnDelete();
                }
                if (! Schema::hasColumn('products', 'manufacturer_name')) {
                    $table->string('manufacturer_name')->nullable()->after('manufacturer_id')->index();
                }
                if (! Schema::hasColumn('products', 'normalized_mpn')) {
                    $table->string('normalized_mpn')->nullable()->after('mpn')->index();
                }
                if (! Schema::hasColumn('products', 'gtin')) {
                    $table->string('gtin')->nullable()->index();
                }
                if (! Schema::hasColumn('products', 'hs_code')) {
                    $table->string('hs_code')->nullable()->index();
                }
                if (! Schema::hasColumn('products', 'eccn')) {
                    $table->string('eccn')->nullable()->index();
                }
                if (! Schema::hasColumn('products', 'lifecycle_status')) {
                    $table->string('lifecycle_status', 60)->nullable()->index();
                }
                if (! Schema::hasColumn('products', 'source_url')) {
                    $table->string('source_url')->nullable();
                }
                if (! Schema::hasColumn('products', 'source_name')) {
                    $table->string('source_name')->nullable();
                }
                if (! Schema::hasColumn('products', 'source_file')) {
                    $table->string('source_file')->nullable();
                }
                if (! Schema::hasColumn('products', 'source_page_url')) {
                    $table->string('source_page_url')->nullable();
                }
                if (! Schema::hasColumn('products', 'downloaded_at')) {
                    $table->timestamp('downloaded_at')->nullable();
                }
                if (! Schema::hasColumn('products', 'imported_at')) {
                    $table->timestamp('imported_at')->nullable();
                }
                if (! Schema::hasColumn('products', 'data_year')) {
                    $table->string('data_year')->nullable();
                }
                if (! Schema::hasColumn('products', 'license_note')) {
                    $table->text('license_note')->nullable();
                }
                if (! Schema::hasColumn('products', 'confidence_level')) {
                    $table->string('confidence_level', 40)->nullable()->index();
                }
                if (! Schema::hasColumn('products', 'original_raw_value')) {
                    $table->text('original_raw_value')->nullable();
                }
                if (! Schema::hasColumn('products', 'normalized_value')) {
                    $table->text('normalized_value')->nullable();
                }
                if (! Schema::hasColumn('products', 'last_verified_at')) {
                    $table->timestamp('last_verified_at')->nullable();
                }
            });

            DB::table('products')
                ->whereNotNull('mpn')
                ->where(function ($query) {
                    $query->whereNull('normalized_mpn')->orWhere('normalized_mpn', '');
                })
                ->orderBy('id')
                ->select(['id', 'mpn'])
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('products')
                            ->where('id', $row->id)
                            ->update([
                                'normalized_mpn' => strtoupper((string) preg_replace('/\s+/', '', (string) $row->mpn)),
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            if (Schema::hasColumn('products', 'manufacturer_id')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropConstrainedForeignId('manufacturer_id');
                });
            }

            Schema::table('products', function (Blueprint $table) {
                foreach ([
                    'manufacturer_name',
                    'normalized_mpn',
                    'gtin',
                    'hs_code',
                    'eccn',
                    'lifecycle_status',
                    'source_url',
                    'source_name',
                    'source_file',
                    'source_page_url',
                    'downloaded_at',
                    'imported_at',
                    'data_year',
                    'license_note',
                    'confidence_level',
                    'original_raw_value',
                    'normalized_value',
                    'last_verified_at',
                ] as $column) {
                    if (Schema::hasColumn('products', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('product_brands') && Schema::hasColumn('product_brands', 'manufacturer_id')) {
            Schema::table('product_brands', function (Blueprint $table) {
                $table->dropConstrainedForeignId('manufacturer_id');
            });
        }

        Schema::dropIfExists('manufacturer_aliases');
        Schema::dropIfExists('manufacturers');
    }
};
