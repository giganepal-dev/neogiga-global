<?php

namespace App\Catalog\Ingestion\Validation;

class CatalogQualityScorer
{
    /** @param array<string, mixed> $candidate */
    public function score(array $candidate): float
    {
        $score = 0.0;
        $score += $this->present($candidate['title'] ?? null) ? 20 : 0;
        $score += $this->present($candidate['mpn'] ?? null) ? 20 : 0;
        $score += $this->present($candidate['brand'] ?? null) || $this->present($candidate['manufacturer'] ?? null) ? 15 : 0;
        $score += $this->present($candidate['source_url'] ?? null) && $this->present($candidate['canonical_url'] ?? null) ? 15 : 0;
        $score += ! empty($candidate['specifications']) ? 15 : 0;
        $score += ! empty($candidate['category_path']) ? 10 : 0;
        $score += ! empty($candidate['assets']) ? 5 : 0;

        return round($score, 2);
    }

    /** @param array<string, mixed> $candidate @return list<string> */
    public function missingFields(array $candidate): array
    {
        $fields = [
            'title' => $candidate['title'] ?? null,
            'mpn' => $candidate['mpn'] ?? null,
            'brand_or_manufacturer' => $candidate['brand'] ?? $candidate['manufacturer'] ?? null,
            'canonical_url' => $candidate['canonical_url'] ?? null,
            'specifications' => $candidate['specifications'] ?? null,
            'category_path' => $candidate['category_path'] ?? null,
            'assets' => $candidate['assets'] ?? null,
        ];

        return array_keys(array_filter($fields, fn ($value) => ! $this->present($value)));
    }

    private function present(mixed $value): bool
    {
        return is_array($value) ? $value !== [] : filled($value);
    }
}
