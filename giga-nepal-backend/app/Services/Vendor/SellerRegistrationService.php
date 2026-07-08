<?php

namespace App\Services\Vendor;

use App\Models\Marketplace\Vendor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SellerRegistrationService
{
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $role = Role::firstOrCreate(
                ['name' => 'seller'],
                [
                    'display_name' => 'Seller',
                    'description' => 'Seller and vendor operations',
                    'permissions' => ['seller.access', 'seller.profile.manage', 'seller.products.manage', 'seller.inventory.manage', 'seller.orders.view'],
                    'is_active' => true,
                ],
            );

            $user = User::create([
                'name' => $data['contact_person'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => $role->id,
            ]);

            $requestedVendorType = $data['vendor_type'] ?? 'company';
            $databaseVendorType = in_array($requestedVendorType, ['manufacturer', 'distributor'], true)
                ? $requestedVendorType
                : 'company';

            $vendor = Vendor::create([
                'user_id' => $user->id,
                'name' => $data['business_name'],
                'slug' => $this->uniqueSlug($data['business_name']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'website' => $data['website'] ?? null,
                'description' => $data['description'] ?? null,
                'country_id' => $data['country_id'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'status' => 'pending',
                'type' => $databaseVendorType,
                'is_verified' => false,
                'metadata' => [
                    'contact_person' => $data['contact_person'],
                    'whatsapp' => $data['whatsapp'] ?? null,
                    'requested_vendor_type' => $requestedVendorType,
                    'seller_onboarding_status' => 'submitted',
                    'email_verification_status' => 'placeholder_pending',
                ],
            ]);

            $vendor->profile()->create([
                'business_type' => $data['business_type'] ?? null,
                'about' => $data['description'] ?? null,
                'metadata' => [
                    'contact_person' => $data['contact_person'],
                    'whatsapp' => $data['whatsapp'] ?? null,
                    'registration_source' => 'seller_register_api',
                ],
            ]);

            return [$user, $vendor];
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'seller';
        $slug = $base;
        $i = 1;
        while (Vendor::where('slug', $slug)->exists()) {
            $slug = $base . '-' . ++$i;
        }

        return $slug;
    }
}
