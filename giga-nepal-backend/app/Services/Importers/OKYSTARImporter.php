<?php

namespace App\Services\Importers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OKYSTARImporter extends BaseImporter
{
    protected string $supplierCode = 'okystar';
    protected string $supplierName = 'OKYSTAR';
    protected int $supplierTier = 1;
    protected string $baseUrl = 'https://www.okystar.com';
    protected string $apiUrl = 'https://api.okystar.com';

    public function fetchCategories(): array
    {
        $categories = [];
        $response = Http::timeout(30)->get("{$this->apiUrl}/categories");
        
        if ($response->successful()) {
            foreach ($response->json() as $cat) {
                $categories[] = [
                    'name' => $cat['name'],
                    'slug' => Str::slug($cat['name']),
                    'external_id' => $cat['id'],
                    'parent_external_id' => $cat['parent_id'] ?? null,
                    'description' => $cat['description'] ?? null,
                ];
            }
        }
        return $categories;
    }

    public function fetchBrands(): array
    {
        return [
            ['name' => 'OKYSTAR', 'slug' => 'okystar', 'description' => 'Arduino, sensors, modules, robotics'],
            ['name' => 'Arduino', 'slug' => 'arduino', 'description' => 'Open-source electronics platform'],
        ];
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
            'sku' => $product['sku'] ?? 'OKY-' . $product['id'],
            'mpn' => $product['mpn'] ?? null,
            'brand_name' => $product['brand']['name'] ?? 'OKYSTAR',
            'category_slug' => $this->mapCategory($product['category_id']),
            'short_description' => $product['short_description'] ?? null,
            'full_description' => $product['description'] ?? null,
            'specifications' => $product['specs'] ?? [],
            'features' => $product['features'] ?? [],
            'applications' => $product['applications'] ?? [],
            'compatible_boards' => $product['compatible_boards'] ?? [],
            'price_usd' => $product['price']['usd'] ?? 0,
            'currency' => 'USD',
            'stock_quantity' => $product['stock']['quantity'] ?? 0,
            'is_available' => $product['in_stock'] ?? false,
            'images' => $this->extractImages($product),
            'datasheet_url' => $product['datasheet_url'] ?? null,
            'github_examples' => $product['github_examples'] ?? [],
            'libraries' => $this->extractLibraries($product),
            'country_of_origin' => $product['country_of_origin'] ?? 'China',
        ];
    }

    protected function extractImages(array $product): array
    {
        $images = [];
        foreach ($product['images'] ?? [] as $image) {
            $images[] = ['url' => $image['url'], 'alt' => $image['alt'] ?? $product['name'], 'is_primary' => $image['is_primary'] ?? false];
        }
        return $images;
    }

    protected function extractLibraries(array $product): array
    {
        $libraries = [];
        foreach ($product['libraries'] ?? [] as $lib) {
            $libraries[] = ['type' => $lib['platform'], 'name' => $lib['name'], 'url' => $lib['url']];
        }
        return $libraries;
    }

    protected function mapCategory(int $categoryId): string { return 'uncategorized'; }

    public function getConfig(): array
    {
        return ['api_key' => config('services.okystar.api_key'), 'rate_limit' => 10, 'batch_size' => 100];
    }
}
