<?php

namespace App\Services\CatalogImport\Elecforest;

use Illuminate\Support\Str;

class ElecforestSpecificationMapper
{
    private const LABELS = [
        'model' => 'Model', 'mpn' => 'Manufacturer Part Number', 'sku' => 'Supplier SKU',
        'voltage' => 'Voltage', 'supply voltage' => 'Supply Voltage', 'current' => 'Current',
        'output current' => 'Output Current', 'power' => 'Power', 'resistance' => 'Resistance',
        'capacitance' => 'Capacitance', 'frequency' => 'Frequency', 'interface' => 'Interface',
        'protocol' => 'Protocol', 'accuracy' => 'Accuracy', 'resolution' => 'Resolution',
        'operating temperature' => 'Operating Temperature', 'measurement range' => 'Measurement Range',
        'module size' => 'Dimensions', 'dimensions' => 'Dimensions', 'weight' => 'Weight',
        'mounting' => 'Mounting', 'package' => 'Package', 'material' => 'Material',
        'connector' => 'Connector', 'battery type' => 'Battery Type', 'motor type' => 'Motor Type',
        'sensor type' => 'Sensor Type', 'communication type' => 'Communication Type',
    ];

    /** @param array<string, mixed> $record @return list<array<string, mixed>> */
    public function map(array $record): array
    {
        $description = (string) ($record['description'] ?? '');
        $candidates = [];
        preg_match_all('/(?:^|[.;]\s*|\d+\.\s*)([A-Za-z][A-Za-z0-9 \/()_-]{1,42})\s*[:.-]\s*([^.;]{1,220})/u', $description, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $label = trim((string) $match[1]);
            $value = trim((string) $match[2]);
            if ($label === '' || $value === '' || mb_strlen($label) > 45) {
                continue;
            }
            $normalizedName = $this->normalizedName($label);
            [$normalizedValue, $unit] = $this->valueAndUnit($value);
            $candidates[$normalizedName] = [
                'source_name' => Str::limit($label, 250, ''),
                'normalized_name' => $normalizedName,
                'source_value' => Str::limit($value, 2000, ''),
                'normalized_value' => Str::limit($normalizedValue, 2000, ''),
                'source_unit' => $unit,
                'normalized_unit' => $unit,
                'confidence' => isset(self::LABELS[mb_strtolower($label)]) ? 0.85 : 0.6,
                'is_verified' => false,
            ];
        }

        if (! empty($record['supplier_sku'])) {
            $candidates['Supplier SKU'] = [
                'source_name' => 'SKU', 'normalized_name' => 'Supplier SKU',
                'source_value' => $record['supplier_sku'], 'normalized_value' => $record['supplier_sku'],
                'source_unit' => null, 'normalized_unit' => null, 'confidence' => 1.0, 'is_verified' => false,
            ];
        }

        return array_slice(array_values($candidates), 0, 40);
    }

    /** @param array<string, mixed> $record @return list<array{application:string,confidence:float,source_notes:string}> */
    public function applications(array $record): array
    {
        $description = (string) ($record['description'] ?? '');
        if (! preg_match('/\bapplications?\s*[:.-]\s*([^.;]{3,500})/i', $description, $match)) {
            return [];
        }

        $parts = preg_split('/\s*[,|]\s*|\s+and\s+/i', (string) $match[1]) ?: [];
        return array_values(array_map(static fn (string $application): array => [
            'application' => Str::limit(trim($application), 250, ''),
            'confidence' => 0.75,
            'source_notes' => 'Explicitly labelled as an application in the ElecForest source description.',
        ], array_filter($parts, static fn (string $application): bool => mb_strlen(trim($application)) >= 3)));
    }

    private function normalizedName(string $label): string
    {
        $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $label) ?? $label));

        return self::LABELS[$key] ?? 'Additional Specifications: '.Str::headline($key);
    }

    /** @return array{0:string,1:?string} */
    private function valueAndUnit(string $value): array
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        $unit = null;
        if (preg_match('/(?:^|\s)(mA|A|mV|V|kV|mW|W|kW|mΩ|Ω|kΩ|MΩ|pF|nF|uF|µF|Hz|kHz|MHz|GHz|°C|mm|cm|m|g|kg)\b/u', $value, $match)) {
            $unit = $match[1];
        }

        return [$value, $unit];
    }
}
