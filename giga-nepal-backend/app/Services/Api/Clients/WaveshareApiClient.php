<?php

namespace App\Services\Api\Clients;

use App\Services\Api\Contracts\SupplierApiClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Class WaveshareApiClient
 * 
 * API client for Waveshare Electronics.
 * Note: Waveshare may require web scraping if no official API is available.
 * 
 * @see https://www.waveshare.com/
 */
class WaveshareApiClient implements SupplierApiClientInterface
{
    protected string $baseUrl = 'https://api.waveshare.com/v1';
    protected ?string $apiKey = null;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->apiKey = config('services.suppliers.waveshare.api_key');
    }

    public function getSupplierName(): string
    {
        return 'Waveshare';
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function authenticate(): bool
    {
        try {
            $response = Http::post($this->baseUrl . '/auth', [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $this->accessToken = $response->json('access_token');
                Log::info('Waveshare API authentication successful');
                return true;
            }

            Log::error('Waveshare API authentication failed', ['status' => $response->status()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Waveshare API authentication error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function fetchCategories(int $page = 1, int $perPage = 50): array
    {
        return $this->makeRequest('GET', '/categories', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    public function fetchBrands(int $page = 1, int $perPage = 50): array
    {
        // Waveshare primarily sells their own brand
        return $this->makeRequest('GET', '/brands', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    public function fetchProducts(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $params = array_merge([
            'page' => $page,
            'per_page' => $perPage,
        ], $filters);

        return $this->makeRequest('GET', '/products', $params);
    }

    public function fetchProduct(string $identifier): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/products/{$identifier}");
            return $response['data'] ?? null;
        } catch (RuntimeException $e) {
            Log::warning("Waveshare product not found: {$identifier}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function downloadFile(string $url, string $destination): bool
    {
        try {
            $response = Http::timeout(60)->get($url);
            
            if ($response->successful()) {
                file_put_contents($destination, $response->body());
                Log::info("File downloaded from Waveshare: {$url}");
                return true;
            }

            Log::error("Failed to download file from Waveshare: {$url}", ['status' => $response->status()]);
            return false;
        } catch (\Exception $e) {
            Log::error("Error downloading file from Waveshare: {$url}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function healthCheck(): bool
    {
        try {
            $response = Http::get($this->baseUrl . '/status');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Make an authenticated API request.
     */
    protected function makeRequest(string $method, string $endpoint, array $params = []): array
    {
        if (!$this->accessToken && !$this->authenticate()) {
            throw new RuntimeException('Failed to authenticate with Waveshare API');
        }

        $url = $this->baseUrl . $endpoint;
        
        $response = match($method) {
            'GET' => Http::withToken($this->accessToken)
                ->timeout(30)
                ->get($url, $params),
            'POST' => Http::withToken($this->accessToken)
                ->timeout(30)
                ->post($url, $params),
            default => throw new RuntimeException("Unsupported HTTP method: {$method}")
        };

        if ($response->successful()) {
            return $response->json();
        }

        throw new RuntimeException(
            "Waveshare API request failed: {$response->status()} - {$response->body()}"
        );
    }

    /**
     * Parse Waveshare-specific product data into normalized format.
     * Special handling for displays, e-paper, HATs, and industrial modules.
     */
    public function normalizeProduct(array $rawProduct): array
    {
        $specs = $rawProduct['specifications'] ?? [];
        
        // Extract display-specific specs
        $displaySpecs = [];
        if (stripos($rawProduct['category'] ?? '', 'display') !== false || 
            stripos($rawProduct['category'] ?? '', 'e-paper') !== false) {
            $displaySpecs = [
                'screen_size' => $specs['screen_size'] ?? null,
                'resolution' => $specs['resolution'] ?? null,
                'interface' => $specs['interface'] ?? null,
                'touch_type' => $specs['touch_type'] ?? null,
                'brightness' => $specs['brightness'] ?? null,
            ];
        }

        // Extract HAT/module specs
        $moduleSpecs = [];
        if (stripos($rawProduct['name'] ?? '', 'HAT') !== false) {
            $moduleSpecs = [
                'compatible_models' => $specs['compatible_raspberry_pi_models'] ?? [],
                'gpio_pins' => $specs['gpio_pins'] ?? null,
                'power_supply' => $specs['power_supply'] ?? null,
            ];
        }

        return [
            'name' => $rawProduct['name'] ?? '',
            'description' => $rawProduct['description'] ?? '',
            'mpn' => $rawProduct['mpn'] ?? $rawProduct['sku'] ?? '',
            'sku' => $rawProduct['sku'] ?? '',
            'upc' => $rawProduct['upc'] ?? null,
            'brand' => $rawProduct['brand']['name'] ?? 'Waveshare',
            'category' => $rawProduct['category']['name'] ?? 'Uncategorized',
            'subcategory' => $rawProduct['subcategory']['name'] ?? null,
            'price' => $rawProduct['price']['amount'] ?? 0,
            'currency' => $rawProduct['price']['currency'] ?? 'USD',
            'stock' => $rawProduct['inventory']['quantity'] ?? 0,
            'images' => collect($rawProduct['images'] ?? [])
                ->pluck('url')
                ->toArray(),
            'datasheets' => collect($rawProduct['documents'] ?? [])
                ->whereIn('type', ['datasheet', 'manual', 'wiki'])
                ->pluck('url')
                ->toArray(),
            'specifications' => array_merge($specs, $displaySpecs, $moduleSpecs),
            'features' => $rawProduct['features'] ?? [],
            'compatible_boards' => $rawProduct['compatibility'] ?? [],
            'libraries' => $rawProduct['libraries'] ?? [],
            'github_examples' => $rawProduct['examples'] ?? [],
            'created_at' => $rawProduct['created_at'] ?? now(),
            'updated_at' => $rawProduct['updated_at'] ?? now(),
        ];
    }
}
