<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portal scoping columns the reseller/distributor/b2b portals filter by.
 * The portal controllers and views were built against these; without them
 * every dashboard query 500s. Nullable, indexed, no backfill — plain
 * bigints (no FKs) because the referenced tables live in migration
 * subdirectories whose presence varies across environments.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'products' => ['reseller_id', 'distributor_id'],
        'orders' => ['reseller_id', 'distributor_id', 'b2b_account_id'],
        'rfq_requests' => ['b2b_account_id'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($tableName, $column)) {
                        $table->unsignedBigInteger($column)->nullable()->index();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
