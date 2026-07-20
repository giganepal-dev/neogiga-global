<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'home_marketplace_id')) {
                $table->unsignedBigInteger('home_marketplace_id')->nullable()->after('role_id')->index();
                $table->foreign('home_marketplace_id')
                    ->references('id')
                    ->on('marketplaces')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'home_marketplace_id')) {
                try {
                    $table->dropForeign(['home_marketplace_id']);
                } catch (Throwable) {
                }
                $table->dropColumn('home_marketplace_id');
            }
        });
    }
};
