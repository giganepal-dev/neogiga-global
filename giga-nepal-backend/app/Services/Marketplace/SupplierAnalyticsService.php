<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\Vendor;
use Illuminate\Support\Facades\DB;

class SupplierAnalyticsService
{
    /**
     * Get comprehensive supplier performance metrics.
     */
    public function getPerformanceMetrics(int $vendorId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $products = Product::where('vendor_id', $vendorId);
        $totalProducts = $products->count();
        $activeProducts = $products->where('status', 'approved')->count();
        $pendingProducts = $products->where('status', 'pending')->count();

        $totalViews = (clone $products)->sum('view_count');
        $avgRating = (clone $products)->where('rating_avg', '>', 0)->avg('rating_avg');
        $ratingCount = (clone $products)->where('rating_count', '>', 0)->sum('rating_count');

        // Order metrics
        $orderCount = 0;
        $orderRevenue = 0;
        $avgOrderValue = 0;
        if (DB::getSchemaBuilder()->hasTable('order_items')) {
            $orderCount = DB::table('order_items')
                ->where('vendor_id', $vendorId)
                ->where('created_at', '>=', $since)
                ->count();
            $orderRevenue = DB::table('order_items')
                ->where('vendor_id', $vendorId)
                ->where('created_at', '>=', $since)
                ->sum('price') ?? 0;
            $avgOrderValue = $orderCount > 0 ? $orderRevenue / $orderCount : 0;
        }

        // RFQ metrics
        $rfqCount = 0;
        $rfqQuoted = 0;
        if (DB::getSchemaBuilder()->hasTable('rfq_items')) {
            $rfqCount = DB::table('rfq_items')
                ->where('vendor_id', $vendorId)
                ->where('created_at', '>=', $since)
                ->count();
            $rfqQuoted = DB::table('rfq_items')
                ->where('vendor_id', $vendorId)
                ->where('status', 'quoted')
                ->where('created_at', '>=', $since)
                ->count();
        }

        // Support metrics
        $supportTickets = 0;
        $avgResponseTime = null;
        if (DB::getSchemaBuilder()->hasTable('support_tickets')) {
            $supportTickets = DB::table('support_tickets')
                ->where('vendor_id', $vendorId)
                ->where('created_at', '>=', $since)
                ->count();
        }

        // Conversion metrics
        $cartAdditions = 0;
        $cartConversion = 0;
        if (DB::getSchemaBuilder()->hasTable('add_to_cart_events')) {
            $cartAdditions = DB::table('add_to_cart_events')
                ->where('vendor_id', $vendorId)
                ->where('created_at', '>=', $since)
                ->count();
        }

        return [
            'period_days' => $days,
            'products' => [
                'total' => $totalProducts,
                'active' => $activeProducts,
                'pending' => $pendingProducts,
                'approval_rate' => $totalProducts > 0 ? round(($activeProducts / $totalProducts) * 100) : 0,
            ],
            'engagement' => [
                'total_views' => $totalViews,
                'avg_views_per_product' => $activeProducts > 0 ? round($totalViews / $activeProducts) : 0,
                'avg_rating' => $avgRating ? round($avgRating, 2) : null,
                'total_ratings' => $ratingCount,
            ],
            'sales' => [
                'order_count' => $orderCount,
                'order_revenue' => round($orderRevenue, 2),
                'avg_order_value' => round($avgOrderValue, 2),
            ],
            'rfq' => [
                'total_rfq' => $rfqCount,
                'quoted' => $rfqQuoted,
                'response_rate' => $rfqCount > 0 ? round(($rfqQuoted / $rfqCount) * 100) : 0,
            ],
            'support' => [
                'tickets' => $supportTickets,
                'avg_response_time_hours' => $avgResponseTime,
            ],
            'conversion' => [
                'cart_additions' => $cartAdditions,
                'cart_conversion_rate' => $cartConversion,
            ],
        ];
    }

