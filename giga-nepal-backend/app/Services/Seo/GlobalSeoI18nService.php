<?php

namespace App\Services\Seo;

use Illuminate\Support\Str;

class GlobalSeoI18nService
{
    public function prefixes(): array
    {
        return array_keys(config('neogiga_global.prefixes', []));
    }

    public function marketplace(string $prefix): array
    {
        return config("neogiga_global.prefixes.{$prefix}", config('neogiga_global.prefixes.en', []));
    }

    public function hreflang(string $path = '/'): array
    {
        $path = '/' . ltrim($this->stripLocalePrefix($path), '/');
        $path = $path === '/' ? '' : $path;
        $links = [[
            'hreflang' => 'x-default',
            'url' => rtrim((string) config('neogiga_global.x_default'), '/') . $path,
        ]];

        foreach (config('neogiga_global.prefixes', []) as $prefix => $marketplace) {
            $links[] = [
                'hreflang' => strtolower($marketplace['locale'] ?? 'en'),
                'url' => 'https://neogiga.com/' . $prefix . $path,
            ];
        }

        return $links;
    }

    public function productSeo(string $prefix, array $product): array
    {
        $marketplace = $this->marketplace($prefix);
        $replacements = [
            '{mpn}' => (string) ($product['mpn'] ?? $product['sku'] ?? 'Product'),
            '{name}' => (string) ($product['name'] ?? $product['mpn'] ?? 'Product'),
            '{country}' => (string) ($marketplace['country'] ?? 'Global'),
            '{brand}' => (string) ($marketplace['brand'] ?? 'NeoGiga'),
            '{category}' => (string) ($product['category'] ?? 'Electronics'),
            '{manufacturer}' => (string) ($product['manufacturer'] ?? $product['brand'] ?? 'Manufacturer'),
        ];

        $slug = (string) ($product['slug'] ?? Str::slug($replacements['{name}']));
        $indexable = (bool) ($product['indexable'] ?? true);

        return [
            'locale' => $marketplace['locale'] ?? 'en',
            'currency' => $marketplace['currency'] ?? 'USD',
            'title' => Str::limit(strtr(config('neogiga_global.seo_templates.product_title'), $replacements), 60, ''),
            'description' => Str::limit(strtr(config('neogiga_global.seo_templates.product_description'), $replacements), 158, ''),
            'canonical' => 'https://neogiga.com/' . $prefix . '/products/' . $slug,
            'robots' => $indexable ? 'index,follow' : 'noindex,nofollow',
            'structured_data_type' => 'Product',
        ];
    }

    public function stripLocalePrefix(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $first = trim(strtok(ltrim($path, '/'), '/') ?: '', '/');

        return in_array($first, $this->prefixes(), true)
            ? '/' . ltrim(substr($path, strlen($first) + 1), '/')
            : $path;
    }
}
