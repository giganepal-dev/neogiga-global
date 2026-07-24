<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class CampaignContactCountryResolver
{
    /**
     * Resolve country from various sources.
     */
    public function resolve(?string $countryCode, ?string $countryName, ?string $defaultCountry): array
    {
        // Try by ISO code first
        if (!empty($countryCode) && strlen($countryCode) === 2) {
            $country = DB::table('countries')
                ->where('iso_code_2', strtoupper($countryCode))
                ->first();

            if ($country) {
                return [
                    'id' => $country->id,
                    'code' => $country->iso_code_2,
                    'name' => $country->name,
                    'resolved' => true,
                    'source' => 'country_code',
                ];
            }
        }

        // Try by country name
        if (!empty($countryName)) {
            $country = DB::table('countries')
                ->where('name', 'like', '%' . $countryName . '%')
                ->first();

            if ($country) {
                return [
                    'id' => $country->id,
                    'code' => $country->iso_code_2,
                    'name' => $country->name,
                    'resolved' => true,
                    'source' => 'country_name',
                ];
            }
        }

        // Try default country
        if (!empty($defaultCountry)) {
            $country = DB::table('countries')
                ->where('iso_code_2', strtoupper($defaultCountry))
                ->orWhere('name', 'like', '%' . $defaultCountry . '%')
                ->first();

            if ($country) {
                return [
                    'id' => $country->id,
                    'code' => $country->iso_code_2,
                    'name' => $country->name,
                    'resolved' => true,
                    'source' => 'default',
                ];
            }
        }

        return [
            'id' => null,
            'code' => null,
            'name' => null,
            'resolved' => false,
            'source' => 'none',
        ];
    }
}
