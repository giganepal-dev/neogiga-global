<?php

namespace App\Services\Importers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdafruitImporter extends BaseImporter
{
    public function getSupplierSlug(): string
    {
        return 'adafruit';
    }

    protected function getSupplierName(): string
    {
        return 'Adafruit';
    }

    protected function getSupplierTier(): string
    {
        return '1';
    }

    protected function getSupplierDescription(): ?string
    {
        return null;
    }

    protected function getSupplierWebsite(): ?string
    {
        return null;
    }

    protected function getSupplierCountry(): ?string
    {
        return null;
    }

    protected string $supplierCode = 'adafruit';
    protected string $supplierName = 'Adafruit';
    protected int $supplierTier = 1;
    protected string $baseUrl = 'https://www.adafruit.com';
    protected string $apiUrl = 'https://api.adafruit.com'; // Hypothetical API endpoint

    /**
     * Fetch categories from Adafruit
     */
    public function fetchCategories(): array
    {
        $categories = [];
        
        // Example: Fetch from API or scrape
        $response = Http::timeout(30)->get("{$this->apiUrl}/categories");
        
        if ($response->successful()) {
            $data = $response->json();
            
            foreach ($data as $cat) {
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

    /**
     * Fetch brands from Adafruit
     */
    public function fetchBrands(): array
    {
        // Adafruit primarily sells their own brand + Arduino, Raspberry Pi, etc.
        return [
            [
                'name' => 'Adafruit',
                'slug' => 'adafruit',
                'logo_url' => 'https://cdn.adafruit.com/logo.png',
                'description' => 'Open-source hardware company',
            ],
            [
                'name' => 'Arduino',
                'slug' => 'arduino',
                'description' => 'Open-source electronics platform',
            ],
            [
                'name' => 'Raspberry Pi',
                'slug' => 'raspberry-pi',
                'description' => 'Single-board computers',
            ],
            [
                'name' => 'Espressif',
                'slug' => 'espressif',
                'description' => 'ESP32 and ESP8266 chips',
            ],
        ];
    }

    /**
     * Fetch products from Adafruit
     */
    public function fetchProducts(array $options = []): \Generator
    {
        $products = [];
        
        $response = Http::timeout(60)->get("{$this->apiUrl}/products", [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            foreach ($data['products'] ?? [] as $product) {
                $products[] = $this->normalizeProduct($product);
            }
        }

        yield from $products;
    }

    /**
     * Normalize Adafruit product data to our schema
     */
    public function normalizeProduct(array $product): array
    {
        return [
            'external_id' => $product['id'],
            'name' => $product['name'],
            'slug' => Str::slug($product['name']),
            'sku' => $product['sku'] ?? 'ADA-' . $product['id'],
            'mpn' => $product['mpn'] ?? null,
            'upc' => $product['upc'] ?? null,
            'ean' => $product['ean'] ?? null,
            'brand_name' => $product['brand']['name'] ?? 'Adafruit',
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
            'manual_url' => $product['manual_url'] ?? null,
            'github_examples' => $product['github_examples'] ?? [],
            'documentation_links' => $product['learn_guides'] ?? [],
            'libraries' => $this->extractLibraries($product),
            'country_of_origin' => $product['country_of_origin'] ?? 'USA',
            'weight' => $product['weight'] ?? null,
            'dimensions' => $product['dimensions'] ?? null,
        ];
    }

    /**
     * Extract images from product data
     */
    protected function extractImages(array $product): array
    {
        $images = [];
        
        if (isset($product['images'])) {
            foreach ($product['images'] as $image) {
                $images[] = [
                    'url' => $image['url'],
                    'alt' => $image['alt'] ?? $product['name'],
                    'position' => $image['position'] ?? 0,
                    'is_primary' => $image['is_primary'] ?? false,
                ];
            }
        }

        return $images;
    }

    /**
     * Extract code libraries from product data
     */
    protected function extractLibraries(array $product): array
    {
        $libraries = [];
        
        if (isset($product['libraries'])) {
            foreach ($product['libraries'] as $lib) {
                $libraries[] = [
                    'type' => $lib['platform'], // arduino, circuitpython, platformio
                    'name' => $lib['name'],
                    'url' => $lib['url'],
                    'repository' => $lib['repository'] ?? null,
                ];
            }
        }

        return $libraries;
    }

    /**
     * Map external category ID to our slug
     */
    protected function mapCategory(int $categoryId): string
    {
        // Implement category mapping logic
        // This would typically query the imported categories
        return 'uncategorized';
    }

    /**
     * Get supplier-specific configuration
     */
    public function getConfig(): array
    {
        return [
            'api_key' => config('services.adafruit.api_key'),
            'rate_limit' => 10, // requests per second
            'batch_size' => 100,
            'retry_attempts' => 3,
        ];
    }
}
