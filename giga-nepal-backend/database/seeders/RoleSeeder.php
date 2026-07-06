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
                'permissions' => ['admin.access', 'catalog.manage', 'orders.manage', 'imports.manage'],
                'is_active' => true,
            ],
            [
                'name' => 'seller',
                'display_name' => 'Seller',
                'description' => 'Seller and vendor operations',
                'permissions' => ['seller.access', 'catalog.manage_own', 'orders.view_own'],
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
