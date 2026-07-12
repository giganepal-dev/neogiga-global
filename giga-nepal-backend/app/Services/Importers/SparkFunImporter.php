<?php

namespace App\Services\Importers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SparkFunImporter extends BaseImporter
{
    protected string $supplierCode = 'sparkfun';
    protected string $supplierName = 'SparkFun';
    protected int $supplierTier = 1;
    protected string $baseUrl = 'https://www.sparkfun.com';
    protected string $apiUrl = 'https://api.sparkfun.com';

    public function fetchCategories(): array
    {
        $categories = [];
        $response = Http::timeout(30)->get("{$this->apiUrl}/categories");
        if ($response->successful()) {
            foreach ($response->json() as $cat) {
                $categories[] = ['name' => $cat['name'], 'slug' => Str::slug($cat['name']), 'external_id' => $cat['id']];
            }
        }
        return $categories;
    }

    public function fetchBrands(): array
    {
        return [['name' => 'SparkFun', 'slug' => 'sparkfun', 'description' => 'Sensors, development boards, education']];
    }

    public function fetchProducts(int $page = 1, int $perPage = 100): array
    {
        $products = [];
        $response = Http::timeout(60)->get("{$this->apiUrl}/products", ['page' => $page, 'per_page' => $perPage]);
        if ($response->successful()) {
            foreach ($response->json()['products'] ?? [] as $product) {
                $products[] = $this->normalizeProduct($product);
            }
        }
        return $products;
    }

    protected function normalizeProduct(array $product): array
    {
        return [
            'external_id' => $product['id'],
            'name' => $product['name'],
            'slug' => Str::slug($product['name']),
            'sku' => $product['sku'] ?? 'SF-' . $product['id'],
            'mpn' => $product['mpn'] ?? null,
            'brand_name' => 'SparkFun',
            'category_slug' => 'uncategorized',
            'short_description' => $product['short_description'] ?? null,
            'full_description' => $product['description'] ?? null,
            'specifications' => $product['specs'] ?? [],
            'features' => $product['features'] ?? [],
            'price_usd' => $product['price']['usd'] ?? 0,
            'currency' => 'USD',
            'stock_quantity' => $product['stock']['quantity'] ?? 0,
            'is_available' => $product['in_stock'] ?? false,
            'images' => $this->extractImages($product),
            'datasheet_url' => $product['datasheet_url'] ?? null,
            'github_examples' => $product['github_examples'] ?? [],
            'libraries' => $this->extractLibraries($product),
        ];
    }

    protected function extractImages(array $product): array
    {
        $images = [];
        foreach ($product['images'] ?? [] as $img) {
            $images[] = ['url' => $img['url'], 'alt' => $img['alt'] ?? $product['name'], 'is_primary' => $img['is_primary'] ?? false];
        }
        return $images;
    }

    protected function extractLibraries(array $product): array
    {
        $libs = [];
        foreach ($product['libraries'] ?? [] as $lib) {
            $libs[] = ['type' => $lib['platform'], 'name' => $lib['name'], 'url' => $lib['url']];
        }
        return $libs;
    }

    protected function mapCategory(int $categoryId): string
    {
        return 'uncategorized';
    }

    public function getConfig(): array
    {
        return ['api_key' => config('services.sparkfun.api_key'), 'rate_limit' => 10, 'batch_size' => 100];
    }
}
