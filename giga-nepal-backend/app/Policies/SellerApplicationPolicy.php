<?php

namespace App\Policies;

use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SellerApplicationPolicy
{
    /**
     * Determine whether the user can view any models.
     * Admin and marketplace managers can view all applications.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'marketplace_manager', 'seller_reviewer']);
    }

    /**
     * Determine whether the user can view the model.
     * Admin can view all, users can view their own.
     */
    public function view(User $user, SellerApplication $sellerApplication): bool
    {
        if ($user->hasRole(['admin', 'marketplace_manager', 'seller_reviewer'])) {
            return true;
        }
        
        return $user->id === $sellerApplication->user_id;
    }

    /**
     * Determine whether the user can create models.
     * Any authenticated user or guest (public form) can create.
     */
    public function create(User $user = null): bool
    {
        // Public form submission is allowed (user may be null)
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * Only admin and reviewers can update (approve/reject).
     */
    public function update(User $user, SellerApplication $sellerApplication): bool
    {
        return $user->hasRole(['admin', 'marketplace_manager', 'seller_reviewer']);
    }

    /**
     * Determine whether the user can delete the model.
     * Only admin can delete.
     */
    public function delete(User $user, SellerApplication $sellerApplication): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SellerApplication $sellerApplication): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SellerApplication $sellerApplication): bool
    {
        return $user->hasRole(['admin']);
    }
}
