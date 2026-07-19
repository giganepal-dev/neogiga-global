<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_profiles') && ! Schema::hasColumn('customer_profiles', 'company_name')) {
            Schema::table('customer_profiles', function (Blueprint $table) {
                $table->string('company_name', 190)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_profiles') && Schema::hasColumn('customer_profiles', 'company_name')) {
            Schema::table('customer_profiles', function (Blueprint $table) {
                $table->dropColumn('company_name');
            });
        }
    }
};
