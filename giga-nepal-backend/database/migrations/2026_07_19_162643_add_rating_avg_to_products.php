<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'rating_avg')) {
                $table->decimal('rating_avg', 3, 2)->nullable()->default(0);
            }
            if (! Schema::hasColumn('products', 'rating_count')) {
                $table->unsignedInteger('rating_count')->nullable()->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'rating_avg')) {
                $table->dropColumn('rating_avg');
            }
            if (Schema::hasColumn('products', 'rating_count')) {
                $table->dropColumn('rating_count');
            }
        });
    }
};
