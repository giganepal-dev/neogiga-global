<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\ResellerApplication;
use App\Models\ResellerRfqAssignment;
use App\Models\ResellerTerritoryRequest;
use App\Services\Reseller\ResellerApplicationService;
use App\Services\Reseller\ResellerTerritoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ResellerAdminController extends Controller
{
    use ApiResponses;

    public function applications(): JsonResponse
    {
        if (! Schema::hasTable('reseller_applications')) {
            return $this->error('Reseller applications migration is pending.', 503);
        }

        return $this->success(ResellerApplication::latest()->paginate(25));
    }

    public function approveApplication(int $application, ResellerApplicationService $service): JsonResponse
    {
        $record = ResellerApplication::findOrFail($application);

        return $this->success($service->approve($record));
    }

    public function rejectApplication(Request $request, int $application): JsonResponse
    {
        $record = ResellerApplication::findOrFail($application);
        $record->forceFill([
            'status' => 'rejected',
            'metadata' => array_merge($record->metadata ?? [], ['reason' => $request->input('reason')]),
        ])->save();

        return $this->success($record->fresh());
    }

    public function approveTerritoryRequest(int $requestId, ResellerTerritoryService $service): JsonResponse
    {
        $record = ResellerTerritoryRequest::findOrFail($requestId);
        $service->approveRequest($record);

        return $this->success($record->fresh());
    }

    public function assignRfq(Request $request, int $rfq): JsonResponse
    {
        $data = $request->validate([
            'reseller_id' => ['required', 'integer', 'exists:resellers,id'],
            'deadline_at' => ['nullable', 'date'],
        ]);

        $assignment = ResellerRfqAssignment::updateOrCreate(
            ['rfq_id' => $rfq, 'reseller_id' => $data['reseller_id']],
            [
                'status' => 'invited',
                'invited_at' => now(),
                'deadline_at' => $data['deadline_at'] ?? null,
            ]
        );

        return $this->success($assignment, 201);
    }
}
