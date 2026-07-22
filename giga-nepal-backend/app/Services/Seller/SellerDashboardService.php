<?php

namespace App\Services\Seller;

use App\Models\Marketplace\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SellerDashboardService
{
    public function overview(Vendor $vendor): array
    {
        return [
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'slug' => $vendor->slug,
                'status' => $vendor->status,
                'commerce_status' => Schema::hasColumn('vendors', 'commerce_status') ? $vendor->commerce_status : $vendor->status,
                'is_verified' => (bool) $vendor->is_verified,
                'operating_scope' => $vendor->operating_scope ?? 'country',
                'country_id' => $vendor->country_id,
            ],
            'onboarding' => $this->onboarding($vendor),
            'products' => $this->productSummary($vendor),
            'orders' => $this->orderSummary($vendor),
            'inventory' => $this->inventorySummary($vendor),
            'payouts' => $this->payoutSummary($vendor),
            'marketplace_approvals' => $this->marketplaceApprovals($vendor),
            'alerts' => $this->alerts($vendor),
        ];
    }

    public function onboarding(Vendor $vendor): array
    {
        return [
            'profile_created' => Schema::hasTable('vendor_profiles') && DB::table('vendor_profiles')->where('vendor_id', $vendor->id)->exists(),
            'has_marketplace_application' => DB::table('vendor_marketplace_approvals')->where('vendor_id', $vendor->id)->exists(),
            'has_warehouse' => Schema::hasTable('vendor_warehouses') && DB::table('vendor_warehouses')->where('vendor_id', $vendor->id)->exists(),
            'has_document' => Schema::hasTable('vendor_documents') && DB::table('vendor_documents')->where('vendor_id', $vendor->id)->exists(),
            'is_verified' => (bool) $vendor->is_verified,
        ];
    }

    public function productSummary(Vendor $vendor): array
    {
        return [
            'total_products' => DB::table('products')->where('vendor_id', $vendor->id)->count(),
            'approved_products' => DB::table('products')->where('vendor_id', $vendor->id)->where('status', 'approved')->count(),
            'pending_products' => DB::table('products')->where('vendor_id', $vendor->id)->whereIn('status', ['pending', 'pending_review'])->count(),
            'rejected_products' => DB::table('products')->where('vendor_id', $vendor->id)->where('status', 'rejected')->count(),
            'submitted_products' => Schema::hasTable('vendor_products') ? DB::table('vendor_products')->where('vendor_id', $vendor->id)->count() : 0,
        ];
    }

    public function orderSummary(Vendor $vendor): array
    {
        if (! Schema::hasTable('vendor_orders')) {
            return ['total_orders' => 0, 'pending_orders' => 0, 'fulfilled_orders' => 0, 'cancelled_orders' => 0, 'gross_sales' => 0, 'net_earnings' => 0];
        }

        $base = DB::table('vendor_orders')->where('vendor_id', $vendor->id);

        return [
            'total_orders' => (clone $base)->count(),
            'pending_orders' => (clone $base)->where('status', 'pending')->count(),
            'fulfilled_orders' => (clone $base)->whereIn('status', ['fulfilled', 'delivered', 'shipped'])->count(),
            'cancelled_orders' => (clone $base)->where('status', 'cancelled')->count(),
            'gross_sales' => (float) (clone $base)->sum('subtotal'),
            'net_earnings' => (float) (clone $base)->sum('vendor_net_total'),
        ];
    }

    public function inventorySummary(Vendor $vendor): array
    {
        if (! Schema::hasTable('vendor_inventory')) {
            return ['stock_rows' => 0, 'available_units' => 0, 'reserved_units' => 0, 'low_stock_items' => 0];
        }

        $base = DB::table('vendor_inventory')->where('vendor_id', $vendor->id);

        return [
            'stock_rows' => (clone $base)->count(),
            'available_units' => (int) (clone $base)->sum('quantity_available'),
            'reserved_units' => (int) (clone $base)->sum('quantity_reserved'),
            'low_stock_items' => 0,
        ];
    }

    public function payoutSummary(Vendor $vendor): array
    {
        if (! Schema::hasTable('vendor_payouts')) {
            return ['pending_payout' => 0, 'paid_payout' => 0, 'payout_count' => 0];
        }

        $base = DB::table('vendor_payouts')->where('vendor_id', $vendor->id);
        // Two vendor_payouts schemas exist (payments-abstraction: `amount`;
        // phase-b: `net_amount`) — whichever migration ran first wins, so
        // resolve the column instead of assuming phase-b.
        $amountColumn = Schema::hasColumn('vendor_payouts', 'net_amount') ? 'net_amount' : 'amount';

        return [
            'pending_payout' => (float) (clone $base)->whereIn('status', ['pending', 'approved'])->sum($amountColumn),
            'paid_payout' => (float) (clone $base)->where('status', 'paid')->sum($amountColumn),
            'payout_count' => (clone $base)->count(),
        ];
    }

    public function marketplaceApprovals(Vendor $vendor)
    {
        return DB::table('vendor_marketplace_approvals')
            ->leftJoin('marketplaces', 'marketplaces.id', '=', 'vendor_marketplace_approvals.marketplace_id')
            ->where('vendor_marketplace_approvals.vendor_id', $vendor->id)
            ->select('vendor_marketplace_approvals.id', 'vendor_marketplace_approvals.status', 'marketplaces.name as marketplace_name', 'marketplaces.code as marketplace_code')
            ->get();
    }

    public function alerts(Vendor $vendor): array
    {
        $alerts = [];
        if (! $vendor->is_verified) {
            $alerts[] = ['type' => 'verification', 'message' => 'Vendor verification is pending.'];
        }
        if (! DB::table('vendor_marketplace_approvals')->where('vendor_id', $vendor->id)->where('status', 'approved')->exists()) {
            $alerts[] = ['type' => 'marketplace', 'message' => 'No approved marketplace yet.'];
        }

        return $alerts;
    }
}
