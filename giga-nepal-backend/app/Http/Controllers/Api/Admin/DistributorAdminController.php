<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Distributor\AdminAssignTerritoryRequest;
use App\Http\Requests\Admin\Distributor\AdminDistributorDecisionRequest;
use App\Models\Distributor\Distributor;
use App\Models\Distributor\DistributorCommission;
use App\Models\Distributor\DistributorTerritoryRequest;
use App\Services\Distributor\DistributorApprovalService;
use App\Services\Distributor\DistributorTerritoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DistributorAdminController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly DistributorApprovalService $approval,
        private readonly DistributorTerritoryService $territoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('distributors')) {
            return $this->error('Distributor foundation migration is pending.', 503);
        }

        return $this->success(Distributor::query()->latest()->paginate((int) $request->input('per_page', 25)));
    }

    public function show(int $distributor): JsonResponse
    {
        if (! Schema::hasTable('distributors')) {
            return $this->error('Distributor foundation migration is pending.', 503);
        }
        $distributor = Distributor::findOrFail($distributor);

        return $this->success($distributor->load(['profile', 'territories']));
    }

    public function approve(Request $request, int $distributor): JsonResponse
    {
        if (! Schema::hasTable('distributors')) {
            return $this->error('Distributor foundation migration is pending.', 503);
        }
        $distributor = Distributor::findOrFail($distributor);

        return $this->success($this->approval->approve($distributor, $request->user(), $request));
    }

    public function reject(AdminDistributorDecisionRequest $request, int $distributor): JsonResponse
    {
        if (! Schema::hasTable('distributors')) {
            return $this->error('Distributor foundation migration is pending.', 503);
        }
        $distributor = Distributor::findOrFail($distributor);

        return $this->success($this->approval->reject($distributor, $request->validated('reason') ?? 'Rejected by admin.', $request->user(), $request));
    }

    public function suspend(AdminDistributorDecisionRequest $request, int $distributor): JsonResponse
    {
        if (! Schema::hasTable('distributors')) {
            return $this->error('Distributor foundation migration is pending.', 503);
        }
        $distributor = Distributor::findOrFail($distributor);

        return $this->success($this->approval->suspend($distributor, $request->validated('reason') ?? 'Suspended by admin.', $request->user(), $request));
    }

    public function assignTerritory(AdminAssignTerritoryRequest $request, int $distributor): JsonResponse
    {
        if (! Schema::hasTable('distributors')) {
            return $this->error('Distributor foundation migration is pending.', 503);
        }
        if (! Schema::hasTable('distributor_territories')) {
            return $this->error('Distributor territory table is pending migration.', 503);
        }
        $distributor = Distributor::findOrFail($distributor);
        $territory = DB::table('distributor_territories')->insertGetId([...$request->validated(), 'distributor_id' => $distributor->id, 'created_at' => now(), 'updated_at' => now()]);

        return $this->success(['id' => $territory], 201);
    }

    public function commissions(): JsonResponse
    {
        if (! Schema::hasTable('distributor_commissions')) {
            return $this->error('Distributor commission table is pending migration.', 503);
        }

        return $this->success(DistributorCommission::query()->latest()->paginate(25));
    }

    public function approveCommission(Request $request, int $commission): JsonResponse
    {
        if (! Schema::hasTable('distributor_commissions')) {
            return $this->error('Distributor commission table is pending migration.', 503);
        }
        $commission = DistributorCommission::findOrFail($commission);
        $commission->forceFill(['status' => 'approved', 'approved_at' => now()])->save();

        return $this->success($commission->fresh());
    }

    public function payouts(): JsonResponse
    {
        if (! Schema::hasTable('distributor_payouts')) {
            return $this->error('Distributor payout table is pending migration.', 503);
        }

        return $this->success(DB::table('distributor_payouts')->latest('id')->paginate(25));
    }

    public function markPayoutPaid(int $payout): JsonResponse
    {
        if (! Schema::hasTable('distributor_payouts')) {
            return $this->error('Distributor payout table is pending migration.', 503);
        }
        DB::table('distributor_payouts')->where('id', $payout)->update(['status' => 'paid', 'paid_at' => now(), 'updated_at' => now()]);

        return $this->success(['id' => $payout, 'status' => 'paid']);
    }

    public function territoryRequests(): JsonResponse
    {
        if (! Schema::hasTable('distributor_territory_requests')) {
            return $this->error('Distributor territory request migration is pending.', 503);
        }

        return $this->success(DistributorTerritoryRequest::query()->with('distributor:id,name,email')->latest()->paginate(25));
    }

    public function approveTerritoryRequest(int $request): JsonResponse
    {
        if (! Schema::hasTable('distributor_territory_requests')) {
            return $this->error('Distributor territory request migration is pending.', 503);
        }

        $territoryRequest = DistributorTerritoryRequest::findOrFail($request);
        abort_if($territoryRequest->status !== 'pending', 422, 'Territory request is not pending.');
        $this->territoryService->approveRequest($territoryRequest);

        return $this->success($territoryRequest->fresh());
    }

    public function rejectTerritoryRequest(Request $request, int $territoryRequest): JsonResponse
    {
        if (! Schema::hasTable('distributor_territory_requests')) {
            return $this->error('Distributor territory request migration is pending.', 503);
        }

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $territoryRequest = DistributorTerritoryRequest::findOrFail($territoryRequest);
        abort_if($territoryRequest->status !== 'pending', 422, 'Territory request is not pending.');
        $this->territoryService->rejectRequest($territoryRequest, $validated['reason']);

        return $this->success($territoryRequest->fresh());
    }
}
