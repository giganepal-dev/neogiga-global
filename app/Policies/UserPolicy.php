<?php

namespace NeoGiga\Policies;

use NeoGiga\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view_any');
    }

    /**
     * Determine if the given user can view another user.
     */
    public function view(User $user, User $targetUser): bool
    {
        // Users can view themselves
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Admins can view all users
        if ($user->hasRole(['super_admin', 'global_admin'])) {
            return true;
        }

        // Country admins can view users in their country
        if ($user->hasRole(['country_admin']) && 
            $user->country_id === $targetUser->country_id) {
            return true;
        }

        // Organization admins can view users in their organization
        if ($user->organization_id && 
            $user->organization_id === $targetUser->organization_id &&
            $user->hasRole(['owner', 'admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given user can create users.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('users.create');
    }

    /**
     * Determine if the given user can update another user.
     */
    public function update(User $user, User $targetUser): bool
    {
        // Users can update their own profile
        if ($user->id === $targetUser->id) {
            return $user->can('users.update_own');
        }

        // Super and global admins can update any user
        if ($user->hasRole(['super_admin', 'global_admin'])) {
            return $user->can('users.update');
        }

        // Country admins can update users in their country (except admins)
        if ($user->hasRole(['country_admin']) && 
            $user->country_id === $targetUser->country_id &&
            !$targetUser->hasRole(['super_admin', 'global_admin', 'country_admin'])) {
            return $user->can('users.update');
        }

        // Organization admins can update users in their org
        if ($user->organization_id && 
            $user->organization_id === $targetUser->organization_id &&
            $user->hasRole(['owner', 'admin']) &&
            !$targetUser->hasRole(['super_admin', 'global_admin'])) {
            return $user->can('users.update');
        }

        return false;
    }

    /**
     * Determine if the given user can delete another user.
     */
    public function delete(User $user, User $targetUser): bool
    {
        // Users cannot delete themselves
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Only super admins can delete users
        return $user->hasRole(['super_admin']) &&
               $user->can('users.delete');
    }

    /**
     * Determine if the given user can assign roles.
     */
    public function assignRole(User $user, User $targetUser): bool
    {
        // Super admins can assign any role
        if ($user->hasRole(['super_admin'])) {
            return $user->can('users.assign_role');
        }

        // Global admins can assign roles except super_admin
        if ($user->hasRole(['global_admin'])) {
            return $user->can('users.assign_role') &&
                   !$targetUser->hasRole(['super_admin']);
        }

        // Country admins can assign limited roles in their country
        if ($user->hasRole(['country_admin']) &&
            $user->country_id === $targetUser->country_id) {
            return $user->can('users.assign_role') &&
                   !$targetUser->hasRole(['super_admin', 'global_admin', 'country_admin']);
        }

        return false;
    }

    /**
     * Determine if the given user can suspend another user.
     */
    public function suspend(User $user, User $targetUser): bool
    {
        // Users cannot suspend themselves
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Super and global admins can suspend any user
        if ($user->hasRole(['super_admin', 'global_admin'])) {
            return $user->can('users.suspend');
        }

        // Country admins can suspend users in their country (except admins)
        if ($user->hasRole(['country_admin']) &&
            $user->country_id === $targetUser->country_id &&
            !$targetUser->hasRole(['super_admin', 'global_admin', 'country_admin'])) {
            return $user->can('users.suspend');
        }

        return false;
    }

    /**
     * Determine if the given user can impersonate another user.
     */
    public function impersonate(User $user, User $targetUser): bool
    {
        // Users cannot impersonate themselves
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Users cannot impersonate higher privilege users
        if ($targetUser->hasRole(['super_admin', 'global_admin'])) {
            return false;
        }

        // Super and global admins can impersonate
        return $user->hasRole(['super_admin', 'global_admin']) &&
               $user->can('users.impersonate');
    }

    /**
     * Determine if the given user can view login history.
     */
    public function viewLoginHistory(User $user, User $targetUser): bool
    {
        // Users can view their own login history
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Admins can view login history of users they can manage
        return $this->view($user, $targetUser) &&
               $user->hasRole(['super_admin', 'global_admin', 'country_admin']);
    }

    /**
     * Determine if the given user can manage sessions.
     */
    public function manageSessions(User $user, User $targetUser): bool
    {
        // Users can manage their own sessions
        if ($user->id === $targetUser->id) {
            return $user->can('users.manage_sessions');
        }

        // Admins can manage sessions of users they can manage
        return $this->view($user, $targetUser) &&
               $user->hasRole(['super_admin', 'global_admin', 'country_admin']) &&
               $user->can('users.manage_sessions');
    }

    /**
     * Determine if the given user can enable/disable 2FA.
     */
    public function manageTwoFactor(User $user, User $targetUser): bool
    {
        // Users can manage their own 2FA
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Admins can force disable 2FA for users they manage
        return $this->view($user, $targetUser) &&
               $user->hasRole(['super_admin', 'global_admin']) &&
               $user->can('users.manage_2fa');
    }

    /**
     * Determine if the given user can export user data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['super_admin', 'global_admin']) &&
               $user->can('users.export');
    }
}
