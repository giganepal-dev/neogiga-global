<?php

namespace App\Services\Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ElasticsearchService
{
    private Client $client;
    private string $indexName;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts(config('elasticsearch.hosts'))
            ->setBasicAuthentication(
                config('elasticsearch.auth.username'),
                config('elasticsearch.auth.password')
            )
            ->setSSLVerification(config('elasticsearch.ssl_verification'))
            ->build();

        $this->indexName = config('elasticsearch.index_prefix') . '_products';
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    /**
     * Create the product index with mapping.
     */
    public function createIndex(): array
    {
        $params = [
            'index' => $this->indexName,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'analyzer' => [
                            'product_analyzer' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase', 'asciifolding'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'properties' => [
                        'product_id' => ['type' => 'integer'],
                        'name' => ['type' => 'text', 'analyzer' => 'product_analyzer', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'slug' => ['type' => 'keyword'],
                        'sku' => ['type' => 'keyword'],
                        'mpn' => ['type' => 'keyword'],
                        'manufacturer_name' => ['type' => 'text', 'analyzer' => 'product_analyzer', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'brand_name' => ['type' => 'text', 'analyzer' => 'product_analyzer', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'category_name' => ['type' => 'text', 'analyzer' => 'product_analyzer', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'description' => ['type' => 'text', 'analyzer' => 'product_analyzer'],
                        'short_description' => ['type' => 'text', 'analyzer' => 'product_analyzer'],
                        'base_price' => ['type' => 'float'],
                        'sale_price' => ['type' => 'float'],
                        'stock_quantity' => ['type' => 'integer'],
                        'status' => ['type' => 'keyword'],
                        'approval_status' => ['type' => 'keyword'],
                        'visibility_status' => ['type' => 'keyword'],
                        'marketplace_id' => ['type' => 'integer'],
                        'currency_code' => ['type' => 'keyword'],
                        'image_url' => ['type' => 'keyword', 'index' => false],
                        'tags' => ['type' => 'keyword'],
                        'created_at' => ['type' => 'date'],
                        'updated_at' => ['type' => 'date'],
                    ],
                ],
            ],
        ];

        return $this->client->indices()->create($params)->asArray();
    }

    /**
     * Delete the product index.
     */
    public function deleteIndex(): array
    {
        if (! $this->client->indices()->exists(['index' => $this->indexName])->asBool()) {
            return ['deleted' => false];
        }

        return $this->client->indices()->delete(['index' => $this->indexName])->asArray();
    }

    /**
     * Check if index exists.
     */
    public function indexExists(): bool
    {
        return $this->client->indices()->exists(['index' => $this->indexName])->asBool();
    }

    /**
     * Index a single product.
     */
    public function indexProduct(array $product): array
    {
        $params = [
            'index' => $this->indexName,
            'id' => $product['product_id'],
            'body' => $product,
        ];

        return $this->client->index($params)->asArray();
    }

    /**
     * Bulk index products.
     */
    public function bulkIndex(array $products): array
    {
        $params = ['body' => []];

        foreach ($products as $product) {
            $params['body'][] = ['index' => ['_index' => $this->indexName, '_id' => $product['product_id']]];
            $params['body'][] = $product;
        }

        return $this->client->bulk($params)->asArray();
    }

    /**
     * Search products.
     */
    public function search(array $params): array
    {
        $body = [
            'from' => $params['from'] ?? 0,
            'size' => $params['size'] ?? 20,
        ];

        $must = [];
        $filter = [];

        // Text search
        if (! empty($params['q'])) {
            $must[] = [
                'multi_match' => [
                    'query' => $params['q'],
                    'fields' => ['name^3', 'sku^2', 'mpn^2', 'manufacturer_name^1.5', 'brand_name^1.5', 'description'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ];
        }

        // Category filter
        if (! empty($params['category'])) {
            $filter[] = ['term' => ['category_name.keyword' => $params['category']]];
        }

        // Brand filter
        if (! empty($params['brand'])) {
            $filter[] = ['term' => ['brand_name.keyword' => $params['brand']]];
        }

        // Manufacturer filter
        if (! empty($params['manufacturer'])) {
            $filter[] = ['term' => ['manufacturer_name.keyword' => $params['manufacturer']]];
        }

        // Price range
        if (isset($params['min_price']) || isset($params['max_price'])) {
            $range = ['base_price' => []];
            if (isset($params['min_price'])) {
                $range['base_price']['gte'] = $params['min_price'];
            }
            if (isset($params['max_price'])) {
                $range['base_price']['lte'] = $params['max_price'];
            }
            $filter[] = ['range' => $range];
        }

        // Stock filter
        if (($params['stock'] ?? '') === 'in') {
            $filter[] = ['range' => ['stock_quantity' => ['gt' => 0]]];
        }

        // Status filter
        $filter[] = ['term' => ['status' => 'active']];

        if ($must !== []) {
            $body['query']['bool']['must'] = $must;
        }
        if ($filter !== []) {
            $body['query']['bool']['filter'] = $filter;
        }

        // Aggregations (facets)
        $body['aggs'] = [
            'categories' => ['terms' => ['field' => 'category_name.keyword', 'size' => 20]],
            'brands' => ['terms' => ['field' => 'brand_name.keyword', 'size' => 20]],
            'manufacturers' => ['terms' => ['field' => 'manufacturer_name.keyword', 'size' => 20]],
            'price_ranges' => [
                'range' => [
                    'field' => 'base_price',
                    'ranges' => [
                        ['key' => '0-10', 'to' => 10],
                        ['key' => '10-50', 'from' => 10, 'to' => 50],
                        ['key' => '50-100', 'from' => 50, 'to' => 100],
                        ['key' => '100+', 'from' => 100],
                    ],
                ],
            ],
        ];

        $response = $this->client->search(['index' => $this->indexName, 'body' => $body])->asArray();

        return [
            'total' => $response['hits']['total']['value'] ?? 0,
            'hits' => array_map(fn ($hit) => array_merge($hit['_source'], ['_score' => $hit['_score']]), $response['hits']['hits'] ?? []),
            'aggregations' => $response['aggregations'] ?? [],
        ];
    }

    /**
     * Get index stats.
     */
    public function stats(): array
    {
        if (! $this->indexExists()) {
            return ['exists' => false, 'count' => 0];
        }

        $count = $this->client->count(['index' => $this->indexName])->asArray();
        $stats = $this->client->indices()->stats(['index' => $this->indexName])->asArray();

        return [
            'exists' => true,
            'count' => $count['count'] ?? 0,
            'size_bytes' => $stats['indices'][$this->indexName]['total']['store']['size_in_bytes'] ?? 0,
            'searches_total' => $stats['indices'][$this->indexName]['total']['searches']['total'] ?? 0,
        ];
    }

    /**
     * Sync products from PostgreSQL to Elasticsearch.
     */
    public function syncProducts(int $chunkSize = 500): array
    {
        $stats = ['indexed' => 0, 'errors' => 0, 'skipped' => 0];
        $offset = 0;

        do {
            $products = DB::table('products')
                ->leftJoin('product_brands', 'product_brands.id', '=', 'products.brand_id')
                ->leftJoin('product_categories', 'product_categories.id', '=', 'products.category_id')
                ->select(
                    'products.id as product_id',
                    'products.name',
                    'products.slug',
                    'products.sku',
                    'products.mpn',
                    'products.manufacturer_name',
                    'product_brands.name as brand_name',
                    'product_categories.name as category_name',
                    'products.description',
                    'products.short_description',
                    'products.base_price',
                    'products.sale_price',
                    'products.stock_quantity',
                    'products.status',
                    'products.created_at',
                    'products.updated_at'
                )
                ->where('products.status', 'active')
                ->orderBy('products.id')
                ->offset($offset)
                ->limit($chunkSize)
                ->get()
                ->map(fn ($p) => (array) $p)
                ->toArray();

            if ($products === []) {
                break;
            }

            try {
                $result = $this->bulkIndex($products);
                $stats['indexed'] += count($products);
                if (! empty($result['errors'])) {
                    $stats['errors']++;
                    Log::warning('Elasticsearch bulk index errors', ['errors' => collect($result['items'])->pluck('index.error')->filter()->toArray()]);
                }
            } catch (\Throwable $e) {
                Log::error('Elasticsearch sync error', ['offset' => $offset, 'error' => $e->getMessage()]);
                $stats['errors']++;
            }

            $offset += $chunkSize;
        } while (count($products) === $chunkSize);

        // Refresh index
        $this->client->indices()->refresh(['index' => $this->indexName]);

        return $stats;
    }
}
