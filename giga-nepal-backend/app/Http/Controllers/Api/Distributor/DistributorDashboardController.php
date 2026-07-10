<?php

namespace App\Http\Controllers\Api\Distributor;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Distributor\DistributorContextService;
use App\Services\Distributor\DistributorDashboardService;
use App\Services\Distributor\DistributorTerritoryStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistributorDashboardController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly DistributorContextService $context,
        private readonly DistributorDashboardService $dashboard,
        private readonly DistributorTerritoryStockService $territoryStock
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        return $this->success($this->dashboard->overview($this->context->abortUnlessDistributor($request->user())));
    }

    public function overview(Request $request): JsonResponse
    {
        $distributor = $this->context->abortUnlessDistributor($request->user());

        return $this->success([
            'dashboard' => $this->dashboard->overview($distributor),
            'territory_stock' => $this->territoryStock->stockSummary($distributor),
            'leads' => $this->territoryStock->leadsSummary($distributor),
            'customers' => $this->territoryStock->customersSummary($distributor),
        ]);
    }

    public function territoryStock(Request $request): JsonResponse
    {
        return $this->success($this->territoryStock->stockSummary($this->context->abortUnlessDistributor($request->user())));
    }

    public function leadsSummary(Request $request): JsonResponse
    {
        return $this->success($this->territoryStock->leadsSummary($this->context->abortUnlessDistributor($request->user())));
    }

    public function customerSummary(Request $request): JsonResponse
    {
        return $this->success($this->territoryStock->customersSummary($this->context->abortUnlessDistributor($request->user())));
    }
}
