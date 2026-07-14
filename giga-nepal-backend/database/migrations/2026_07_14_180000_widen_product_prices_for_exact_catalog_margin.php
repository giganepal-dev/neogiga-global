<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PRICE_COLUMNS = [
        'base_price',
        'cost_price',
        'sale_price',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'base_price')) {
                $table->decimal('base_price', 15, 4)->default('0.0000')->change();
            }

            if (Schema::hasColumn('products', 'cost_price')) {
                $table->decimal('cost_price', 15, 4)->nullable()->change();
            }

            if (Schema::hasColumn('products', 'sale_price')) {
                $table->decimal('sale_price', 15, 4)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $columns = array_values(array_filter(
            self::PRICE_COLUMNS,
            static fn (string $column): bool => Schema::hasColumn('products', $column)
        ));

        if ($columns === []) {
            return;
        }

        DB::table('products')
            ->select(array_merge(['id'], $columns))
            ->orderBy('id')
            ->chunkById(1_000, function ($products) use ($columns): void {
                foreach ($products as $product) {
                    foreach ($columns as $column) {
                        $this->assertFitsLegacyDecimal($product->{$column}, $product->id, $column);
                    }
                }
            }, 'id');

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'base_price')) {
                $table->decimal('base_price', 12, 2)->default('0.00')->change();
            }

            if (Schema::hasColumn('products', 'cost_price')) {
                $table->decimal('cost_price', 12, 2)->nullable()->change();
            }

            if (Schema::hasColumn('products', 'sale_price')) {
                $table->decimal('sale_price', 12, 2)->nullable()->change();
            }
        });
    }

    private function assertFitsLegacyDecimal(mixed $value, mixed $productId, string $column): void
    {
        if ($value === null) {
            return;
        }

        $decimal = trim((string) $value);

        if (preg_match('/^[+-]?(\d+)(?:\.(\d+))?$/', $decimal, $matches) !== 1) {
            throw new RuntimeException(sprintf(
                'Refusing to narrow products.%s: product %s has an unrecognized decimal value.',
                $column,
                (string) $productId
            ));
        }

        $integerDigits = strlen(ltrim($matches[1], '0'));
        $fraction = $matches[2] ?? '';
        $nonZeroBeyondTwoDecimals = preg_match('/[1-9]/', substr($fraction, 2)) === 1;

        if ($integerDigits > 10 || $nonZeroBeyondTwoDecimals) {
            throw new RuntimeException(sprintf(
                'Refusing to narrow products.%s to DECIMAL(12,2): product %s has value %s.',
                $column,
                (string) $productId,
                $decimal
            ));
        }
    }
};
