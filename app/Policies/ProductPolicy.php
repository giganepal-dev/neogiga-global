<?php

namespace NeoGiga\Policies;

use NeoGiga\Models\User;
use NeoGiga\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user can view any products.
     */
    public function viewAny(User $user): bool
    {
        // Public catalog is visible, but admin/seller views require permission
        return true;
    }

    /**
     * Determine if the given user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        // If product is published, anyone can view
        if ($product->is_published) {
            return true;
        }

        // Owners and admins can view drafts
        return $user->can('view_drafts') || 
               $product->seller_id === $user->seller_id ||
               $user->hasRole(['super_admin', 'global_admin', 'country_admin']);
    }

    /**
     * Determine if the given user can create products.
     */
    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    /**
     * Determine if the given user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        // Only owners, admins, or specific roles can update
        if ($user->hasRole(['super_admin', 'global_admin', 'product_manager'])) {
            return true;
        }

        // Sellers can only update their own products
        if ($user->seller_id && $product->seller_id === $user->seller_id) {
            return $user->can('products.update');
        }

        // Manufacturers can update their own master products
        if ($user->manufacturer_id && $product->manufacturer_id === $user->manufacturer_id) {
            return $user->can('products.update');
        }

        return false;
    }

    /**
     * Determine if the given user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        // Soft delete only allowed for owners and admins
        return $user->hasRole(['super_admin', 'global_admin']) || 
               ($user->seller_id === $product->seller_id && $user->can('products.delete'));
    }

    /**
     * Determine if the given user can restore the product.
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'product_manager']);
    }

    /**
     * Determine if the given user can permanently delete the product.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine if the user can publish the product to a country.
     */
    public function publish(User $user, Product $product): bool
    {
        return $user->can('products.publish') && 
               ($user->hasRole(['super_admin', 'global_admin', 'country_admin', 'product_manager']) ||
                ($user->seller_id === $product->seller_id && $product->approval_status === 'approved'));
    }

    /**
     * Determine if the user can approve the product.
     */
    public function approve(User $user, Product $product): bool
    {
        return $user->can('products.approve') && 
               $user->hasRole(['super_admin', 'global_admin', 'product_manager']);
    }
}
