<?php

namespace App\Catalog\Ingestion\Suppliers;

use App\Catalog\Ingestion\Contracts\SupplierImporterInterface;
use App\Catalog\Ingestion\Normalizers\CatalogNormalizer;
use App\Catalog\Ingestion\Parsers\JsonLdExtractor;
use Illuminate\Support\Facades\Http;

class StructuredSupplierImporter implements SupplierImporterInterface
{
    public function __construct(private readonly JsonLdExtractor $jsonLd, private readonly CatalogNormalizer $normalizer) {}

    public function discover(array $definition, int $limit = 0): iterable
    {
        $seen = [];
        foreach ($definition['sitemap_urls'] ?? [] as $sitemap) {
            $response = $this->request($sitemap, $definition);
            if (! $response->successful() || ! str_contains(strtolower($response->header('Content-Type', '')), 'xml')) {
                continue;
            }
            preg_match_all('#<loc>\s*(.*?)\s*</loc>#is', $response->body(), $matches);
            foreach ($matches[1] ?? [] as $url) {
                $url = $this->normalizer->canonicalUrl(html_entity_decode(trim($url)));
                if (! isset($seen[$url])) {
                    $seen[$url] = true;
                    yield $url;
                    if ($limit > 0 && count($seen) >= $limit) {
                        return;
                    }
                }
            }
        }
    }

    public function parse(string $url, array $definition): array
    {
        $response = $this->request($url, $definition);
        if (! $response->successful()) {
            throw new \RuntimeException("Source returned HTTP {$response->status()} for {$url}");
        }
        $node = $this->jsonLd->product($response->body());
        $offers = (array) ($node['offers'] ?? []);
        $image = $node['image'] ?? null;
        $images = is_array($image) ? $image : ($image ? [$image] : []);

        return [
            'source_url' => $url,
            'canonical_url' => $this->normalizer->canonicalUrl((string) ($node['url'] ?? $url)),
            'source_product_id' => (string) ($node['productID'] ?? $node['sku'] ?? ''),
            'supplier_sku' => $node['sku'] ?? null,
            'mpn' => $node['mpn'] ?? null,
            'title' => $node['name'] ?? null,
            'brand' => is_array($node['brand'] ?? null) ? ($node['brand']['name'] ?? null) : ($node['brand'] ?? null),
            'manufacturer' => is_array($node['manufacturer'] ?? null) ? ($node['manufacturer']['name'] ?? null) : ($node['manufacturer'] ?? null),
            'source_status' => $offers['availability'] ?? null,
            'source_currency' => $offers['priceCurrency'] ?? null,
            'source_price' => isset($offers['price']) && is_numeric($offers['price']) ? (float) $offers['price'] : null,
            'description' => $node['description'] ?? null,
            'assets' => array_map(fn ($item) => ['asset_type' => 'image', 'original_url' => $item], $images),
            'raw_payload' => $node,
        ];
    }

    private function request(string $url, array $definition)
    {
        return Http::accept('application/xml, application/xhtml+xml, text/html;q=0.9')
            ->withUserAgent($definition['user_agent'])
            ->timeout((int) config('catalog_import.timeout'))
            ->connectTimeout((int) config('catalog_import.connect_timeout'))
            ->retry((int) config('catalog_import.retry_attempts'), 500, throw: false)
            ->get($url);
    }
}
