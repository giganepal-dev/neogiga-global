<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Marketplace\SupplierAnalyticsService;
use Illuminate\Http\Request;

class SupplierAnalyticsController extends Controller
{
    public function __construct(
        private SupplierAnalyticsService $analytics,
    ) {}

    /** GET /seller/analytics */
    public function index(Request $request)
    {
        $vendor = $request->user()->vendor;
        $vendorId = $vendor?->id;

        if (!$vendorId) {
            return view('seller.analytics.index', [
                'metrics' => null,
                'products' => [],
                'engagement' => null,
                'inventory' => null,
                'vendor' => null,
            ]);
        }

        $days = (int) $request->input('days', 30);
        $metrics = $this->analytics->getPerformanceMetrics($vendorId, $days);
        $products = $this->analytics->getProductPerformance($vendorId, 15);
        $engagement = $this->analytics->getCustomerEngagement($vendorId, $days);
        $inventory = $this->analytics->getInventoryHealth($vendorId);
        $trends = $this->analytics->getEngagementTrends($vendorId, $days);

        return view('seller.analytics.index', compact('metrics', 'products', 'engagement', 'inventory', 'trends', 'vendor'));
    }

    /** GET /seller/analytics/products */
    public function products(Request $request)
    {
        $vendor = $request->user()->vendor;
        $vendorId = $vendor?->id;

        if (!$vendorId) {
            return view('seller.analytics.products', ['products' => [], 'vendor' => null]);
        }

        $products = $this->analytics->getProductPerformance($vendorId, 50);
        $topByCategory = $this->analytics->getTopByCategory($vendorId);

        return view('seller.analytics.products', compact('products', 'topByCategory', 'vendor'));
    }

    /** GET /seller/analytics/engagement */
    public function engagement(Request $request)
    {
        $vendor = $request->user()->vendor;
        $vendorId = $vendor?->id;

        if (!$vendorId) {
            return view('seller.analytics.engagement', ['engagement' => null, 'trends' => [], 'vendor' => null]);
        }

        $days = (int) $request->input('days', 30);
        $engagement = $this->analytics->getCustomerEngagement($vendorId, $days);
        $trends = $this->analytics->getEngagementTrends($vendorId, $days);

        return view('seller.analytics.engagement', compact('engagement', 'trends', 'vendor'));
    }
}
