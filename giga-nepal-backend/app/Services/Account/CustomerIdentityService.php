<?php

namespace App\Services\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerIdentityService
{
    public function __construct(private readonly AccountHubService $accounts) {}

    /**
     * @return array{name:string,email:string,phone:string,company_name:string,country_id:?int,country:string}
     */
    public function defaults(?User $user): array
    {
        if (! $user) {
            return [
                'name' => '',
                'email' => '',
                'phone' => '',
                'company_name' => '',
                'country_id' => null,
                'country' => '',
            ];
        }

        $profile = $this->accounts->customerProfile($user);
        $profileName = trim(implode(' ', array_filter([
            $profile->first_name ?? null,
            $profile->last_name ?? null,
        ])));
        $countryId = isset($profile->country_id) ? (int) $profile->country_id : null;
        $country = '';

        if ($countryId && Schema::hasTable('countries')) {
            $country = (string) (DB::table('countries')->where('id', $countryId)->value('name') ?? '');
        }

        return [
            'name' => $profileName !== '' ? $profileName : (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($profile->phone ?? ''),
            'company_name' => (string) ($profile->company_name ?? ''),
            'country_id' => $countryId,
            'country' => $country,
        ];
    }

    /** @return array{contact_name:string,contact_email:string,contact_phone:string,company_name:string} */
    public function rfqDefaults(?User $user): array
    {
        $defaults = $this->defaults($user);

        return [
            'contact_name' => $defaults['name'],
            'contact_email' => $defaults['email'],
            'contact_phone' => $defaults['phone'],
            'company_name' => $defaults['company_name'],
        ];
    }
}
