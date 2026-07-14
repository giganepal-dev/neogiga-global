<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const REQUIRED_LENGTH = 80;

    public function up(): void
    {
        if (! Schema::hasTable('marketplace_product_prices')
            || ! Schema::hasColumn('marketplace_product_prices', 'source_review_status')) {
            return;
        }

        $currentLength = $this->currentLength();
        if ($currentLength === null || $currentLength >= self::REQUIRED_LENGTH) {
            return;
        }

        $longestValue = (int) (DB::table('marketplace_product_prices')
            ->selectRaw('MAX(CHAR_LENGTH(source_review_status)) AS max_length')
            ->value('max_length') ?? 0);
        if ($longestValue > self::REQUIRED_LENGTH) {
            throw new RuntimeException('Refusing to resize marketplace_product_prices.source_review_status because an existing value exceeds 80 characters.');
        }

        Schema::table('marketplace_product_prices', function (Blueprint $table): void {
            $table->string('source_review_status', self::REQUIRED_LENGTH)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Upgrade-only provenance: never narrow a review-status column.
    }

    private function currentLength(): ?int
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return null;
        }

        if ($driver === 'pgsql') {
            $column = DB::selectOne(
                'select character_maximum_length from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ?',
                ['marketplace_product_prices', 'source_review_status']
            );
        } elseif ($driver === 'mysql') {
            $column = DB::selectOne(
                'select character_maximum_length from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?',
                ['marketplace_product_prices', 'source_review_status']
            );
        } else {
            throw new RuntimeException("Unsupported catalog release database driver {$driver}.");
        }

        return $column?->character_maximum_length === null
            ? null
            : (int) $column->character_maximum_length;
    }
};
