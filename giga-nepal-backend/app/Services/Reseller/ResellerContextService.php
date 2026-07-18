<?php

namespace App\Services\Reseller;

use App\Models\Reseller;
use App\Models\User;

class ResellerContextService
{
    public function resellerFor(User $user): ?Reseller
    {
        return Reseller::where('user_id', $user->id)->where('is_active', true)->first();
    }
}
