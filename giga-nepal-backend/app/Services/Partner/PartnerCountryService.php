<?php

namespace App\Services\Partner;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

class PartnerCountryService
{
    public const SCOPE_COUNTRY = 'country';
    public const SCOPE_GLOBAL = 'global';

    public function activeCountries(): Collection
    {
        return Country::query()
            ->where('countries.is_active', true)
            ->whereHas('marketplaces', fn ($query) => $query->where('is_active', true)->whereRaw('UPPER(code) <> ?', ['GLOBAL']))
            ->when(Schema::hasColumn('countries', 'sort_order'), fn ($query) => $query->orderBy('countries.sort_order'))
            ->orderBy('countries.name')
            ->get(['countries.id', 'countries.name', 'countries.iso_code_2', 'countries.phone_code']);
    }

    public function detectedCountry(Request $request): ?Country
    {
        $iso = strtoupper(trim((string) $request->header('CF-IPCountry', '')));
        if (! preg_match('/^[A-Z]{2}$/', $iso)) {
            $language = (string) $request->header('Accept-Language', '');
            $iso = preg_match('/[a-z]{2}[-_]([a-z]{2})/i', $language, $match)
                ? strtoupper($match[1])
                : '';
        }

        if ($iso === '') {
            return null;
        }

        return Country::query()
            ->where('is_active', true)
            ->where('iso_code_2', $iso)
            ->whereHas('marketplaces', fn ($query) => $query->where('is_active', true)->whereRaw('UPPER(code) <> ?', ['GLOBAL']))
            ->first();
    }

    public function options(Request $request): array
    {
        $detected = $this->detectedCountry($request);

        return [
            'countries' => $this->activeCountries(),
            'detected_country_id' => $detected?->id,
            'detected_country' => $detected?->only(['id', 'name', 'iso_code_2']),
            'country_locked' => $detected !== null,
            'detection_source' => $detected ? 'geolocation' : null,
            'scopes' => [self::SCOPE_COUNTRY, self::SCOPE_GLOBAL],
        ];
    }

    public function resolveSignupCountry(Request $request, mixed $submittedCountryId): int
    {
        if ($detected = $this->detectedCountry($request)) {
            return (int) $detected->id;
        }

        return $this->assertActiveCountryId($submittedCountryId);
    }

    public function assertActiveCountryId(mixed $countryId): int
    {
        $countryId = filter_var($countryId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $active = $countryId && Country::query()
            ->whereKey($countryId)
            ->where('is_active', true)
            ->whereHas('marketplaces', fn ($query) => $query->where('is_active', true)->whereRaw('UPPER(code) <> ?', ['GLOBAL']))
            ->exists();

        if (! $active) {
            throw ValidationException::withMessages([
                'country_id' => 'Select a country with an active NeoGiga marketplace.',
            ]);
        }

        return (int) $countryId;
    }

    public function normalizeScope(mixed $scope): string
    {
        $scope = $scope ?: self::SCOPE_COUNTRY;
        if (! in_array($scope, [self::SCOPE_COUNTRY, self::SCOPE_GLOBAL], true)) {
            throw ValidationException::withMessages([
                'operating_scope' => 'Choose single country or global operation.',
            ]);
        }

        return $scope;
    }

    public function marketplaceIdForCountry(int $countryId): ?int
    {
        return Marketplace::query()
            ->where('country_id', $countryId)
            ->where('is_active', true)
            ->whereRaw('UPPER(code) <> ?', ['GLOBAL'])
            ->orderByDesc('is_default')
            ->value('id');
    }

    public function countryName(int $countryId): string
    {
        return (string) Country::query()->whereKey($countryId)->value('name');
    }

    public function marketplaceIdsForScope(string $scope, int $countryId): Collection
    {
        return Marketplace::query()
            ->where('is_active', true)
            ->when($scope !== self::SCOPE_GLOBAL, fn ($query) => $query->where('country_id', $countryId)->whereRaw('UPPER(code) <> ?', ['GLOBAL']))
            ->pluck('id');
    }
}
