<?php

namespace App\Services\Api\Clients;

use App\Services\Api\Contracts\SupplierApiClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Class AdafruitApiClient
 * 
 * API client for Adafruit Industries.
 * 
 * @see https://www.adafruit.com/
 */
class AdafruitApiClient implements SupplierApiClientInterface
{
    protected string $baseUrl = 'https://api.adafruit.com/v1';
    protected ?string $apiKey = null;
    protected ?string $apiSecret = null;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->apiKey = config('services.suppliers.adafruit.api_key');
        $this->apiSecret = config('services.suppliers.adafruit.api_secret');
    }

    public function getSupplierName(): string
    {
        return 'Adafruit';
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function authenticate(): bool
    {
        try {
            $response = Http::post($this->baseUrl . '/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->apiKey,
                'client_secret' => $this->apiSecret,
            ]);

            if ($response->successful()) {
                $this->accessToken = $response->json('access_token');
                Log::info('Adafruit API authentication successful');
                return true;
            }

            Log::error('Adafruit API authentication failed', ['status' => $response->status()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Adafruit API authentication error', ['error' => $e->getMessage()]);
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
        // Adafruit is primarily their own brand, but may have partner brands
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
            Log::warning("Adafruit product not found: {$identifier}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function downloadFile(string $url, string $destination): bool
    {
        try {
            $response = Http::timeout(60)->get($url);
            
            if ($response->successful()) {
                file_put_contents($destination, $response->body());
                Log::info("File downloaded from Adafruit: {$url}");
                return true;
            }

            Log::error("Failed to download file from Adafruit: {$url}", ['status' => $response->status()]);
            return false;
        } catch (\Exception $e) {
            Log::error("Error downloading file from Adafruit: {$url}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function healthCheck(): bool
    {
        try {
            $response = Http::get($this->baseUrl . '/health');
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
            throw new RuntimeException('Failed to authenticate with Adafruit API');
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
            "Adafruit API request failed: {$response->status()} - {$response->body()}"
        );
    }

    /**
     * Parse Adafruit-specific product data into normalized format.
     */
    public function normalizeProduct(array $rawProduct): array
    {
        return [
            'name' => $rawProduct['name'] ?? '',
            'description' => $rawProduct['description'] ?? '',
            'mpn' => $rawProduct['mpn'] ?? $rawProduct['sku'] ?? '',
            'sku' => $rawProduct['sku'] ?? '',
            'upc' => $rawProduct['upc'] ?? null,
            'brand' => $rawProduct['brand']['name'] ?? 'Adafruit',
            'category' => $rawProduct['category']['name'] ?? 'Uncategorized',
            'subcategory' => $rawProduct['subcategory']['name'] ?? null,
            'price' => $rawProduct['price']['amount'] ?? 0,
            'currency' => $rawProduct['price']['currency'] ?? 'USD',
            'stock' => $rawProduct['inventory']['quantity'] ?? 0,
            'images' => collect($rawProduct['images'] ?? [])
                ->pluck('url')
                ->toArray(),
            'datasheets' => collect($rawProduct['documents'] ?? [])
                ->where('type', 'datasheet')
                ->pluck('url')
                ->toArray(),
            'specifications' => $rawProduct['specs'] ?? [],
            'features' => $rawProduct['features'] ?? [],
            'compatible_boards' => $rawProduct['compatibility'] ?? [],
            'libraries' => $rawProduct['libraries'] ?? [],
            'github_examples' => $rawProduct['examples'] ?? [],
            'created_at' => $rawProduct['created_at'] ?? now(),
            'updated_at' => $rawProduct['updated_at'] ?? now(),
        ];
    }
}
