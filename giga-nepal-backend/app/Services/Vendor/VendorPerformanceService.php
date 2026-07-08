<?php

namespace App\Services\Vendor;

use App\Models\Marketplace\Vendor;
use App\Services\Seller\SellerDashboardService;

class VendorPerformanceService
{
    public function __construct(private readonly SellerDashboardService $dashboard)
    {
    }

    public function summary(Vendor $vendor): array
    {
        $orders = $this->dashboard->orderSummary($vendor);
        $products = $this->dashboard->productSummary($vendor);

        return [
            'performance_score' => min(100, ($products['approved_products'] * 5) + ($orders['fulfilled_orders'] * 2)),
            'orders' => $orders,
            'products' => $products,
            'ratings_average' => (float) ($vendor->profile?->rating_average ?? 0),
        ];
    }
}
