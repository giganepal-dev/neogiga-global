<?php

namespace App\Policies;

use App\Models\Marketplace\Vendor;
use App\Models\Marketplace\VendorOrder;
use App\Models\User;

class SellerOrderPolicy
{
    public function view(User $user, Vendor $vendor, ?VendorOrder $order = null): bool
    {
        if (! $user->hasPermission('seller.orders.view')) {
            return false;
        }

        return ! $order || $order->vendor_id === $vendor->id;
    }
}
