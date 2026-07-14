<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'source_url')) {
                $table->text('source_url')->nullable()->change();
            }

            if (Schema::hasColumn('products', 'source_page_url')) {
                $table->text('source_page_url')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Deliberately non-destructive: imported provenance URLs may exceed 255
        // characters and must not be truncated during rollback.
    }
};
