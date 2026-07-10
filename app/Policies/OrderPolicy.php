<?php

namespace NeoGiga\Policies;

use NeoGiga\Models\User;
use NeoGiga\Models\Order;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view_any');
    }

    /**
     * Determine if the given user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Admins can view all orders
        if ($user->hasRole(['super_admin', 'global_admin', 'country_admin', 'finance_manager'])) {
            return true;
        }

        // Buyers can view their own orders
        if ($order->customer_id === $user->id) {
            return true;
        }

        // Sellers can view orders containing their offers
        if ($user->seller_id && $order->items->contains(function ($item) use ($user) {
            return $item->seller_offer->seller_id === $user->seller_id;
        })) {
            return true;
        }

        // Warehouse managers can view orders for fulfillment
        if ($user->hasRole(['warehouse_manager']) && $order->warehouse_id === $user->warehouse_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given user can create orders.
     */
    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    /**
     * Determine if the given user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // Only admins and specific roles can update orders
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) ||
               ($user->hasRole(['warehouse_manager', 'finance_manager']) && 
                $user->can('orders.update'));
    }

    /**
     * Determine if the given user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        // Customers can cancel pending orders
        if ($order->customer_id === $user->id && in_array($order->status, ['pending', 'confirmed'])) {
            return $user->can('orders.cancel');
        }

        // Admins can cancel any order
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('orders.cancel');
    }

    /**
     * Determine if the given user can refund the order.
     */
    public function refund(User $user, Order $order): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) &&
               $user->can('orders.refund');
    }

    /**
     * Determine if the given user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // Orders should rarely be deleted, only by super admins
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine if the user can export orders.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) &&
               $user->can('orders.export');
    }

    /**
     * Determine if the user can fulfill the order.
     */
    public function fulfill(User $user, Order $order): bool
    {
        return ($user->hasRole(['warehouse_manager']) && 
                $order->warehouse_id === $user->warehouse_id) ||
               $user->hasRole(['super_admin', 'global_admin', 'country_admin']);
    }
}
