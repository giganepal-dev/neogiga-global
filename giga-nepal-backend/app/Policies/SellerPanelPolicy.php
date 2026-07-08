<?php

namespace App\Policies;

use App\Models\Marketplace\Vendor;
use App\Models\User;

class SellerPanelPolicy
{
    public function access(User $user, Vendor $vendor): bool
    {
        return $vendor->user_id === $user->id || $user->hasPermission('seller.access');
    }
}
