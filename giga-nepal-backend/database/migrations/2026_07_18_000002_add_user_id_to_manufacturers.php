<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-column guards: fresh-migrate chains already create some of these
        // columns earlier, while prod's incremental history did not.
        Schema::table('manufacturers', function (Blueprint $table) {
            if (! Schema::hasColumn('manufacturers', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('manufacturers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('country_of_origin');
            }
            if (! Schema::hasColumn('manufacturers', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('manufacturers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'is_active', 'is_verified']);
        });
    }
};
