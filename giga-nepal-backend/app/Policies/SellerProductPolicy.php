<?php

namespace App\Policies;

use App\Models\Marketplace\Vendor;
use App\Models\Marketplace\VendorProduct;
use App\Models\User;

class SellerProductPolicy
{
    public function manage(User $user, Vendor $vendor, ?VendorProduct $product = null): bool
    {
        if (! $user->hasPermission('seller.products.manage')) {
            return false;
        }

        return ! $product || $product->vendor_id === $vendor->id;
    }
}
