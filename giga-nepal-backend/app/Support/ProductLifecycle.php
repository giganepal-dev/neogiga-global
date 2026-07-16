<?php

namespace App\Support;

final class ProductLifecycle
{
    /**
     * Lifecycle values shown to catalog operators. Imports can retain an
     * unrecognised manufacturer value, so callers should not silently coerce it.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'ACTIVE' => 'Active',
            'NEW_PRODUCT' => 'New product',
            'NOT_RECOMMENDED_FOR_NEW_DESIGNS' => 'Not recommended for new designs',
            'NRND' => 'Not recommended for new designs (NRND)',
            'LAST_TIME_BUY' => 'Last time buy',
            'END_OF_LIFE' => 'End of life',
            'EOL' => 'End of life (EOL)',
            'DISCONTINUED' => 'Discontinued',
            'OBSOLETE' => 'Obsolete',
            'NEEDS_VERIFICATION' => 'Needs verification',
            'UNKNOWN' => 'Status unverified',
        ];
    }

    public static function normalize(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        return $value === '' ? null : $value;
    }
}
