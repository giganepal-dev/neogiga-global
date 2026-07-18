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
}
