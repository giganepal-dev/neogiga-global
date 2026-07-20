<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_providers')) {
            return;
        }

        Schema::table('payment_providers', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_providers', 'marketplace_id')) {
                $table->unsignedBigInteger('marketplace_id')->nullable()->after('id')->index();
            }
        });

        Schema::table('payment_providers', function (Blueprint $table) {
            if (Schema::hasColumn('payment_providers', 'marketplace_id')) {
                $table->foreign('marketplace_id')
                    ->references('id')
                    ->on('marketplaces')
                    ->nullOnDelete();
            }
        });

        // Replace global-unique code with per-marketplace uniqueness.
        try {
            Schema::table('payment_providers', function (Blueprint $table) {
                $table->dropUnique(['code']);
            });
        } catch (Throwable) {
            // Index name may differ across environments.
        }

        Schema::table('payment_providers', function (Blueprint $table) {
            if (! $this->hasCompositeUnique('payment_providers', 'payment_providers_marketplace_id_code_unique')) {
                $table->unique(['marketplace_id', 'code']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_providers')) {
            return;
        }

        Schema::table('payment_providers', function (Blueprint $table) {
            try {
                $table->dropUnique(['marketplace_id', 'code']);
            } catch (Throwable) {
            }

            try {
                $table->dropForeign(['marketplace_id']);
            } catch (Throwable) {
            }

            if (Schema::hasColumn('payment_providers', 'marketplace_id')) {
                $table->dropColumn('marketplace_id');
            }

            try {
                $table->unique('code');
            } catch (Throwable) {
            }
        });
    }

    private function hasCompositeUnique(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if (($index['name'] ?? '') === $indexName) {
                return true;
            }
        }

        return false;
    }
};
