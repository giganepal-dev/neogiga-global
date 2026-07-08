<?php

namespace App\Services\Seller;

use App\Models\Marketplace\Vendor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SellerContextService
{
    public function vendorFor(User $user): ?Vendor
    {
        $direct = Vendor::query()->where('user_id', $user->id)->first();
        if ($direct) {
            return $direct;
        }

        if (! Schema::hasTable('vendor_staff')) {
            return null;
        }

        $staff = DB::table('vendor_staff')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        return $staff ? Vendor::find($staff->vendor_id) : null;
    }

    public function abortUnlessVendor(User $user): Vendor
    {
        $vendor = $this->vendorFor($user);
        abort_if(! $vendor, 403, 'No seller vendor is linked to this account.');

        return $vendor;
    }
}
