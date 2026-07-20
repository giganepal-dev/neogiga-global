<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->roles() as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }

    private function roles(): array
    {
        return [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Full platform administration',
                'permissions' => ['*'],
                'is_active' => true,
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Marketplace administration',
                'permissions' => [
                    'admin.access',
                    'catalog.manage',
                    'orders.manage',
                    'imports.manage',
                    'customers.view',
                    'customers.import',
                    'customers.export',
                    'customers.consent.manage',
                    'customers.suppression.manage',
                    'campaigns.create',
                    'campaigns.view',
                    'campaigns.approve',
                    'campaigns.schedule',
                    'campaigns.test',
                    'campaigns.send',
                    'email.templates.manage',
                    'email.providers.manage',
                    'email.events.view',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'seller',
                'display_name' => 'Seller',
                'description' => 'Seller and vendor operations',
                'permissions' => [
                    'seller.access',
                    'seller.profile.manage',
                    'seller.products.manage',
                    'seller.inventory.manage',
                    'seller.orders.view',
                    'seller.orders.manage',
                    'seller.payouts.view',
                    'seller.support.manage',
                    'catalog.manage_own',
                    'orders.view_own',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'reseller',
                'display_name' => 'Reseller',
                'description' => 'Regional reseller partner operations',
                'permissions' => [
                    'reseller.access',
                    'reseller.products.manage',
                    'reseller.orders.view',
                    'reseller.rfq.bid',
                    'reseller.support.manage',
                    'reseller.messaging.manage',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'distributor',
                'display_name' => 'Distributor',
                'description' => 'Distributor, reseller, and territory operations',
                'permissions' => [
                    'distributor.access',
                    'distributor.leads.manage',
                    'distributor.customers.manage',
                    'distributor.orders.manage',
                    'distributor.commissions.view',
                    'distributor.payouts.view',
                    'distributor.downlines.view',
                    'distributor.support.manage',
                    'distributor.messaging.manage',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'manufacturer',
                'display_name' => 'Manufacturer',
                'description' => 'Manufacturer global inventory and regional allocation',
                'permissions' => [
                    'manufacturer.access',
                    'manufacturer.inventory.manage',
                    'manufacturer.allocations.manage',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'pos_cashier',
                'display_name' => 'POS Cashier',
                'description' => 'Point-of-sale terminal operations',
                'permissions' => [
                    'pos.access',
                    'pos.refund',
                    'pos.customers.manage',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'b2b_buyer',
                'display_name' => 'B2B Buyer',
                'description' => 'Institutional and bulk buyer account',
                'permissions' => [
                    'b2b.access',
                    'b2b.rfq.manage',
                    'b2b.quotations.view',
                    'b2b.purchase_orders.manage',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'customer',
                'display_name' => 'Customer',
                'description' => 'Buyer account',
                'permissions' => ['cart.manage', 'checkout.create', 'orders.view'],
                'is_active' => true,
            ],
            [
                'name' => 'support',
                'display_name' => 'Support',
                'description' => 'Support and handoff operations',
                'permissions' => ['support.access', 'orders.view', 'ai.handoff.manage'],
                'is_active' => true,
            ],
        ];
    }
}
