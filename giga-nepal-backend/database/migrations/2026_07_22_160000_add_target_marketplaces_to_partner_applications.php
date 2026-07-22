<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['seller_applications', 'distributor_applications'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'target_marketplace_ids')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->json('target_marketplace_ids')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally additive: preserve submitted marketplace choices on rollback.
    }
};
