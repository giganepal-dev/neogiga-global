<?php

namespace App\Services\Importers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WaveshareImporter extends BaseImporter
{
    public function getSupplierSlug(): string
    {
        return 'waveshare';
    }

    protected function getSupplierName(): string
    {
        return 'Waveshare';
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

    protected string $supplierCode = 'waveshare';
    protected string $supplierName = 'Waveshare';
    protected int $supplierTier = 1;
    protected string $baseUrl = 'https://www.waveshare.com';
    protected string $apiUrl = 'https://api.waveshare.com'; // Hypothetical API endpoint

    /**
     * Fetch categories from Waveshare
     */
    public function fetchCategories(): array
    {
        $categories = [];
        
        // Waveshare categories: Displays, E-Paper, HATs, Robotics, Industrial Modules
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
     * Fetch brands from Waveshare
     */
    public function fetchBrands(): array
    {
        return [
            [
                'name' => 'Waveshare',
                'slug' => 'waveshare',
                'logo_url' => 'https://cdn.waveshare.com/logo.png',
                'description' => 'Displays, HATs, and industrial modules',
            ],
            [
                'name' => 'Raspberry Pi',
                'slug' => 'raspberry-pi',
                'description' => 'Single-board computers',
            ],
            [
                'name' => 'Arduino',
                'slug' => 'arduino',
                'description' => 'Open-source electronics platform',
            ],
            [
                'name' => 'STM32',
                'slug' => 'stm32',
                'description' => 'STMicroelectronics ARM Cortex-M MCUs',
            ],
        ];
    }

    /**
     * Fetch products from Waveshare
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
     * Normalize Waveshare product data to our schema
     */
    public function normalizeProduct(array $product): array
    {
        return [
            'external_id' => $product['id'],
            'name' => $product['name'],
            'slug' => Str::slug($product['name']),
            'sku' => $product['sku'] ?? 'WS-' . $product['id'],
            'mpn' => $product['mpn'] ?? null,
            'upc' => $product['upc'] ?? null,
            'ean' => $product['ean'] ?? null,
            'brand_name' => $product['brand']['name'] ?? 'Waveshare',
            'category_slug' => $this->mapCategory($product['category_id']),
            'short_description' => $product['short_description'] ?? null,
            'full_description' => $product['description'] ?? null,
            'specifications' => $this->extractSpecifications($product),
            'features' => $product['features'] ?? [],
            'applications' => $product['applications'] ?? [],
            'compatible_boards' => $product['compatible_boards'] ?? [],
            'price_usd' => $product['price']['usd'] ?? 0,
            'currency' => 'USD',
            'stock_quantity' => $product['stock']['quantity'] ?? 0,
            'is_available' => $product['in_stock'] ?? false,
            'images' => $this->extractImages($product),
            'datasheet_url' => $product['datasheet_url'] ?? null,
            'manual_url' => $product['wiki_url'] ?? null,
            'github_examples' => $product['github_examples'] ?? [],
            'documentation_links' => $product['wiki_links'] ?? [],
            'libraries' => $this->extractLibraries($product),
            'country_of_origin' => $product['country_of_origin'] ?? 'China',
            'weight' => $product['weight'] ?? null,
            'dimensions' => $product['dimensions'] ?? null,
        ];
    }

    /**
     * Extract specifications with focus on display/industrial specs
     */
    protected function extractSpecifications(array $product): array
    {
        $specs = $product['specs'] ?? [];
        
        // Add Waveshare-specific specs
        if (isset($product['display_type'])) {
            $specs['display_type'] = $product['display_type'];
        }
        if (isset($product['resolution'])) {
            $specs['resolution'] = $product['resolution'];
        }
        if (isset($product['interface'])) {
            $specs['interface'] = $product['interface'];
        }
        if (isset($product['operating_voltage'])) {
            $specs['operating_voltage'] = $product['operating_voltage'];
        }

        return $specs;
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
                    'type' => $lib['platform'],
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
        return 'uncategorized';
    }

    /**
     * Get supplier-specific configuration
     */
    public function getConfig(): array
    {
        return [
            'api_key' => config('services.waveshare.api_key'),
            'rate_limit' => 8,
            'batch_size' => 100,
            'retry_attempts' => 3,
        ];
    }
}
