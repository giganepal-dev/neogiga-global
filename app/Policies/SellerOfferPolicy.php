<?php

namespace NeoGiga\Policies;

use NeoGiga\Models\User;
use NeoGiga\Models\SellerOffer;
use Illuminate\Auth\Access\HandlesAuthorization;

class SellerOfferPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user can view any seller offers.
     */
    public function viewAny(User $user): bool
    {
        return true; // Public marketplace offers are visible
    }

    /**
     * Determine if the given user can view the seller offer.
     */
    public function view(User $user, SellerOffer $offer): bool
    {
        // Published offers are visible to all
        if ($offer->is_active) {
            return true;
        }

        // Owners and admins can view inactive offers
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) ||
               ($user->seller_id && $offer->seller_id === $user->seller_id);
    }

    /**
     * Determine if the given user can create seller offers.
     */
    public function create(User $user): bool
    {
        return $user->can('offers.create') &&
               $user->seller_id !== null;
    }

    /**
     * Determine if the given user can update the seller offer.
     */
    public function update(User $user, SellerOffer $offer): bool
    {
        // Admins can update any offer
        if ($user->hasRole(['super_admin', 'global_admin', 'product_manager'])) {
            return true;
        }

        // Sellers can only update their own offers
        return $user->seller_id && $offer->seller_id === $user->seller_id &&
               $user->can('offers.update');
    }

    /**
     * Determine if the given user can delete the seller offer.
     */
    public function delete(User $user, SellerOffer $offer): bool
    {
        return $user->hasRole(['super_admin', 'global_admin']) ||
               ($user->seller_id === $offer->seller_id && $user->can('offers.delete'));
    }

    /**
     * Determine if the given user can restore the seller offer.
     */
    public function restore(User $user, SellerOffer $offer): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'product_manager']);
    }

    /**
     * Determine if the given user can permanently delete the seller offer.
     */
    public function forceDelete(User $user, SellerOffer $offer): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine if the user can activate the offer.
     */
    public function activate(User $user, SellerOffer $offer): bool
    {
        return $user->can('offers.activate') &&
               ($user->hasRole(['super_admin', 'global_admin', 'country_admin']) ||
                ($user->seller_id === $offer->seller_id && $offer->approval_status === 'approved'));
    }

    /**
     * Determine if the user can approve the offer.
     */
    public function approve(User $user, SellerOffer $offer): bool
    {
        return $user->can('offers.approve') &&
               $user->hasRole(['super_admin', 'global_admin', 'country_admin', 'product_manager']);
    }

    /**
     * Determine if the user can set pricing for the offer.
     */
    public function setPricing(User $user, SellerOffer $offer): bool
    {
        return $user->can('offers.pricing') &&
               ($user->hasRole(['super_admin', 'global_admin']) ||
                $user->seller_id === $offer->seller_id);
    }
}
