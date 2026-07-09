<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AIRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_product_recommendations()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai/recommendations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'recommendations' => [
                    '*' => ['id', 'name', 'price']
                ]
            ]);
    }

    public function test_ai_smart_search()
    {
        $response = $this->getJson('/api/v1/ai/search?q=engineering+tools');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'original_query',
                'enhanced_query',
                'results'
            ]);
    }

    public function test_sentiment_analysis()
    {
        $response = $this->postJson('/api/v1/ai/sentiment', [
            'text' => 'This product is amazing! Best purchase ever.'
        ]);

        $response->assertStatus(200)
            ->assertJson(['sentiment' => 'positive']);
    }

    public function test_fallback_recommendations_when_ai_unavailable()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Simulate AI service failure
        config(['services.ai.api_key' => null]);

        $response = $this->getJson('/api/v1/ai/recommendations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'recommendations' => [
                    '*' => ['id', 'name']
                ]
            ]);
    }
}
