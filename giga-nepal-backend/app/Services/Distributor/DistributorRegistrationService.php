<?php

namespace App\Services\Distributor;

use App\Models\Distributor\Distributor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DistributorRegistrationService
{
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $role = Role::firstOrCreate(
                ['name' => 'distributor'],
                [
                    'display_name' => 'Distributor',
                    'description' => 'Distributor, reseller, and territory operations',
                    'permissions' => ['distributor.access', 'distributor.leads.manage', 'distributor.customers.manage', 'distributor.orders.manage'],
                    'is_active' => true,
                ],
            );

            $user = User::create([
                'name' => $data['contact_person'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => $role->id,
            ]);

            $distributor = Distributor::create([
                'user_id' => $user->id,
                'name' => $data['business_name'],
                'slug' => $this->uniqueSlug($data['business_name']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'type' => $data['distributor_type'],
                'status' => 'pending',
                'country_id' => $data['country_id'] ?? null,
                'metadata' => [
                    'contact_person' => $data['contact_person'],
                    'whatsapp' => $data['whatsapp'] ?? null,
                    'onboarding_status' => 'submitted',
                    'email_verification_status' => 'placeholder_pending',
                ],
            ]);

            $distributor->profile()->create([
                'business_name' => $data['business_name'],
                'tax_number' => $data['tax_number'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'address' => $data['address'] ?? null,
                'capabilities' => ['type' => $data['distributor_type']],
            ]);

            return [$user, $distributor];
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'distributor';
        $slug = $base;
        $i = 1;
        while (Distributor::where('slug', $slug)->exists()) {
            $slug = $base . '-' . ++$i;
        }

        return $slug;
    }
}