    /**
     * Get product-level performance breakdown.
     */
    public function getProductPerformance(int $vendorId, int $limit = 20): array
    {
        return Product::where('vendor_id', $vendorId)
            ->select('id', 'name', 'mpn', 'sku', 'status', 'view_count', 'rating_avg', 'rating_count', 'created_at')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'mpn' => $p->mpn,
                'sku' => $p->sku,
                'status' => $p->status,
                'views' => $p->view_count,
                'rating' => $p->rating_avg ? round($p->rating_avg, 1) : null,
                'rating_count' => $p->rating_count,
                'days_listed' => $p->created_at ? now()->diffInDays($p->created_at) : 0,
            ])
            ->toArray();
    }

    /**
     * Get engagement trends over time.
     */
    public function getEngagementTrends(int $vendorId, int $days = 30): array
    {
        if (!DB::getSchemaBuilder()->hasTable('product_search_events')) {
            return [];
        }

        return DB::table('product_search_events')
            ->where('clicked_product_id', function ($q) use ($vendorId) {
                $q->select('id')->from('products')->where('vendor_id', $vendorId);
            })
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as views'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get customer engagement metrics.
     */
    public function getCustomerEngagement(int $vendorId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $searchEvents = 0;
        if (DB::getSchemaBuilder()->hasTable('product_search_events')) {
            $searchEvents = DB::table('product_search_events')
                ->where('clicked_product_id', function ($q) use ($vendorId) {
                    $q->select('id')->from('products')->where('vendor_id', $vendorId);
                })
                ->where('created_at', '>=', $since)
                ->count();
        }

        $supportMessages = 0;
        if (DB::getSchemaBuilder()->hasTable('support_tickets')) {
            $supportMessages = DB::table('support_tickets')
                ->where('vendor_id', $vendorId)
                ->where('created_at', '>=', $since)
                ->count();
        }

        $rfqSubmissions = 0;
        if (DB::getSchemaBuilder()->hasTable('rfq_items')) {
            $rfqSubmissions = DB::table('rfq_items')
                ->where('vendor_id', $vendorId)
                ->where('created_at', '>=', $since)
                ->count();
        }

        return [
            'search_views' => $searchEvents,
            'support_tickets' => $supportMessages,
            'rfq_submissions' => $rfqSubmissions,
            'total_touchpoints' => $searchEvents + $supportMessages + $rfqSubmissions,
        ];
    }

    /**
     * Get top performing products by category.
     */
    public function getTopByCategory(int $vendorId, int $limit = 10): array
    {
        return Product::where('vendor_id', $vendorId)
            ->where('status', 'approved')
            ->select('category_id', DB::raw('count(*) as product_count'), DB::raw('sum(view_count) as total_views'), DB::raw('avg(rating_avg) as avg_rating'))
            ->groupBy('category_id')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get inventory health metrics.
     */
    public function getInventoryHealth(int $vendorId): array
    {
        $products = Product::where('vendor_id', $vendorId)->where('status', 'approved');

        $total = $products->count();
        $withStock = 0;
        $lowStock = 0;
        $outOfStock = 0;

        if (DB::getSchemaBuilder()->hasTable('marketplace_product_prices')) {
            $withStock = DB::table('marketplace_product_prices')
                ->whereIn('product_id', fn ($q) => $q->select('id')->from('products')->where('vendor_id', $vendorId))
                ->where('stock_quantity', '>', 0)
                ->distinct('product_id')
                ->count('product_id');

            $outOfStock = DB::table('marketplace_product_prices')
                ->whereIn('product_id', fn ($q) => $q->select('id')->from('products')->where('vendor_id', $vendorId))
                ->where('stock_quantity', '<=', 0)
                ->distinct('product_id')
                ->count('product_id');
        }

        $lowStock = max(0, $total - $withStock - $outOfStock);

        return [
            'total_active' => $total,
            'in_stock' => $withStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'stock_coverage' => $total > 0 ? round(($withStock / $total) * 100) : 0,
        ];
    }
}
