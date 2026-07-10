<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Class OrganizationScope
 * 
 * Global scope to ensure users only see data from their organization.
 */
class OrganizationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        // Super admins and global admins can see all data
        if ($user->hasRole('super_admin') || $user->hasRole('global_admin')) {
            return;
        }

        // If the model has an organization_id column, filter by user's organization
        if ($model->getTable() === 'organizations' || $model->hasColumn('organization_id')) {
            $organizationId = $this->getUserOrganizationId($user);
            
            if ($organizationId) {
                $builder->where(function ($query) use ($model, $organizationId) {
                    if ($model->getTable() === 'organizations') {
                        $query->where('id', $organizationId);
                    } else {
                        $query->where($model->getTable() . '.organization_id', $organizationId);
                    }
                });
            }
        }
    }

    /**
     * Get the organization ID for the user.
     */
    protected function getUserOrganizationId($user): ?int
    {
        // Check if user belongs to an organization directly
        if (isset($user->organization_id)) {
            return $user->organization_id;
        }

        // Check if user has a related organization through other models
        if ($user->vendor) {
            return $user->vendor->organization_id ?? null;
        }

        if ($user->distributor) {
            return $user->distributor->organization_id ?? null;
        }

        return null;
    }
}
