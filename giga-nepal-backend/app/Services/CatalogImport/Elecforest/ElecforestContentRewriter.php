<?php

namespace App\Services\CatalogImport\Elecforest;

use Illuminate\Support\Str;

class ElecforestContentRewriter
{
    /** @param array<string, mixed> $record @param list<array<string, mixed>> $specifications @return array<string, mixed> */
    public function rewrite(array $record, array $specifications): array
    {
        $name = $this->name((string) $record['source_name']);
        $category = trim((string) ($record['category_name'] ?? $record['subcategory'] ?? $record['main_category'] ?? 'Electronic product'));
        $facts = $this->facts((string) ($record['description'] ?? ''));
        $primarySpec = $specifications[0]['source_value'] ?? null;
        $subtitle = $category.($primarySpec ? ' — '.Str::limit((string) $primarySpec, 90, '') : '');

        $short = $facts
            ? $name.' is listed with source details including '.lcfirst(Str::limit(implode('; ', array_slice($facts, 0, 2)), 260, '')).'.'
            : $name.' is an ElecForest catalogue item awaiting detailed technical-content review.';

        $features = array_slice($facts, 0, 12);
        $sourceNotes = 'Generated only from the ElecForest title, category and supplied description. No independent manufacturer verification was available in the export.';
        $disclaimer = (string) config('elecforest_import.advisory_disclaimer');

        $sections = [
            $name,
            '',
            $short,
            '',
            'Key source details',
            $features ? implode("\n", array_map(static fn (string $fact): string => '• '.$fact, $features)) : '• Detailed source features were not provided.',
            '',
            'Applications',
            'Application suitability is not verified in the source export. Confirm the required electrical, mechanical and environmental limits before use.',
            '',
            'Compatibility',
            'Compatibility was not specified in the source export.',
            '',
            'Package contents',
            'Package contents were not specified in the source export.',
            '',
            'Usage and safety notes',
            'Review the available source specifications and the applicable manufacturer documentation before installation, connection or operation.',
            '',
            'Warranty',
            'Warranty information was not supplied in the source export; confirm current terms with NeoGiga before purchase.',
            '',
            'NeoGiga availability',
            'Available through NeoGiga for prototyping, education, maintenance and production sourcing, subject to technical and commercial review.',
            '',
            $disclaimer,
        ];

        return [
            'name' => $name,
            'subtitle' => Str::limit($subtitle, 180, ''),
            'short_description' => Str::limit($short, 420, ''),
            'description' => implode("\n", $sections),
            'key_features' => $features,
            'applications' => [],
            'compatibility' => [],
            'package_contents' => [],
            'usage_notes' => ['Verify source specifications and applicable manufacturer documentation before use.'],
            'safety_notes' => ['Technical and safety limits require independent verification.'],
            'warranty' => 'Not supplied by source; confirm with NeoGiga before purchase.',
            'source_notes' => $sourceNotes,
            'confidence_level' => $facts ? 'medium_source_derived' : 'low_missing_source_detail',
            'last_updated' => now()->toIso8601String(),
            'advisory_disclaimer' => $disclaimer,
        ];
    }

    private function name(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $replacements = [
            '/\bwifi\b/i' => 'Wi-Fi',
            '/\bbluetooth\b/i' => 'Bluetooth',
            '/\busb\b/i' => 'USB',
            '/\bdc-dc\b/i' => 'DC-DC',
            '/\bac-dc\b/i' => 'AC-DC',
            '/\bled\b/i' => 'LED',
            '/\bgps\b/i' => 'GPS',
            '/\bgsm\b/i' => 'GSM',
        ];
        foreach ($replacements as $pattern => $replacement) {
            $name = preg_replace($pattern, $replacement, $name) ?? $name;
        }

        return Str::limit($name, 240, '');
    }

    /** @return list<string> */
    private function facts(string $description): array
    {
        $description = preg_replace('/\s*(?:\d+\.)\s*/', '. ', $description) ?? $description;
        $parts = preg_split('/(?<=[.!?])\s+|\s*;\s*/u', $description) ?: [];
        $facts = [];
        foreach ($parts as $part) {
            $part = trim($part, " \t\n\r\0\x0B.-");
            if (mb_strlen($part) < 4) {
                continue;
            }
            $part = ucfirst($part);
            if (! str_ends_with($part, '.')) {
                $part .= '.';
            }
            $facts[] = Str::limit($part, 280, '');
        }

        return array_values(array_unique($facts));
    }
}
