<?php

namespace App\Catalog\Ingestion\Normalizers;

use Illuminate\Support\Str;

class CatalogNormalizer
{
    public function text(?string $value): ?string
    {
        $value = $value === null ? null : preg_replace('/\s+/u', ' ', trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $value === '' ? null : $value;
    }

    public function mpn(?string $value): ?string
    {
        $value = $this->text($value);

        return $value ? strtoupper(preg_replace('/\s+/', '', $value)) : null;
    }

    public function slug(string $value): string
    {
        return Str::limit(Str::slug($this->text($value) ?? 'product'), 180, '');
    }

    /** @return array{value:string,numeric_value:?float,numeric_max_value:?float,unit:?string,confidence:float} */
    public function specification(string $value, ?string $unit = null): array
    {
        $original = $this->text($value) ?? '';
        $unit = $this->canonicalUnit($unit);
        preg_match_all('/-?\d+(?:\.\d+)?/', $original, $numbers);
        $isRange = (bool) preg_match('/\bto\b|\s[-–]\s/i', $original);
        $numericValues = array_map('floatval', $numbers[0] ?? []);

        return [
            'value' => $original,
            'numeric_value' => $numericValues[0] ?? null,
            'numeric_max_value' => $isRange && count($numericValues) > 1 ? $numericValues[1] : null,
            'unit' => $unit,
            'confidence' => $original !== '' ? ($unit || $numericValues !== [] ? 0.9 : 0.65) : 0.0,
        ];
    }

    public function canonicalUrl(string $url): string
    {
        $parts = parse_url($url) ?: [];
        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host'] ?? '');
        $path = '/'.ltrim($parts['path'] ?? '', '/');
        $path = rtrim($path, '/') ?: '/';

        return $host ? $scheme.'://'.$host.$path : $url;
    }

    private function canonicalUnit(?string $unit): ?string
    {
        return match (strtolower(trim((string) $unit))) {
            'v', 'volt', 'volts' => 'V',
            'ma', 'milliamps', 'milliampere' => 'mA',
            'a', 'amp', 'amps', 'ampere', 'amperes' => 'A',
            'mm', 'millimeter', 'millimeters', 'millimetre', 'millimetres' => 'mm',
            'cm', 'centimeter', 'centimeters', 'centimetre', 'centimetres' => 'cm',
            'mhz' => 'MHz', 'ghz' => 'GHz', 'khz' => 'kHz',
            'i2c', 'iic', 'i²c' => 'I2C',
            default => $unit ? trim($unit) : null,
        };
    }
}
