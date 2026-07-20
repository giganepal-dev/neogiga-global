<?php

namespace App\Services\Manufacturer;

use App\Models\Manufacturer;
use App\Models\User;

class ManufacturerContextService
{
    public function manufacturerFor(User $user): ?Manufacturer
    {
        return Manufacturer::where('user_id', $user->id)->where('is_active', true)->first();
    }

    public function abortUnlessManufacturer(User $user): Manufacturer
    {
        $manufacturer = $this->manufacturerFor($user);
        abort_if(! $manufacturer, 403, 'No manufacturer account is linked to this user.');

        return $manufacturer;
    }
}
