<?php

namespace App\Catalog\Ingestion\Parsers;

class JsonLdExtractor
{
    /** @return array<string, mixed> */
    public function product(string $html): array
    {
        preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches);
        foreach ($matches[1] ?? [] as $json) {
            $decoded = json_decode(trim($json), true);
            foreach ($this->flatten($decoded) as $node) {
                $types = (array) ($node['@type'] ?? []);
                if (in_array('Product', $types, true) || ($node['@type'] ?? null) === 'Product') {
                    return $node;
                }
            }
        }

        return [];
    }

    private function flatten(mixed $value): iterable
    {
        if (! is_array($value)) {
            return;
        }
        if (array_is_list($value)) {
            foreach ($value as $item) {
                yield from $this->flatten($item);
            }

            return;
        }
        if (isset($value['@graph'])) {
            yield from $this->flatten($value['@graph']);
        }
        yield $value;
    }
}
