<?php

namespace NeoGiga\Policies;

use NeoGiga\Models\User;
use NeoGiga\Models\Settlement;
use Illuminate\Auth\Access\HandlesAuthorization;

class SettlementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user can view any settlements.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('settlements.view_any');
    }

    /**
     * Determine if the given user can view the settlement.
     */
    public function view(User $user, Settlement $settlement): bool
    {
        // Finance managers and admins can view all
        if ($user->hasRole(['super_admin', 'global_admin', 'finance_manager'])) {
            return true;
        }

        // Sellers can view their own settlements
        if ($user->seller_id && $settlement->seller_id === $user->seller_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given user can create settlements.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) &&
               $user->can('settlements.create');
    }

    /**
     * Determine if the given user can update the settlement.
     */
    public function update(User $user, Settlement $settlement): bool
    {
        // Only finance managers and admins can update (rarely needed)
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) &&
               $user->can('settlements.update');
    }

    /**
     * Determine if the given user can approve the settlement.
     */
    public function approve(User $user, Settlement $settlement): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) &&
               $user->can('settlements.approve') &&
               $settlement->status === 'pending';
    }

    /**
     * Determine if the given user can process payout.
     */
    public function processPayout(User $user, Settlement $settlement): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) &&
               $user->can('settlements.process_payout') &&
               $settlement->status === 'approved';
    }

    /**
     * Determine if the given user can cancel the settlement.
     */
    public function cancel(User $user, Settlement $settlement): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) &&
               $user->can('settlements.cancel') &&
               in_array($settlement->status, ['pending', 'approved']);
    }

    /**
     * Determine if the given user can delete the settlement.
     */
    public function delete(User $user, Settlement $settlement): bool
    {
        // Settlements should almost never be deleted
        return $user->hasRole(['super_admin']) &&
               $user->can('settlements.delete');
    }

    /**
     * Determine if the user can export settlements.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) &&
               $user->can('settlements.export');
    }

    /**
     * Determine if the user can dispute a settlement.
     */
    public function dispute(User $user, Settlement $settlement): bool
    {
        // Sellers can dispute their own settlements
        if ($user->seller_id && $settlement->seller_id === $user->seller_id) {
            return $user->can('settlements.dispute') &&
                   in_array($settlement->status, ['approved', 'paid']);
        }

        // Admins can dispute any
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) &&
               $user->can('settlements.dispute');
    }

    /**
     * Determine if the user can adjust settlement amounts.
     */
    public function adjust(User $user, Settlement $settlement): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) &&
               $user->can('settlements.adjust') &&
               $settlement->status === 'pending';
    }

    /**
     * Determine if the user can view settlement details including breakdown.
     */
    public function viewDetails(User $user, Settlement $settlement): bool
    {
        return $this->view($user, $settlement);
    }

    /**
     * Determine if the user can request payout.
     */
    public function requestPayout(User $user, Settlement $settlement): bool
    {
        // Sellers can request payout for approved settlements
        return $user->seller_id && $settlement->seller_id === $user->seller_id &&
               $user->can('settlements.request_payout') &&
               $settlement->status === 'approved';
    }
}
