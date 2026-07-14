<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_profiles') && ! Schema::hasColumn('customer_profiles', 'marketplace_id')) {
            Schema::table('customer_profiles', function (Blueprint $table): void {
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_profiles') && Schema::hasColumn('customer_profiles', 'marketplace_id')) {
            Schema::table('customer_profiles', function (Blueprint $table): void {
                $table->dropColumn('marketplace_id');
            });
        }
    }
};
