<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_images')) {
            return;
        }

        Schema::table('product_images', function (Blueprint $table) {
            if (! Schema::hasColumn('product_images', 'original_url')) {
                $table->text('original_url')->nullable()->after('file_path');
            }
            if (! Schema::hasColumn('product_images', 'source_url')) {
                $table->text('source_url')->nullable()->after('original_url');
            }
            if (! Schema::hasColumn('product_images', 'source_name')) {
                $table->string('source_name')->nullable()->after('source_url');
            }
            if (! Schema::hasColumn('product_images', 'source_license')) {
                $table->string('source_license')->nullable()->after('source_name');
            }
            if (! Schema::hasColumn('product_images', 'copyright')) {
                $table->text('copyright')->nullable()->after('source_license');
            }
            if (! Schema::hasColumn('product_images', 'checksum')) {
                $table->string('checksum', 128)->nullable()->after('copyright');
            }
            if (! Schema::hasColumn('product_images', 'width')) {
                $table->unsignedInteger('width')->nullable()->after('checksum');
            }
            if (! Schema::hasColumn('product_images', 'height')) {
                $table->unsignedInteger('height')->nullable()->after('width');
            }
            if (! Schema::hasColumn('product_images', 'downloaded_at')) {
                $table->timestamp('downloaded_at')->nullable()->after('height');
            }
            if (! Schema::hasColumn('product_images', 'metadata')) {
                $table->json('metadata')->nullable()->after('downloaded_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_images')) {
            return;
        }

        Schema::table('product_images', function (Blueprint $table) {
            foreach (['metadata', 'downloaded_at', 'height', 'width', 'checksum', 'copyright', 'source_license', 'source_name', 'source_url', 'original_url'] as $column) {
                if (Schema::hasColumn('product_images', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
