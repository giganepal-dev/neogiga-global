<?php

namespace App\Services\CatalogImport\Elecforest;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use JsonException;

class ElecforestRecordParser
{
    /** @return array<string, mixed> */
    public function parse(string $line, int $lineNumber): array
    {
        if (! mb_check_encoding($line, 'UTF-8')) {
            throw new \InvalidArgumentException("Line {$lineNumber} is not valid UTF-8.");
        }

        try {
            $raw = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException("Line {$lineNumber} is not valid JSON: {$exception->getMessage()}", previous: $exception);
        }

        if (! is_array($raw)) {
            throw new \InvalidArgumentException("Line {$lineNumber} must contain one JSON object.");
        }

        $name = $this->text($raw['product_name'] ?? null);
        $sourceSku = $this->sourceSku($raw['sku'] ?? null);
        $slug = $this->text($raw['slug'] ?? null);
        $rawUrl = $this->url($raw['product_url'] ?? null);
        $urlWasDerived = $rawUrl === '' && $slug !== '' && $slug !== 'products';
        $url = $urlWasDerived ? 'https://elecforest.com/products/'.rawurlencode($slug) : $rawUrl;
        $sourceProductId = $slug !== '' && $slug !== 'products'
            ? Str::limit($slug, 240, '')
            : substr(hash('sha256', $url !== '' ? $url : "line:{$lineNumber}"), 0, 32);

        $record = [
            'line_number' => $lineNumber,
            'source_product_id' => $sourceProductId,
            'source_url' => $url,
            'source_url_was_derived' => $urlWasDerived,
            'source_slug' => $slug,
            'source_name' => $name,
            'supplier_sku' => $sourceSku,
            'main_category' => $this->text($raw['main_category'] ?? null),
            'subcategory' => $this->text($raw['subcategory'] ?? null),
            'description' => $this->description($raw['description'] ?? null),
            'generated_tags' => $this->list($raw['generated_tags'] ?? null),
            'site_tags' => $this->list($raw['site_tags'] ?? null),
            'image_urls' => $this->imageUrls($raw['image_urls'] ?? null),
            'price' => $this->money($raw['price'] ?? null),
            'compare_at_price' => $this->money($raw['compare_at_price'] ?? null),
            'currency' => strtoupper($this->text($raw['currency'] ?? null)),
            'stock_status' => $this->text($raw['stock_status'] ?? null),
            'quantity_text' => $this->text($raw['quantity_text'] ?? null),
            'scraped_at' => $this->date($raw['scraped_at_utc'] ?? null),
            'source_method' => $this->text($raw['source_method'] ?? null),
            'raw' => $raw,
        ];

        $record['content_hash'] = hash('sha256', json_encode($raw, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $record['idempotency_key'] = hash('sha256', 'elecforest|'.$sourceProductId.'|'.$url);
        $record['is_collection_page'] = $url === 'https://elecforest.com/products' || strcasecmp($name, 'All Products') === 0;

        return $record;
    }

    public function sourceSku(mixed $value): ?string
    {
        $sku = strtoupper($this->text($value));
        if ($sku === '' || in_array($sku, ['SKU', 'SKU:', 'N/A', 'NA', '-'], true)) {
            return null;
        }

        return Str::limit($sku, 190, '');
    }

    public function text(mixed $value): string
    {
        if (! is_scalar($value) && $value !== null) {
            return '';
        }

        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $value) ?? $value;
        $value = strip_tags($value);

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function description(mixed $value): string
    {
        $text = $this->text($value);
        $patterns = [
            '/Elecforest is a belt that connects.*$/i',
            '/(?:contact|message) us (?:on|at).*$/i',
            '/(?:free|fast) shipping.*$/i',
            '/buy now.*$/i',
        ];

        foreach ($patterns as $pattern) {
            $text = trim(preg_replace($pattern, '', $text) ?? $text);
        }

        return Str::limit($text, 20000, '');
    }

    /** @return list<string> */
    private function list(mixed $value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = preg_split('/\s*[|,;]\s*/', (string) $value) ?: [];
        }

        return array_values(array_unique(array_filter(array_map(fn ($part) => $this->text($part), $parts))));
    }

    /** @return list<string> */
    private function imageUrls(mixed $value): array
    {
        $values = is_array($value) ? $value : preg_split('/\s*[|\n]\s*/', (string) $value);
        $urls = [];
        foreach ($values ?: [] as $candidate) {
            $url = $this->url($candidate);
            if ($url !== '' && str_starts_with($url, 'https://') && $this->isProductImageUrl($url)) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    public function isProductImageUrl(string $url): bool
    {
        $basename = strtolower(basename((string) parse_url($url, PHP_URL_PATH)));

        return $basename !== '' && ! in_array($basename, config('elecforest_import.ignored_image_basenames', []), true);
    }

    private function url(mixed $value): string
    {
        $url = trim((string) $value);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        return Str::limit($url, 2000, '');
    }

    private function money(mixed $value): ?string
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);
        if ($normalized === null || $normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 6, '.', '');
    }

    private function date(mixed $value): ?string
    {
        if (trim((string) $value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->utc()->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }
}
