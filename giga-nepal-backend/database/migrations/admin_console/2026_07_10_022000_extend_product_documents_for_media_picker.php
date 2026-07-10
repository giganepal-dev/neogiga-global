<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_documents')) {
            return;
        }

        Schema::table('product_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('product_documents', 'media_asset_id')) {
                $table->foreignId('media_asset_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('admin_media_assets')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_documents') || ! Schema::hasColumn('product_documents', 'media_asset_id')) {
            return;
        }

        Schema::table('product_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_asset_id');
        });
    }
};
