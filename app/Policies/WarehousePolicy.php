<?php

namespace NeoGiga\Policies;

use NeoGiga\Models\User;
use NeoGiga\Models\Warehouse;
use Illuminate\Auth\Access\HandlesAuthorization;

class WarehousePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user can view any warehouses.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('warehouses.view_any');
    }

    /**
     * Determine if the given user can view the warehouse.
     */
    public function view(User $user, Warehouse $warehouse): bool
    {
        // Admins and managers can view all
        if ($user->hasRole(['super_admin', 'global_admin', 'country_admin'])) {
            return true;
        }

        // Warehouse managers can view their assigned warehouse
        if ($user->warehouse_id && $user->warehouse_id === $warehouse->id) {
            return true;
        }

        // Regional admins can view warehouses in their region
        if ($user->hasRole(['regional_admin']) && $user->region_id === $warehouse->region_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given user can create warehouses.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'global_admin']) &&
               $user->can('warehouses.create');
    }

    /**
     * Determine if the given user can update the warehouse.
     */
    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('warehouses.update');
    }

    /**
     * Determine if the given user can delete the warehouse.
     */
    public function delete(User $user, Warehouse $warehouse): bool
    {
        // Only super and global admins can delete warehouses
        return $user->hasRole(['super_admin', 'global_admin']) &&
               $user->can('warehouses.delete');
    }

    /**
     * Determine if the given user can manage warehouse staff.
     */
    public function manageStaff(User $user, Warehouse $warehouse): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('warehouses.manage_staff');
    }

    /**
     * Determine if the given user can perform stock operations.
     */
    public function manageStock(User $user, Warehouse $warehouse): bool
    {
        // Warehouse managers can manage stock in their warehouse
        if ($user->warehouse_id === $warehouse->id && 
            $user->hasRole(['warehouse_manager'])) {
            return $user->can('warehouses.manage_stock');
        }

        // Admins can manage stock in any warehouse
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('warehouses.manage_stock');
    }

    /**
     * Determine if the given user can receive goods.
     */
    public function receiveGoods(User $user, Warehouse $warehouse): bool
    {
        return ($user->warehouse_id === $warehouse->id &&
                $user->hasRole(['warehouse_manager', 'warehouse_staff'])) ||
               $user->hasRole(['super_admin', 'global_admin', 'country_admin']);
    }

    /**
     * Determine if the given user can ship orders.
     */
    public function shipOrders(User $user, Warehouse $warehouse): bool
    {
        return ($user->warehouse_id === $warehouse->id &&
                $user->hasRole(['warehouse_manager', 'warehouse_staff'])) ||
               $user->hasRole(['super_admin', 'global_admin', 'country_admin']);
    }

    /**
     * Determine if the given user can transfer stock between warehouses.
     */
    public function transferStock(User $user, Warehouse $warehouse): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin', 'warehouse_manager']) &&
               $user->can('warehouses.transfer_stock');
    }

    /**
     * Determine if the given user can configure warehouse settings.
     */
    public function configure(User $user, Warehouse $warehouse): bool
    {
        return $user->hasRole(['super_admin', 'global_admin']) &&
               $user->can('warehouses.configure');
    }

    /**
     * Determine if the user can view warehouse analytics.
     */
    public function viewAnalytics(User $user, Warehouse $warehouse): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin', 'finance_manager']) ||
               ($user->warehouse_id === $warehouse->id &&
                $user->hasRole(['warehouse_manager']));
    }
}
