<?php

namespace NeoGiga\Policies;

use NeoGiga\Models\User;
use NeoGiga\Models\Inventory;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user can view any inventory.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view_any');
    }

    /**
     * Determine if the given user can view the inventory.
     */
    public function view(User $user, Inventory $inventory): bool
    {
        // Admins and managers can view all
        if ($user->hasRole(['super_admin', 'global_admin', 'country_admin', 'warehouse_manager'])) {
            return true;
        }

        // Sellers can view their own inventory
        if ($user->seller_id && $inventory->seller_id === $user->seller_id) {
            return true;
        }

        // Warehouse staff can view inventory in their warehouse
        if ($user->warehouse_id && $inventory->warehouse_id === $user->warehouse_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given user can create inventory.
     */
    public function create(User $user): bool
    {
        return $user->can('inventory.create') &&
               ($user->hasRole(['super_admin', 'global_admin', 'warehouse_manager']) ||
                $user->seller_id !== null);
    }

    /**
     * Determine if the given user can update the inventory.
     */
    public function update(User $user, Inventory $inventory): bool
    {
        // Admins and warehouse managers can update
        if ($user->hasRole(['super_admin', 'global_admin', 'country_admin', 'warehouse_manager'])) {
            return $user->can('inventory.update');
        }

        // Sellers can update their own inventory
        if ($user->seller_id && $inventory->seller_id === $user->seller_id) {
            return $user->can('inventory.update');
        }

        return false;
    }

    /**
     * Determine if the given user can adjust stock.
     */
    public function adjustStock(User $user, Inventory $inventory): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'warehouse_manager', 'finance_manager']) &&
               $user->can('inventory.adjust_stock');
    }

    /**
     * Determine if the given user can transfer stock.
     */
    public function transferStock(User $user, Inventory $inventory): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'warehouse_manager']) &&
               $user->can('inventory.transfer_stock');
    }

    /**
     * Determine if the given user can reserve stock.
     */
    public function reserveStock(User $user, Inventory $inventory): bool
    {
        // Warehouse managers and admins can reserve
        if ($user->hasRole(['super_admin', 'global_admin', 'warehouse_manager'])) {
            return $user->can('inventory.reserve_stock');
        }

        // Sellers can reserve their own stock
        if ($user->seller_id && $inventory->seller_id === $user->seller_id) {
            return $user->can('inventory.reserve_stock');
        }

        return false;
    }

    /**
     * Determine if the given user can delete inventory records.
     */
    public function delete(User $user, Inventory $inventory): bool
    {
        // Only admins should delete inventory (rare operation)
        return $user->hasRole(['super_admin', 'global_admin']) &&
               $user->can('inventory.delete');
    }

    /**
     * Determine if the user can perform cycle count.
     */
    public function cycleCount(User $user, Inventory $inventory): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'warehouse_manager']) &&
               $user->can('inventory.cycle_count');
    }

    /**
     * Determine if the user can reconcile inventory.
     */
    public function reconcile(User $user, Inventory $inventory): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'warehouse_manager', 'finance_manager']) &&
               $user->can('inventory.reconcile');
    }

    /**
     * Determine if the user can view inventory valuation.
     */
    public function viewValuation(User $user, Inventory $inventory): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) ||
               ($user->seller_id && $inventory->seller_id === $user->seller_id);
    }
}
