<?php

namespace NeoGiga\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use NeoGiga\Models\Product;
use NeoGiga\Models\SellerOffer;
use NeoGiga\Models\Order;
use NeoGiga\Models\Organization;
use NeoGiga\Models\Inventory;
use NeoGiga\Models\Settlement;
use NeoGiga\Models\Warehouse;
use NeoGiga\Models\RFQ;
use NeoGiga\Models\User;
use NeoGiga\Policies\ProductPolicy;
use NeoGiga\Policies\SellerOfferPolicy;
use NeoGiga\Policies\OrderPolicy;
use NeoGiga\Policies\OrganizationPolicy;
use NeoGiga\Policies\InventoryPolicy;
use NeoGiga\Policies\SettlementPolicy;
use NeoGiga\Policies\WarehousePolicy;
use NeoGiga\Policies\RFQPolicy;
use NeoGiga\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        SellerOffer::class => SellerOfferPolicy::class,
        Order::class => OrderPolicy::class,
        Organization::class => OrganizationPolicy::class,
        Inventory::class => InventoryPolicy::class,
        Settlement::class => SettlementPolicy::class,
        Warehouse::class => WarehousePolicy::class,
        RFQ::class => RFQPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define implicit gates for common permissions
        Gate::before(function ($user, $ability) {
            // Super admin can do anything
            if ($user->hasRole(['super_admin'])) {
                return true;
            }
        });

        // Define ability macro for checking permissions
        Gate::define('viewAny', function ($user, $model) {
            $policyClass = get_class($model) . 'Policy';
            if (class_exists($policyClass)) {
                $policy = app($policyClass);
                return method_exists($policy, 'viewAny') ? $policy->viewAny($user) : false;
            }
            return false;
        });
    }
}
