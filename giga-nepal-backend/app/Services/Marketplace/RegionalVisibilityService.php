<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RegionalVisibilityService
{
    public function __construct(private readonly RegionalCommercePolicyService $policy)
    {
    }

    public function marketplacePrice(int $productId, ?Marketplace $marketplace): ?object
    {
        if (! Schema::hasTable('marketplace_product_prices')) {
            return null;
        }

        $query = DB::table('marketplace_product_prices as p')
            ->leftJoin('marketplaces as m', 'm.id', '=', 'p.marketplace_id')
            ->leftJoin('currencies as c', 'c.code', '=', 'p.currency_code')
            ->where('p.product_id', $productId)
            ->where('p.is_active', true)
            ->where(function ($q) {
                $q->whereNull('p.sale_start_date')->orWhere('p.sale_start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('p.sale_end_date')->orWhere('p.sale_end_date', '>=', now());
            });

        if ($marketplace?->id) {
            $query->where('p.marketplace_id', $marketplace->id);
        }

        return $query
            ->select('p.*', 'm.name as marketplace_name', 'c.symbol as currency_symbol', 'c.native_symbol as currency_native_symbol')
            ->orderByRaw('coalesce(p.sale_price, p.base_price) asc')
            ->first() ?: null;
    }

    public function sellerOffers(int $productId, ?Marketplace $marketplace): Collection
    {
        if (! Schema::hasTable('vendor_product_prices')) {
            return collect();
        }

        $query = DB::table('vendor_product_prices as price')
            ->join('vendors as v', 'v.id', '=', 'price.vendor_id')
            ->leftJoin('vendor_products as vp', function ($join) use ($productId) {
                $join->on('vp.vendor_id', '=', 'price.vendor_id')
                    ->where('vp.product_id', '=', $productId);
            })
            ->leftJoin('vendor_marketplace_approvals as approval', function ($join) use ($marketplace) {
                $join->on('approval.vendor_id', '=', 'price.vendor_id');
                if ($marketplace?->id) {
                    $join->where('approval.marketplace_id', '=', $marketplace->id);
                }
            })
            ->leftJoin('marketplaces as m', 'm.id', '=', 'vp.marketplace_id')
            ->leftJoin('currencies as c', 'c.code', '=', 'price.currency_code')
            ->where('price.product_id', $productId)
            ->where('price.is_active', true)
            ->whereIn('v.status', ['active', 'approved']);

        if ($marketplace?->id && ! $this->policy->allowsGlobalFallback($marketplace->code)) {
            $query->where(function ($q) use ($marketplace) {
                $q->where('vp.marketplace_id', $marketplace->id)
                    ->orWhere(function ($inner) use ($marketplace) {
                        $inner->whereNull('vp.marketplace_id')
                            ->where('approval.marketplace_id', $marketplace->id)
                            ->where('approval.status', 'approved');
                    });
            });
        } elseif ($marketplace?->id) {
            $query->where(function ($q) use ($marketplace) {
                $q->where('vp.marketplace_id', $marketplace->id)->orWhereNull('vp.marketplace_id');
            });
        }

        return $query
            ->select([
                'price.*',
                'v.name as vendor_name',
                'v.slug as vendor_slug',
                'v.status as vendor_status',
                'v.is_verified',
                'vp.marketplace_id',
                'vp.status as vendor_product_status',
                'approval.status as marketplace_approval_status',
                'm.name as marketplace_name',
                'c.symbol as currency_symbol',
                'c.native_symbol as currency_native_symbol',
            ])
            ->orderBy('price.selling_price')
            ->limit(8)
            ->get();
    }

    public function stockRows(int $productId, ?Marketplace $marketplace): Collection
    {
        if (! Schema::hasTable('inventory_stocks')) {
            return collect();
        }

        $query = DB::table('inventory_stocks as s')
            ->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->leftJoin('countries as c', 'c.id', '=', 'w.country_id')
            ->where('s.product_id', $productId)
            ->where('s.is_active', true);

        if ($marketplace?->id && ! $this->policy->allowsGlobalFallback($marketplace->code)) {
            $query->where(function ($q) use ($marketplace) {
                $q->where('s.marketplace_id', $marketplace->id)
                    ->orWhere('w.marketplace_id', $marketplace->id);
            });
        } elseif ($marketplace?->id) {
            $query->where(function ($q) use ($marketplace) {
                $q->where('s.marketplace_id', $marketplace->id)
                    ->orWhere('w.marketplace_id', $marketplace->id)
                    ->orWhereNull('s.marketplace_id')
                    ->orWhereNull('w.marketplace_id');
            });
        }

        if ($marketplace?->country_id && ! $this->policy->allowsGlobalFallback($marketplace->code)) {
            $query->where('w.country_id', $marketplace->country_id);
        }

        $quoteOnly = Schema::hasColumn('inventory_stocks', 'quote_only') ? 's.quote_only' : 'false as quote_only';

        return $query
            ->select('s.*', 'w.country_id as country_id', 'w.name as warehouse_name', 'c.name as country_name')
            ->selectRaw($quoteOnly)
            ->orderByDesc('s.quantity_available')
            ->limit(12)
            ->get();
    }
}
