<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Seller\SellerContextService;
use App\Services\Seller\SellerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerDashboardController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly SellerContextService $context, private readonly SellerDashboardService $dashboard)
    {
    }

    public function dashboard(Request $request): JsonResponse
    {
        return $this->overview($request);
    }

    public function overview(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        return $this->success($this->dashboard->overview($vendor));
    }

    public function salesSummary(Request $request): JsonResponse
    {
        return $this->success($this->dashboard->orderSummary($this->context->abortUnlessVendor($request->user())));
    }

    public function orderSummary(Request $request): JsonResponse
    {
        return $this->salesSummary($request);
    }

    public function productSummary(Request $request): JsonResponse
    {
        return $this->success($this->dashboard->productSummary($this->context->abortUnlessVendor($request->user())));
    }

    public function inventorySummary(Request $request): JsonResponse
    {
        return $this->success($this->dashboard->inventorySummary($this->context->abortUnlessVendor($request->user())));
    }

    public function payoutSummary(Request $request): JsonResponse
    {
        return $this->success($this->dashboard->payoutSummary($this->context->abortUnlessVendor($request->user())));
    }

    public function alerts(Request $request): JsonResponse
    {
        return $this->success($this->dashboard->alerts($this->context->abortUnlessVendor($request->user())));
    }
}
