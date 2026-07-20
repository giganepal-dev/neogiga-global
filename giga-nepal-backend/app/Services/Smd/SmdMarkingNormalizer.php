<?php

namespace App\Services\Smd;

/**
 * Normalizes SMD top markings for search and deduplication.
 *
 * The same marking can belong to multiple products — "0A" might be a Zener
 * diode from Diotec AND a voltage detector from Torex. Normalization is
 * lossy for search (uppercase, trimmed) while preserving the original
 * display text with case, dots, hyphens, and symbols intact.
 */
class SmdMarkingNormalizer
{
    /**
     * Normalize for search/indexing: uppercase, trim, collapse whitespace.
     * Does NOT remove dots, hyphens, plus/minus, or underscores.
     */
    public function normalize(string $marking): string
    {
        $normalized = trim($marking);
        $normalized = $this->replaceUnicodeVariants($normalized);
        $normalized = mb_strtoupper($normalized, 'UTF-8');
        $normalized = preg_replace('/\s+/', ' ', $normalized); // collapse spaces
        $normalized = trim($normalized);

        return $normalized;
    }

    /**
     * Clean for display: trim, normalize unicode, preserve original case and symbols.
     */
    public function display(string $marking): string
    {
        $display = trim($marking);
        $display = $this->replaceUnicodeVariants($display);
        $display = preg_replace('/\s+/', ' ', $display);

        return trim($display);
    }

    /**
     * Check if two markings are equivalent after normalization.
     */
    public function equivalent(string $a, string $b): bool
    {
        return $this->normalize($a) === $this->normalize($b);
    }

    /**
     * Extract search prefixes for index lookups.
     */
    public function prefixes(string $marking): array
    {
        $n = $this->normalize($marking);

        return [
            'first_char' => mb_substr($n, 0, 1),
            'first_two' => mb_substr($n, 0, 2),
            'length' => mb_strlen($n),
            'full' => $n,
        ];
    }

    /**
     * Replace common Unicode lookalikes with ASCII equivalents.
     */
    private function replaceUnicodeVariants(string $text): string
    {
        $map = [
            "\xC2\xA0" => ' ',     // non-breaking space
            "\xE2\x80\x89" => ' ', // thin space
            '−' => '-',            // minus sign → hyphen-minus
            '–' => '-',            // en dash
            '—' => '-',            // em dash
            '′' => "'",            // prime
            '″' => '"',            // double prime
        ];

        return str_replace(array_keys($map), array_values($map), $text);
    }
}
