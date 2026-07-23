<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCommerceChatControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_create_session_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/ai-commerce/session');

        $response->assertStatus(401);
    }

    public function test_create_session(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ai-commerce/session');

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'active',
                ],
            ]);
    }

    public function test_chat_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/ai-commerce/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(401);
    }

    public function test_chat_requires_message(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ai-commerce/chat', []);

        $response->assertStatus(422);
    }

    public function test_chat_product_search(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ai-commerce/chat', [
                'message' => 'Find STM32F103',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'structured' => [
                        'type' => 'product_results',
                    ],
                ],
            ]);
    }

    public function test_chat_alternatives_request(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ai-commerce/chat', [
                'message' => 'Find alternatives for LM358',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'structured' => [
                        'type' => 'alternatives',
                    ],
                ],
            ]);
    }

    public function test_chat_general_help(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ai-commerce/chat', [
                'message' => 'Hello, what can you do?',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'structured' => [
                        'type' => 'help',
                    ],
                ],
            ]);
    }

    public function test_chat_bom_paste(): void
    {
        $bom = "MPN,Quantity\nSTM32F103,10\nLM358,5";

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ai-commerce/chat', [
                'message' => $bom,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'structured' => [
                        'type' => 'bom_results',
                    ],
                ],
            ]);
    }

    public function test_chat_with_session(): void
    {
        $sessionResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/ai-commerce/session');

        $sessionKey = $sessionResponse->json('data.session_key');

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ai-commerce/chat', [
                'message' => 'Hello',
                'session_key' => $sessionKey,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }
}
