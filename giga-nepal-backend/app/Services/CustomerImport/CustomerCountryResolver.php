<?php

namespace App\Services\CustomerImport;

use Illuminate\Support\Facades\DB;

class CustomerCountryResolver
{
    public function resolve(?string $accountCountry, ?string $sourceCountry, ?string $forcedCountry = null): array
    {
        $primary = $this->one($forcedCountry ?: $accountCountry);
        $secondary = $this->one($sourceCountry);
        $resolved = $primary ?: $secondary;
        $conflict = $primary && $secondary && $primary['iso_code_2'] !== $secondary['iso_code_2'];

        return [
            'resolved' => $resolved,
            'primary' => $primary,
            'secondary' => $secondary,
            'conflict' => (bool) $conflict,
            'confidence' => $resolved ? ($conflict ? 'conflict_primary_selected' : ($resolved['confidence_level'] ?? 'exact')) : 'unresolved',
            'original_account_country' => $accountCountry,
            'original_source_country' => $sourceCountry,
            'forced_country' => $forcedCountry,
        ];
    }

    public function ensure(array $resolved): array
    {
        if (! empty($resolved['id'])) {
            return $resolved;
        }
        $existing = $this->findDatabase($resolved['iso_code_2'] ?? '');
        if ($existing) {
            return $existing;
        }

        $id = DB::table('countries')->insertGetId([
            'name' => $resolved['name'],
            'iso_code_2' => $resolved['iso_code_2'],
            'iso_code_3' => $resolved['iso_code_3'],
            'phone_code' => $resolved['phone_code'] ?? null,
            'region' => $resolved['region'] ?? null,
            'subregion' => $resolved['subregion'] ?? null,
            'is_active' => true,
            'is_eu' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $resolved['id'] = $id;
        $resolved['confidence_level'] = 'canonical_config_created';

        return $resolved;
    }

    private function one(?string $value): ?array
    {
        $key = $this->key($value);
        if ($key === '') {
            return null;
        }
        if ($database = $this->findDatabase($key)) {
            return $database;
        }
        foreach (config('customer_import.canonical_country_fallbacks', []) as $country) {
            $aliases = array_merge([$country['name'], $country['iso_code_2'], $country['iso_code_3']], $country['aliases'] ?? []);
            if (in_array($key, array_map(fn ($alias) => $this->key($alias), $aliases), true)) {
                return $country + ['id' => null, 'confidence_level' => 'exact_canonical_config'];
            }
        }

        return null;
    }

    private function findDatabase(string $value): ?array
    {
        $key = $this->key($value);
        if ($key === '') {
            return null;
        }
        $country = DB::table('countries')
            ->whereRaw('UPPER(name) = ?', [$key])
            ->orWhereRaw('UPPER(iso_code_2) = ?', [$key])
            ->orWhereRaw('UPPER(iso_code_3) = ?', [$key])
            ->first();

        return $country ? [
            'id' => $country->id,
            'name' => $country->name,
            'iso_code_2' => $country->iso_code_2,
            'iso_code_3' => $country->iso_code_3,
            'phone_code' => $country->phone_code ?? null,
            'region' => $country->region ?? null,
            'subregion' => $country->subregion ?? null,
            'confidence_level' => 'exact_database_match',
        ] : null;
    }

    private function key(?string $value): string
    {
        return mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', (string) $value)));
    }
}
