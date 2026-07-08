<?php

namespace App\Services\Distributor;

use App\Models\Distributor\Distributor;
use App\Models\User;
use Illuminate\Http\Request;

class DistributorApprovalService
{
    public function __construct(private readonly DistributorActivityLogger $logger)
    {
    }

    public function approve(Distributor $distributor, ?User $user = null, ?Request $request = null): Distributor
    {
        $old = $distributor->only(['status', 'approved_by', 'approved_at']);
        $distributor->forceFill(['status' => 'approved', 'approved_by' => $user?->id, 'approved_at' => now(), 'rejection_reason' => null])->save();
        $this->logger->log($distributor, 'distributor.approved', $user, 'distributor', $distributor->id, $old, $distributor->fresh()->only(['status', 'approved_by', 'approved_at']), $request);

        return $distributor->fresh();
    }

    public function reject(Distributor $distributor, string $reason, ?User $user = null, ?Request $request = null): Distributor
    {
        $old = $distributor->only(['status', 'rejection_reason']);
        $distributor->forceFill(['status' => 'rejected', 'rejection_reason' => $reason])->save();
        $this->logger->log($distributor, 'distributor.rejected', $user, 'distributor', $distributor->id, $old, $distributor->fresh()->only(['status', 'rejection_reason']), $request);

        return $distributor->fresh();
    }

    public function suspend(Distributor $distributor, string $reason, ?User $user = null, ?Request $request = null): Distributor
    {
        $old = $distributor->only(['status', 'rejection_reason']);
        $distributor->forceFill(['status' => 'suspended', 'rejection_reason' => $reason])->save();
        $this->logger->log($distributor, 'distributor.suspended', $user, 'distributor', $distributor->id, $old, $distributor->fresh()->only(['status', 'rejection_reason']), $request);

        return $distributor->fresh();
    }
}
