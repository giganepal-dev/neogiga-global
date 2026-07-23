<?php

namespace App\Policies;

use App\Models\SellerOffer;
use App\Models\User;

class SellerOfferPolicy
{
    /**
     * Determine if the user can view any offers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['seller', 'admin']);
    }

    /**
     * Determine if the user can view the offer.
     */
    public function view(User $user, SellerOffer $offer): bool
    {
        // Admins can view all offers
        if ($user->hasRole('admin')) {
            return true;
        }

        // Sellers can only view their own offers
        if ($user->hasRole('seller')) {
            return $offer->vendor->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can create offers.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['seller', 'admin']) && 
               $user->vendor && 
               $user->vendor->is_approved;
    }

    /**
     * Determine if the user can update the offer.
     */
    public function update(User $user, SellerOffer $offer): bool
    {
        // Admins can update any offer
        if ($user->hasRole('admin')) {
            return true;
        }

        // Sellers can only update their own offers and only if not locked
        if ($user->hasRole('seller')) {
            if ($offer->vendor->user_id !== $user->id) {
                return false;
            }

            // Cannot update if offer has active orders or is paused by admin
            if ($offer->status === 'paused' || $offer->hasActiveOrders()) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete the offer.
     */
    public function delete(User $user, SellerOffer $offer): bool
    {
        // Only admins can delete offers
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can approve the offer.
     */
    public function approve(User $user, SellerOffer $offer): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can pause/resume the offer.
     */
    public function pause(User $user, SellerOffer $offer): bool
    {
        // Admins can always pause
        if ($user->hasRole('admin')) {
            return true;
        }

        // Sellers can only pause their own offers
        if ($user->hasRole('seller')) {
            return $offer->vendor->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can duplicate the offer.
     */
    public function duplicate(User $user, SellerOffer $offer): bool
    {
        // Same rules as update
        return $this->update($user, $offer);
    }
}
