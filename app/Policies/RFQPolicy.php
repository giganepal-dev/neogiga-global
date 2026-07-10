<?php

namespace NeoGiga\Policies;

use NeoGiga\Models\User;
use NeoGiga\Models\RFQ;
use Illuminate\Auth\Access\HandlesAuthorization;

class RFQPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user can view any RFQs.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('rfqs.view_any');
    }

    /**
     * Determine if the given user can view the RFQ.
     */
    public function view(User $user, RFQ $rfq): bool
    {
        // Admins and procurement managers can view all
        if ($user->hasRole(['super_admin', 'global_admin', 'country_admin'])) {
            return true;
        }

        // Buyers can view their own RFQs
        if ($rfq->buyer_id === $user->id) {
            return true;
        }

        // Sellers invited to quote can view the RFQ
        if ($user->seller_id && $rfq->invitedSellers->contains($user->seller_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given user can create RFQs.
     */
    public function create(User $user): bool
    {
        return $user->can('rfqs.create') &&
               ($user->hasRole(['procurement_buyer', 'corporate_buyer']) ||
                $user->hasRole(['super_admin', 'global_admin']));
    }

    /**
     * Determine if the given user can update the RFQ.
     */
    public function update(User $user, RFQ $rfq): bool
    {
        // Buyers can update their own RFQs before quotations are submitted
        if ($rfq->buyer_id === $user->id && $rfq->status === 'open') {
            return $user->can('rfqs.update');
        }

        // Admins can update any RFQ
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('rfqs.update');
    }

    /**
     * Determine if the given user can submit a quotation for the RFQ.
     */
    public function submitQuotation(User $user, RFQ $rfq): bool
    {
        // Only invited sellers can submit quotations
        if (!$user->seller_id || !$rfq->invitedSellers->contains($user->seller_id)) {
            return false;
        }

        return $user->can('rfqs.submit_quotation') &&
               $rfq->status === 'open' &&
               !$rfq->quotations->where('seller_id', $user->seller_id)->first();
    }

    /**
     * Determine if the given user can close the RFQ.
     */
    public function close(User $user, RFQ $rfq): bool
    {
        // Buyers can close their own RFQs
        if ($rfq->buyer_id === $user->id) {
            return $user->can('rfqs.close');
        }

        // Admins can close any RFQ
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('rfqs.close');
    }

    /**
     * Determine if the given user can accept a quotation.
     */
    public function acceptQuotation(User $user, RFQ $rfq): bool
    {
        // Only the buyer can accept a quotation
        return $rfq->buyer_id === $user->id &&
               $user->can('rfqs.accept_quotation') &&
               $rfq->status === 'quotation_received';
    }

    /**
     * Determine if the given user can delete the RFQ.
     */
    public function delete(User $user, RFQ $rfq): bool
    {
        // Buyers can delete their own RFQs if no quotations received
        if ($rfq->buyer_id === $user->id && $rfq->quotations->count() === 0) {
            return $user->can('rfqs.delete');
        }

        // Admins can delete any RFQ
        return $user->hasRole(['super_admin', 'global_admin']) &&
               $user->can('rfqs.delete');
    }

    /**
     * Determine if the given user can invite sellers to the RFQ.
     */
    public function inviteSellers(User $user, RFQ $rfq): bool
    {
        // Buyers can invite sellers to their own RFQs
        if ($rfq->buyer_id === $user->id && $rfq->status === 'open') {
            return $user->can('rfqs.invite_sellers');
        }

        // Admins can invite sellers to any RFQ
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('rfqs.invite_sellers');
    }

    /**
     * Determine if the given user can negotiate on the RFQ.
     */
    public function negotiate(User $user, RFQ $rfq): bool
    {
        // Buyers and invited sellers can negotiate
        if ($rfq->buyer_id === $user->id) {
            return $user->can('rfqs.negotiate');
        }

        if ($user->seller_id && $rfq->invitedSellers->contains($user->seller_id)) {
            return $user->can('rfqs.negotiate');
        }

        return false;
    }

    /**
     * Determine if the given user can convert RFQ to purchase order.
     */
    public function convertToPO(User $user, RFQ $rfq): bool
    {
        return ($rfq->buyer_id === $user->id ||
                $user->hasRole(['super_admin', 'global_admin', 'procurement_buyer'])) &&
               $user->can('rfqs.convert_to_po') &&
               $rfq->status === 'accepted';
    }

    /**
     * Determine if the given user can view pricing details.
     */
    public function viewPricing(User $user, RFQ $rfq): bool
    {
        // Buyers can see all pricing for their RFQs
        if ($rfq->buyer_id === $user->id) {
            return true;
        }

        // Sellers can only see their own quoted pricing
        if ($user->seller_id && $rfq->invitedSellers->contains($user->seller_id)) {
            return true;
        }

        // Admins and finance managers can see all
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']);
    }
}
