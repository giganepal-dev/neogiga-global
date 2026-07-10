<?php

namespace NeoGiga\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use NeoGiga\Models\User;

class CountryScope implements Scope
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
            $builder->whereNull('country_id');
            return;
        }

        // Super and global admins can see all countries
        if ($user->hasRole(['super_admin', 'global_admin'])) {
            return;
        }

        // Country admins can only see data for their country
        if ($user->hasRole(['country_admin']) && $user->country_id) {
            $builder->where('country_id', $user->country_id);
            return;
        }

        // Regional admins can see data for their region's countries
        if ($user->hasRole(['regional_admin']) && $user->region_id) {
            $builder->whereHas('country', function ($query) use ($user) {
                $query->where('region_id', $user->region_id);
            });
            return;
        }

        // All other users see data based on their country assignment
        if ($user->country_id) {
            $builder->where('country_id', $user->country_id);
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
        $builder->macro('withoutCountryScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forCountry', function (Builder $builder, $countryId) {
            return $builder->where('country_id', $countryId);
        });

        $builder->macro('forCountries', function (Builder $builder, array $countryIds) {
            return $builder->whereIn('country_id', $countryIds);
        });
    }
}
