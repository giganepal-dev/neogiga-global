<?php

namespace App\Services\CatalogImport\Elecforest;

use Illuminate\Support\Str;

class ElecforestSeoGenerator
{
    /** @param array<string, mixed> $product @param array<string, mixed> $content @return array<string, mixed> */
    public function generate(array $product, array $content): array
    {
        $name = (string) $product['name'];
        $category = (string) ($product['category_name'] ?? 'Electronic Components');
        $sku = (string) $product['sku'];
        $slug = (string) $product['slug'];
        $title = $this->limit("{$name} | NeoGiga", 65);
        $description = $this->limit("Review {$name}, source-backed specifications, technical details and RFQ availability for prototyping, maintenance and production sourcing through NeoGiga.", 158);
        $canonical = config('elecforest_import.public_url').'/en/products/'.$slug;
        $image = config('elecforest_import.public_url').'/images/products/neogiga-product-placeholder-2026.png';
        $updated = now()->toIso8601String();
        $disclaimer = (string) config('elecforest_import.advisory_disclaimer');
        $keywords = implode(', ', $this->keywords([
            $name, $category, $sku, ...($product['keywords'] ?? []),
            'electronics', 'engineering components', 'prototyping components', 'product sourcing', 'NeoGiga',
        ]));

        $breadcrumb = [
            '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Products', 'item' => config('elecforest_import.public_url').'/en/products'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $category],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => $canonical],
            ],
        ];
        $schema = [
            '@context' => 'https://schema.org', '@type' => 'Product', '@id' => $canonical.'#product',
            'name' => $name, 'sku' => $sku, 'description' => $description, 'url' => $canonical,
            'image' => [$image], 'category' => $category,
        ];

        return [
            'title' => $title, 'meta_title' => $title, 'meta_description' => $description,
            'meta_keywords' => $keywords, 'canonical_url' => $canonical, 'robots' => 'noindex,nofollow',
            'og_title' => $title, 'og_description' => $description, 'og_image' => $image,
            'twitter_title' => $title, 'twitter_description' => $description, 'twitter_image' => $image,
            'breadcrumb_schema' => $breadcrumb, 'product_schema' => $schema, 'schema_json' => $schema,
            'schema_type' => 'Product', 'source_notes' => $content['source_notes'],
            'confidence_level' => $content['confidence_level'], 'last_updated' => $updated,
            'advisory_disclaimer' => $disclaimer,
            'metadata' => [
                'source' => 'elecforest_deterministic_seo_generator', 'editable' => true,
                'generated_at' => $updated, 'draft_only' => true, 'faq_schema' => null,
                'offer_schema' => null, 'source_notes' => $content['source_notes'],
                'confidence_level' => $content['confidence_level'], 'last_updated' => $updated,
                'advisory_disclaimer' => $disclaimer,
            ],
        ];
    }

    private function limit(string $value, int $length): string
    {
        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');
        if (mb_strlen($value) <= $length) {
            return $value;
        }
        $truncated = rtrim(mb_substr($value, 0, $length));

        return trim(preg_replace('/\s+\S*$/u', '', $truncated) ?: $truncated);
    }

    /** @param list<mixed> $values @return list<string> */
    private function keywords(array $values): array
    {
        $keywords = [];
        $seen = [];
        foreach ($values as $value) {
            $keyword = trim(preg_replace('/\s+/u', ' ', str_replace([',', ';'], ' ', strip_tags((string) $value))) ?? '');
            $key = mb_strtolower($keyword);
            if ($keyword === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $keywords[] = Str::limit($keyword, 100, '');
            if (count($keywords) === 15) {
                break;
            }
        }

        return $keywords;
    }
}
