<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Seller\SellerContextService;
use App\Services\Vendor\VendorPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerPerformanceController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly SellerContextService $context, private readonly VendorPerformanceService $performance)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return $this->success($this->performance->summary($this->context->abortUnlessVendor($request->user())));
    }
}
