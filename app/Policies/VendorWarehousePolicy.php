<?php

namespace App\Policies;

use App\Models\VendorWarehouse;
use App\Models\User;

class VendorWarehousePolicy
{
    /**
     * Determine if the user can view any warehouses.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['seller', 'admin']);
    }

    /**
     * Determine if the user can view the warehouse.
     */
    public function view(User $user, VendorWarehouse $warehouse): bool
    {
        // Admins can view all warehouses
        if ($user->hasRole('admin')) {
            return true;
        }

        // Sellers can only view their own warehouses
        if ($user->hasRole('seller')) {
            return $warehouse->vendor->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can create warehouses.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['seller', 'admin']) && 
               $user->vendor;
    }

    /**
     * Determine if the user can update the warehouse.
     */
    public function update(User $user, VendorWarehouse $warehouse): bool
    {
        // Admins can update any warehouse
        if ($user->hasRole('admin')) {
            return true;
        }

        // Sellers can only update their own warehouses
        if ($user->hasRole('seller')) {
            return $warehouse->vendor->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can delete the warehouse.
     */
    public function delete(User $user, VendorWarehouse $warehouse): bool
    {
        // Only admins can delete warehouses
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can approve the warehouse.
     */
    public function approve(User $user, VendorWarehouse $warehouse): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can add stock to the warehouse.
     */
    public function addStock(User $user, VendorWarehouse $warehouse): bool
    {
        // Admins can always add stock
        if ($user->hasRole('admin')) {
            return true;
        }

        // Sellers can only add stock to their own verified warehouses
        if ($user->hasRole('seller')) {
            if ($warehouse->vendor->user_id !== $user->id) {
                return false;
            }

            return $warehouse->is_verified;
        }

        return false;
    }
}
