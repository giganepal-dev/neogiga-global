<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIRecommendationEngine
{
    protected $apiKey;
    protected $endpoint;

    public function __construct()
    {
        $this->apiKey = config('services.ai.api_key');
        $this->endpoint = config('services.ai.endpoint', 'https://api.openai.com/v1/chat/completions');
    }

    /**
     * Get personalized product recommendations based on user behavior
     */
    public function getRecommendations($userId, $limit = 10)
    {
        try {
            // Get user's browsing and purchase history
            $userHistory = $this->getUserHistory($userId);
            
            // Generate AI-powered recommendations
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post($this->endpoint, [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an e-commerce recommendation engine for engineering products.'
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'action' => 'recommend_products',
                            'user_history' => $userHistory,
                            'limit' => $limit
                        ])
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000
            ]);

            if ($response->successful()) {
                return $this->parseRecommendations($response->json());
            }

            Log::warning('AI recommendation failed', ['error' => $response->body()]);
            return $this->getFallbackRecommendations($userId, $limit);

        } catch (\Exception $e) {
            Log::error('AI recommendation error', ['error' => $e->getMessage()]);
            return $this->getFallbackRecommendations($userId, $limit);
        }
    }

    /**
     * Get smart search results with AI enhancement
     */
    public function smartSearch($query, $filters = [])
    {
        try {
            // Enhance query with AI
            $enhancedQuery = $this->enhanceSearchQuery($query);
            
            // Perform search with enhanced query
            return [
                'original_query' => $query,
                'enhanced_query' => $enhancedQuery,
                'results' => $this->executeSearch($enhancedQuery, $filters)
            ];

        } catch (\Exception $e) {
            Log::error('AI smart search error', ['error' => $e->getMessage()]);
            return [
                'original_query' => $query,
                'enhanced_query' => $query,
                'results' => $this->executeSearch($query, $filters)
            ];
        }
    }

    /**
     * Predict optimal pricing based on market data
     */
    public function predictOptimalPrice($productId, $competitorPrices = [])
    {
        try {
            $product = \App\Models\Product::findOrFail($productId);
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post($this->endpoint, [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a pricing optimization expert for engineering products.'
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'product' => $product->only(['name', 'description', 'category', 'cost_price']),
                            'competitor_prices' => $competitorPrices,
                            'goal' => 'maximize_profit_while_staying_competitive'
                        ])
                    ]
                ]
            ]);

            if ($response->successful()) {
                return $this->parsePricingRecommendation($response->json());
            }

            return null;

        } catch (\Exception $e) {
            Log::error('AI pricing prediction error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Analyze customer sentiment from reviews
     */
    public function analyzeSentiment($text)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post($this->endpoint, [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Analyze the sentiment of customer reviews. Return: positive, neutral, or negative.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $text
                    ]
                ],
                'max_tokens' => 50
            ]);

            if ($response->successful()) {
                return $this->parseSentiment($response->json());
            }

            return 'neutral';

        } catch (\Exception $e) {
            Log::error('AI sentiment analysis error', ['error' => $e->getMessage()]);
            return 'neutral';
        }
    }

    /**
     * Get user browsing and purchase history
     */
    protected function getUserHistory($userId)
    {
        $user = \App\Models\User::with(['orders.products', 'wishlist', 'viewedProducts'])->find($userId);
        
        if (!$user) {
            return [];
        }

        return [
            'past_purchases' => $user->orders->pluck('products')->flatten()->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category,
                'price' => $p->price
            ])->toArray(),
            'wishlist' => $user->wishlist->map(fn($item) => $item->product_id)->toArray(),
            'viewed' => $user->viewedProducts->take(20)->map(fn($p) => [
                'id' => $p->id,
                'category' => $p->category
            ])->toArray()
        ];
    }

    /**
     * Enhance search query using AI
     */
    protected function enhanceSearchQuery($query)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post($this->endpoint, [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Expand and improve this search query for engineering products. Return only the enhanced query.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $query
                    ]
                ],
                'max_tokens' => 100
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return trim($data['choices'][0]['message']['content'] ?? $query);
            }
        } catch (\Exception $e) {
            Log::error('Query enhancement error', ['error' => $e->getMessage()]);
        }

        return $query;
    }

    /**
     * Execute actual database search
     */
    protected function executeSearch($query, $filters = [])
    {
        return \App\Models\Product::search($query)
            ->when(!empty($filters['category']), fn($q) => $q->where('category', $filters['category']))
            ->when(!empty($filters['min_price']), fn($q) => $q->where('price', '>=', $filters['min_price']))
            ->when(!empty($filters['max_price']), fn($q) => $q->where('price', '<=', $filters['max_price']))
            ->limit(20)
            ->get();
    }

    /**
     * Fallback recommendations when AI is unavailable
     */
    protected function getFallbackRecommendations($userId, $limit)
    {
        return \App\Models\Product::inRandomOrder()->limit($limit)->get();
    }

    /**
     * Parse AI response for recommendations
     */
    protected function parseRecommendations($response)
    {
        // Extract product IDs from AI response
        $content = $response['choices'][0]['message']['content'] ?? '';
        preg_match_all('/\d+/', $content, $matches);
        
        $productIds = array_slice($matches[0], 0, 10);
        
        if (empty($productIds)) {
            return $this->getFallbackRecommendations(null, 10);
        }

        return \App\Models\Product::whereIn('id', $productIds)->get();
    }

    /**
     * Parse pricing recommendation
     */
    protected function parsePricingRecommendation($response)
    {
        $content = $response['choices'][0]['message']['content'] ?? '';
        preg_match('/\$?(\d+(\.\d{2})?)/', $content, $matches);
        
        return $matches[1] ?? null;
    }

    /**
     * Parse sentiment analysis result
     */
    protected function parseSentiment($response)
    {
        $content = strtolower($response['choices'][0]['message']['content'] ?? 'neutral');
        
        if (str_contains($content, 'positive')) return 'positive';
        if (str_contains($content, 'negative')) return 'negative';
        
        return 'neutral';
    }
}
