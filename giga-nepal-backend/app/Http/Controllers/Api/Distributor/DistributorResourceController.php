<?php

namespace App\Http\Controllers\Api\Distributor;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Distributor\DistributorLeadStoreRequest;
use App\Models\Distributor\DistributorLead;
use App\Services\Distributor\DistributorContextService;
use App\Services\Distributor\DistributorTerritoryStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DistributorResourceController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly DistributorContextService $context,
        private readonly DistributorTerritoryStockService $territoryStock
    ) {}

    public function profile(Request $request): JsonResponse
    {
        return $this->success($this->context->abortUnlessDistributor($request->user())->load(['profile', 'territories']));
    }

    public function territories(Request $request): JsonResponse
    {
        $distributor = $this->context->abortUnlessDistributor($request->user());
        return $this->success(DB::table('distributor_territories')->where('distributor_id', $distributor->id)->get());
    }

    public function leads(Request $request): JsonResponse
    {
        $distributor = $this->context->abortUnlessDistributor($request->user());
        return $this->success(DistributorLead::where('distributor_id', $distributor->id)->latest()->paginate(25));
    }

    public function storeLead(DistributorLeadStoreRequest $request): JsonResponse
    {
        $distributor = $this->context->abortUnlessDistributor($request->user());
        $lead = DistributorLead::create([...$request->validated(), 'distributor_id' => $distributor->id, 'status' => 'new']);
        return $this->success($lead, 201);
    }

    public function table(Request $request, string $table): JsonResponse
    {
        $allowed = ['distributor_customers', 'distributor_orders', 'distributor_commissions', 'distributor_payouts', 'distributor_downlines'];
        abort_unless(in_array($table, $allowed, true), 404);
        if (! Schema::hasTable($table)) {
            return $this->error('Distributor foundation migration is pending.', 503);
        }
        $distributor = $this->context->abortUnlessDistributor($request->user());
        $column = $table === 'distributor_downlines' ? 'parent_distributor_id' : 'distributor_id';
        return $this->success(DB::table($table)->where($column, $distributor->id)->latest('id')->paginate(25));
    }

    public function territoryProducts(Request $request): JsonResponse
    {
        return $this->success($this->territoryStock->products($this->context->abortUnlessDistributor($request->user())));
    }

    public function territoryVendors(Request $request): JsonResponse
    {
        return $this->success($this->territoryStock->vendors($this->context->abortUnlessDistributor($request->user())));
    }
}
