<?php

namespace App\Services\Distributor;

use App\Models\Distributor\Distributor;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DistributorTerritoryStockService
{
    public function stockSummary(Distributor $distributor): array
    {
        if (! Schema::hasTable('inventory_stocks')) {
            return $this->empty();
        }

        $query = $this->territoryStockQuery($distributor);
        $rows = $query->get();

        return [
            'territories' => $this->territories($distributor),
            'total_products' => $rows->pluck('product_id')->filter()->unique()->count(),
            'total_vendors' => $rows->pluck('vendor_id')->filter()->unique()->count(),
            'available_quantity' => (float) $rows->sum(fn ($row) => (float) ($row->quantity_available ?? 0)),
            'reserved_quantity' => (float) $rows->sum(fn ($row) => (float) ($row->quantity_reserved ?? 0)),
            'incoming_quantity' => (float) $rows->sum(fn ($row) => (float) ($row->quantity_incoming ?? 0)),
            'quote_only_products' => $rows->filter(fn ($row) => (bool) ($row->quote_only ?? false))->pluck('product_id')->filter()->unique()->count(),
            'low_stock_products' => $rows->filter(fn ($row) => (float) ($row->quantity_available ?? 0) <= (float) ($row->reorder_point ?? 0))->pluck('product_id')->filter()->unique()->count(),
        ];
    }

    public function products(Distributor $distributor): array
    {
        if (! Schema::hasTable('inventory_stocks') || ! Schema::hasTable('products')) {
            return [];
        }

        return $this->territoryStockQuery($distributor)
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.sku',
                'products.status',
                DB::raw('sum(inventory_stocks.quantity_available) as available_quantity'),
                DB::raw('sum(inventory_stocks.quantity_incoming) as incoming_quantity'),
                DB::raw('bool_or(coalesce(inventory_stocks.quote_only, false)) as quote_only'),
            ])
            ->groupBy('products.id', 'products.name', 'products.slug', 'products.sku', 'products.status')
            ->orderBy('products.name')
            ->limit(100)
            ->get()
            ->all();
    }

    public function vendors(Distributor $distributor): array
    {
        if (! Schema::hasTable('inventory_stocks') || ! Schema::hasTable('vendors')) {
            return [];
        }

        return $this->territoryStockQuery($distributor)
            ->join('vendors', 'vendors.id', '=', 'inventory_stocks.vendor_id')
            ->select([
                'vendors.id',
                'vendors.name',
                'vendors.slug',
                'vendors.status',
                'vendors.type',
                DB::raw('count(distinct inventory_stocks.product_id) as product_count'),
                DB::raw('sum(inventory_stocks.quantity_available) as available_quantity'),
            ])
            ->whereIn('vendors.status', ['active', 'pending'])
            ->groupBy('vendors.id', 'vendors.name', 'vendors.slug', 'vendors.status', 'vendors.type')
            ->orderBy('vendors.name')
            ->limit(100)
            ->get()
            ->all();
    }

    public function leadsSummary(Distributor $distributor): array
    {
        if (! Schema::hasTable('distributor_leads')) {
            return ['total' => 0, 'by_status' => []];
        }

        return [
            'total' => DB::table('distributor_leads')->where('distributor_id', $distributor->id)->count(),
            'by_status' => DB::table('distributor_leads')
                ->select('status', DB::raw('count(*) as total'))
                ->where('distributor_id', $distributor->id)
                ->groupBy('status')
                ->orderBy('status')
                ->get(),
        ];
    }

    public function customersSummary(Distributor $distributor): array
    {
        if (! Schema::hasTable('distributor_customers')) {
            return ['total' => 0, 'by_type' => []];
        }

        return [
            'total' => DB::table('distributor_customers')->where('distributor_id', $distributor->id)->count(),
            'by_type' => DB::table('distributor_customers')
                ->select('type', DB::raw('count(*) as total'))
                ->where('distributor_id', $distributor->id)
                ->groupBy('type')
                ->orderBy('type')
                ->get(),
        ];
    }

    private function territoryStockQuery(Distributor $distributor): Builder
    {
        $query = DB::table('inventory_stocks')
            ->where(function ($inner) {
                $inner->whereNull('inventory_stocks.status')->orWhere('inventory_stocks.status', 'active');
            });

        $territories = $this->territories($distributor);

        if ($territories === []) {
            $query->whereRaw('1 = 0');
            return $query;
        }

        $query->where(function ($outer) use ($territories) {
            foreach ($territories as $territory) {
                $outer->orWhere(function ($inner) use ($territory) {
                    foreach (['country_id', 'region_id', 'city_id'] as $column) {
                        if (! empty($territory[$column])) {
                            $inner->where('inventory_stocks.' . $column, $territory[$column]);
                        }
                    }
                });
            }
        });

        return $query;
    }

    private function territories(Distributor $distributor): array
    {
        if (! Schema::hasTable('distributor_territories')) {
            return [];
        }

        return DB::table('distributor_territories')
            ->where('distributor_id', $distributor->id)
            ->get(['country_id', 'region_id', 'city_id', 'territory_name', 'exclusive'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function empty(): array
    {
        return [
            'territories' => [],
            'total_products' => 0,
            'total_vendors' => 0,
            'available_quantity' => 0,
            'reserved_quantity' => 0,
            'incoming_quantity' => 0,
            'quote_only_products' => 0,
            'low_stock_products' => 0,
        ];
    }
}
