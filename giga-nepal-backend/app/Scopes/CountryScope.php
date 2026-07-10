<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Class CountryScope
 * 
 * Global scope to ensure users only see data from their assigned country.
 */
class CountryScope implements Scope
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

        // Super admins, global admins, and country admins can see all countries
        if ($user->hasRole('super_admin') || 
            $user->hasRole('global_admin') || 
            $user->hasRole('country_admin')) {
            return;
        }

        // If the model has a country_id column, filter by user's country
        if ($model->hasColumn('country_id')) {
            $countryId = $this->getUserCountryId($user);
            
            if ($countryId) {
                $builder->where($model->getTable() . '.country_id', $countryId);
            }
        }

        // Apply country filtering for marketplace-specific models
        if ($this->isMarketplaceModel($model)) {
            $countryId = $this->getUserCountryId($user);
            
            if ($countryId) {
                $builder->where(function ($query) use ($countryId) {
                    $query->where('country_id', $countryId)
                          ->orWhereNull('country_id'); // Allow global items
                });
            }
        }
    }

    /**
     * Get the country ID for the user.
     */
    protected function getUserCountryId($user): ?int
    {
        // Check if user has a country_id directly
        if (isset($user->country_id)) {
            return $user->country_id;
        }

        // Check through organization
        if ($user->organization && isset($user->organization->country_id)) {
            return $user->organization->country_id;
        }

        // Check through vendor/distributor relationships
        if ($user->vendor && isset($user->vendor->country_id)) {
            return $user->vendor->country_id;
        }

        if ($user->distributor && isset($user->distributor->country_id)) {
            return $user->distributor->country_id;
        }

        return null;
    }

    /**
     * Check if the model is a marketplace-specific model.
     */
    protected function isMarketplaceModel(Model $model): bool
    {
        $marketplaceModels = [
            \App\Models\Marketplace\Product::class,
            \App\Models\Marketplace\Vendor::class,
            \App\Models\Marketplace\InventoryStock::class,
            \App\Models\Marketplace\Warehouse::class,
        ];

        return in_array(get_class($model), $marketplaceModels);
    }
}
