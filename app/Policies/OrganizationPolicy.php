<?php

namespace NeoGiga\Policies;

use NeoGiga\Models\User;
use NeoGiga\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user can view any organizations.
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all organizations
        if ($user->hasRole(['super_admin', 'global_admin'])) {
            return true;
        }

        // Users can view organizations they belong to
        return $user->organization_id !== null;
    }

    /**
     * Determine if the given user can view the organization.
     */
    public function view(User $user, Organization $organization): bool
    {
        // Super and global admins can view all
        if ($user->hasRole(['super_admin', 'global_admin'])) {
            return true;
        }

        // Country and regional admins can view orgs in their region
        if ($user->hasRole(['country_admin', 'regional_admin'])) {
            return $user->country_id === $organization->country_id ||
                   $user->region_id === $organization->region_id;
        }

        // Users can view their own organization
        return $user->organization_id === $organization->id;
    }

    /**
     * Determine if the given user can create organizations.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'global_admin']) &&
               $user->can('organizations.create');
    }

    /**
     * Determine if the given user can update the organization.
     */
    public function update(User $user, Organization $organization): bool
    {
        // Super and global admins can update any
        if ($user->hasRole(['super_admin', 'global_admin'])) {
            return $user->can('organizations.update');
        }

        // Organization owners can update their own
        if ($user->organization_id === $organization->id && 
            $user->hasRole(['owner', 'admin'])) {
            return $user->can('organizations.update');
        }

        return false;
    }

    /**
     * Determine if the given user can verify the organization.
     */
    public function verify(User $user, Organization $organization): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('organizations.verify');
    }

    /**
     * Determine if the given user can approve seller status.
     */
    public function approveSeller(User $user, Organization $organization): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('organizations.approve_seller');
    }

    /**
     * Determine if the given user can approve distributor status.
     */
    public function approveDistributor(User $user, Organization $organization): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('organizations.approve_distributor');
    }

    /**
     * Determine if the given user can approve manufacturer status.
     */
    public function approveManufacturer(User $user, Organization $organization): bool
    {
        return $user->hasRole(['super_admin', 'global_admin']) &&
               $user->can('organizations.approve_manufacturer');
    }

    /**
     * Determine if the given user can delete the organization.
     */
    public function delete(User $user, Organization $organization): bool
    {
        // Only super admins can delete organizations
        return $user->hasRole(['super_admin']) &&
               $user->can('organizations.delete');
    }

    /**
     * Determine if the given user can suspend the organization.
     */
    public function suspend(User $user, Organization $organization): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('organizations.suspend');
    }

    /**
     * Determine if the user can invite staff to the organization.
     */
    public function inviteStaff(User $user, Organization $organization): bool
    {
        // Organization admins and owners can invite
        if ($user->organization_id === $organization->id &&
            $user->hasRole(['owner', 'admin'])) {
            return $user->can('organizations.invite_staff');
        }

        // Global and country admins can also invite
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('organizations.invite_staff');
    }

    /**
     * Determine if the user can view financial data of the organization.
     */
    public function viewFinancials(User $user, Organization $organization): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'finance_manager']) ||
               ($user->organization_id === $organization->id &&
                $user->hasRole(['owner', 'admin', 'finance_manager']));
    }
}
