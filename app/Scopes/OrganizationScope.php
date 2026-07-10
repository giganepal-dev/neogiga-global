<?php

namespace NeoGiga\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use NeoGiga\Models\User;

class OrganizationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        // If no user is authenticated, apply strict isolation
        if (!$user) {
            $builder->whereNull('organization_id');
            return;
        }

        // Super and global admins can see all organizations
        if ($user->hasRole(['super_admin', 'global_admin'])) {
            return;
        }

        // Country admins can see organizations in their country
        if ($user->hasRole(['country_admin']) && $user->country_id) {
            $builder->whereHas('organization', function ($query) use ($user) {
                $query->where('country_id', $user->country_id);
            });
            return;
        }

        // All other users only see their own organization's data
        if ($user->organization_id) {
            $builder->where('organization_id', $user->organization_id);
        } else {
            $builder->whereNull('organization_id');
        }
    }

    /**
     * Extend the query builder with specific methods.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutOrganizationScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forOrganization', function (Builder $builder, $organizationId) {
            return $builder->where('organization_id', $organizationId);
        });
    }
}
