<?php

namespace App\Http\Controllers\Api\Distributor;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Distributor\DistributorContextService;
use App\Services\Distributor\DistributorDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistributorDashboardController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly DistributorContextService $context, private readonly DistributorDashboardService $dashboard) {}

    public function dashboard(Request $request): JsonResponse
    {
        return $this->success($this->dashboard->overview($this->context->abortUnlessDistributor($request->user())));
    }
}
