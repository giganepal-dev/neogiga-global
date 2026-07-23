<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the unique index first if it exists
            if (Schema::hasIndex('users', 'users_api_token_hash_unique')) {
                $table->dropIndex('users_api_token_hash_unique');
            }
            // Then drop the column
            if (Schema::hasColumn('users', 'api_token_hash')) {
                $table->dropColumn('api_token_hash');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'api_token_hash')) {
                $table->string('api_token_hash', 64)->nullable()->unique()->after('remember_token');
            }
        });
    }
};
