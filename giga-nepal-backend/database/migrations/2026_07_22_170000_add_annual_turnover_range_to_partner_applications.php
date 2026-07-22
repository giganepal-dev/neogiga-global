<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['seller_applications', 'distributor_applications'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'annual_turnover_range')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->string('annual_turnover_range', 40)->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally additive: preserve submitted commercial-profile history.
    }
};
