<?php

namespace App\Services\Product;

use App\Models\Marketplace\Product;
use Illuminate\Support\Collection;

/**
 * Presents retained importer attributes as readable specifications without
 * overwriting curated specification records.
 */
class ProductSpecificationResolver
{
    /** @return Collection<int, array{label:string,value:string,url:?string,source:string}> */
    public function sourceSpecifications(Product $product): Collection
    {
        $attributes = is_array($product->attributes) ? $product->attributes : [];
        $raw = $this->jsonObject(data_get($attributes, 'raw.extra'));
        $specifications = [];

        foreach ([$attributes['specifications'] ?? null, $attributes['specs'] ?? null, $raw['attributes'] ?? null] as $source) {
            foreach ($this->scalarPairs($source) as $label => $value) {
                $this->add($specifications, $label, $value, null, 'source_attribute');
            }
        }

        foreach ([
            'Package' => $raw['package'] ?? ($attributes['package'] ?? null),
            'Packaging' => $raw['packaging'] ?? ($attributes['packaging'] ?? null),
            'Minimum Order Quantity' => $raw['moq'] ?? ($attributes['moq'] ?? null),
            'Order Multiple' => $raw['order_multiple'] ?? ($attributes['order_multiple'] ?? null),
            'RoHS Compliant' => array_key_exists('rohs', $raw) ? ($raw['rohs'] ? 'Yes' : 'No') : null,
        ] as $label => $value) {
            $this->add($specifications, $label, $value, null, 'source_attribute');
        }

        $datasheet = data_get($raw, 'datasheet.pdf') ?: data_get($attributes, 'datasheet_url');
        $this->add($specifications, 'Datasheet', $datasheet ? 'Open datasheet' : null, $datasheet, 'source_document');

        return collect($specifications)->values();
    }

    /** @return array<string, mixed> */
    private function jsonObject(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, string> */
    private function scalarPairs(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $pairs = [];
        foreach ($value as $label => $rawValue) {
            if (! is_string($label) || is_array($rawValue) || is_object($rawValue)) {
                continue;
            }
            $formatted = is_bool($rawValue) ? ($rawValue ? 'Yes' : 'No') : trim((string) $rawValue);
            if ($formatted === '' || $formatted === '-') {
                continue;
            }
            $pairs[$label] = $formatted;
        }

        return $pairs;
    }

    /** @param array<string, array{label:string,value:string,url:?string,source:string}> $specifications */
    private function add(array &$specifications, string $label, mixed $value, ?string $url, string $source): void
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || $value === '-') {
            return;
        }

        $key = strtolower(preg_replace('/[^a-z0-9]+/i', '', $label) ?? $label);
        if ($key === '' || isset($specifications[$key])) {
            return;
        }

        $specifications[$key] = [
            'label' => $label,
            'value' => $value,
            'url' => filter_var($url, FILTER_VALIDATE_URL) ? $url : null,
            'source' => $source,
        ];
    }
}
