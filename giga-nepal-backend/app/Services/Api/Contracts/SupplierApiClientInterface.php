<?php

namespace App\Services\Api\Contracts;

/**
 * Interface SupplierApiClientInterface
 * 
 * Contract for all supplier API clients.
 */
interface SupplierApiClientInterface
{
    /**
     * Get the supplier name.
     */
    public function getSupplierName(): string;

    /**
     * Get the base API URL.
     */
    public function getBaseUrl(): string;

    /**
     * Authenticate with the supplier API.
     */
    public function authenticate(): bool;

    /**
     * Fetch categories from the supplier.
     * 
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function fetchCategories(int $page = 1, int $perPage = 50): array;

    /**
     * Fetch brands from the supplier.
     * 
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function fetchBrands(int $page = 1, int $perPage = 50): array;

    /**
     * Fetch products from the supplier.
     * 
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function fetchProducts(array $filters = [], int $page = 1, int $perPage = 50): array;

    /**
     * Fetch a single product by ID or SKU.
     * 
     * @param string $identifier
     * @return array|null
     */
    public function fetchProduct(string $identifier): ?array;

    /**
     * Download a file (image, datasheet, etc.).
     * 
     * @param string $url
     * @param string $destination
     * @return bool
     */
    public function downloadFile(string $url, string $destination): bool;

    /**
     * Check if the API connection is healthy.
     */
    public function healthCheck(): bool;
}
