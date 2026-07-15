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
            $columns = Schema::getColumnListing('product_documents');
            
            if (! in_array('revision', $columns)) {
                $table->string('revision')->nullable()->after('title');
            }
            if (! in_array('document_date', $columns)) {
                $table->date('document_date')->nullable()->after('revision');
            }
            if (! in_array('file_size', $columns)) {
                $table->unsignedBigInteger('file_size')->nullable()->after('file_url');
            }
            if (! in_array('mime_type', $columns)) {
                $table->string('mime_type')->nullable()->after('file_size');
            }
            if (! in_array('download_count', $columns)) {
                $table->unsignedInteger('download_count')->default(0)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        // No rollback for additive changes
    }
};
