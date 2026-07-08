<?php

namespace App\Policies;

use App\Models\Marketplace\Vendor;
use App\Models\User;

class SellerInventoryPolicy
{
    public function manage(User $user, Vendor $vendor): bool
    {
        return $user->hasPermission('seller.inventory.manage')
            && ($vendor->user_id === $user->id || $user->hasPermission('seller.access'));
    }
}
