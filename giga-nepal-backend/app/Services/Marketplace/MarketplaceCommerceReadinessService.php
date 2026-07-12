<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketplaceCommerceReadinessService
{
    /**
     * Checkout requires real regional commercial data. This is read-only and
     * deliberately does not synthesize prices, tax, delivery, or stock.
     *
     * @return array{can_enable_checkout:bool,checks:list<array{key:string,label:string,passed:bool,count:int}>}
     */
    public function assess(Marketplace $marketplace): array
    {
        $checks = [
            $this->check('prices', 'Active regional product prices', $this->activePriceCount($marketplace)),
            $this->check('warehouses', 'Active marketplace warehouses', $this->activeWarehouseCount($marketplace)),
            $this->check('stock', 'Active regional stock rows', $this->activeStockCount($marketplace)),
            $this->check('tax', 'Active tax zones', $this->activeZoneCount('tax_zones', $marketplace)),
            $this->check('delivery', 'Active delivery zones', $this->activeZoneCount('delivery_zones', $marketplace)),
        ];

        return [
            'can_enable_checkout' => collect($checks)->every(fn (array $check) => $check['passed']),
            'checks' => $checks,
        ];
    }

    private function check(string $key, string $label, int $count): array
    {
        return ['key' => $key, 'label' => $label, 'passed' => $count > 0, 'count' => $count];
    }

    private function activePriceCount(Marketplace $marketplace): int
    {
        if (! Schema::hasTable('marketplace_product_prices')) {
            return 0;
        }

        return DB::table('marketplace_product_prices')
            ->where('marketplace_id', $marketplace->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('sale_start_date')->orWhere('sale_start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('sale_end_date')->orWhere('sale_end_date', '>=', now());
            })
            ->count();
    }

    private function activeWarehouseCount(Marketplace $marketplace): int
    {
        if (! Schema::hasTable('warehouses')) {
            return 0;
        }

        return DB::table('warehouses')
            ->where('marketplace_id', $marketplace->id)
            ->where('is_active', true)
            ->when($marketplace->country_id, fn ($query) => $query->where('country_id', $marketplace->country_id))
            ->count();
    }

    private function activeStockCount(Marketplace $marketplace): int
    {
        if (! Schema::hasTable('inventory_stocks') || ! Schema::hasTable('warehouses')) {
            return 0;
        }

        $countryExpression = Schema::hasColumn('inventory_stocks', 'country_id')
            ? 'COALESCE(s.country_id, w.country_id)'
            : 'w.country_id';

        return DB::table('inventory_stocks as s')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->where('s.is_active', true)
            ->where('w.is_active', true)
            ->where(function ($query) use ($marketplace) {
                $query->where('s.marketplace_id', $marketplace->id)
                    ->orWhere('w.marketplace_id', $marketplace->id);
            })
            ->when($marketplace->country_id, fn ($query) => $query->whereRaw("{$countryExpression} = ?", [$marketplace->country_id]))
            ->count();
    }

    private function activeZoneCount(string $table, Marketplace $marketplace): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)
            ->where('marketplace_id', $marketplace->id)
            ->where('is_active', true)
            ->where(function ($query) use ($marketplace) {
                $query->whereNull('country_id');
                if ($marketplace->country_id) {
                    $query->orWhere('country_id', $marketplace->country_id);
                }
            })
            ->count();
    }
}
