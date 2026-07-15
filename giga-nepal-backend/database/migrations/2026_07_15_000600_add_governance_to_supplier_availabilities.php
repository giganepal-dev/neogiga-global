<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_GENERATOR_POLICY = 'deterministic_80_percent_shenzhen_20_percent_rotated_regional_capped_by_observed_stock';

    private const LEGACY_GENERATOR_SOURCE = 'CDFER JLCPCB/LCSC in-stock SQLite';

    private const MANAGED_BY = 'jlcpcb_commerce_enrichment';

    public function up(): void
    {
        if (! Schema::hasTable('supplier_availabilities')) {
            return;
        }

        Schema::table('supplier_availabilities', function (Blueprint $table): void {
            if (! Schema::hasColumn('supplier_availabilities', 'managed_by')) {
                // Ownership is opt-in. Existing rows, and rows created by any
                // other workflow, must remain unclaimed until their writer
                // explicitly identifies itself.
                $table->string('managed_by', 120)->nullable();
            }
            if (! Schema::hasColumn('supplier_availabilities', 'is_manual_override')) {
                // NULL deliberately means "legacy/unknown", not writable.
                // The JLC enrichment writer supplies an explicit false only
                // for rows it creates itself.
                $table->boolean('is_manual_override')->nullable();
            }
            if (! Schema::hasColumn('supplier_availabilities', 'is_locked')) {
                $table->boolean('is_locked')->nullable();
            }
        });

        // Adopt only the complete, audited signature written by the prior JLC
        // generator. Every near-match, external row, partial governance state,
        // manual override and lock remains NULL/unclaimed and therefore
        // protected by the enrichment service.
        DB::table('supplier_availabilities')
            ->whereNull('managed_by')
            ->whereNull('is_manual_override')
            ->whereNull('is_locked')
            ->where('allocation_policy', self::LEGACY_GENERATOR_POLICY)
            ->where('source_name', self::LEGACY_GENERATOR_SOURCE)
            ->where('stock_type', 'supplier_virtual')
            ->where('quote_only', true)
            ->where('is_reservable', false)
            ->where('is_fulfillable', false)
            ->update([
                'managed_by' => self::MANAGED_BY,
                'is_manual_override' => false,
                'is_locked' => false,
            ]);
    }

    public function down(): void
    {
        // Upgrade-only migration: governance state protects operator edits
        // and must not be removed by automatic rollback.
    }
};
