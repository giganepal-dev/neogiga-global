<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyAiCommerceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureCommerceAiTables();
    }

    public function test_authenticated_legacy_ai_routes_delegate_to_the_bounded_commerce_ai_service(): void
    {
        [$user, $token] = $this->apiUser('legacy-ai@example.test');

        $session = $this->withToken($token)
            ->postJson('/api/v1/ai/session')
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.engine', 'local_rule_engine');

        $sessionKey = $session->json('data.session_key');

        $this->withToken($token)
            ->postJson('/api/v1/ai/message', [
                'prompt' => 'Build a 4WD robot car',
                'session_key' => $sessionKey,
            ])
            ->assertOk()
            ->assertJsonPath('data.bom.disclaimer', 'Advisory only. No order, payment, or live stock reservation has been created.')
            ->assertJsonPath('data.bom.confidence_level', 'medium');

        $this->withToken($token)
            ->postJson('/api/v1/ai/build-bom', [
                'prompt' => 'Suggest parts for a smart irrigation system',
                'session_key' => $sessionKey,
            ])
            ->assertCreated()
            ->assertJsonPath('data.source_notes', 'Local NeoGiga rule engine. Product catalog matching is attempted when products exist; unavailable items remain generic suggestions.')
            ->assertJsonStructure(['data' => ['last_updated', 'items']]);

        $this->assertDatabaseHas('commerce_ai_sessions', [
            'session_key' => $sessionKey,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('commerce_ai_bom_requests', ['user_id' => $user->id]);
    }

    public function test_legacy_ai_session_key_cannot_be_reused_by_a_different_account(): void
    {
        [, $ownerToken] = $this->apiUser('legacy-ai-owner@example.test');
        [, $otherToken] = $this->apiUser('legacy-ai-other@example.test');

        $sessionKey = $this->withToken($ownerToken)
            ->postJson('/api/v1/ai/session')
            ->assertCreated()
            ->json('data.session_key');

        $this->withToken($otherToken)
            ->postJson('/api/v1/ai/build-bom', [
                'prompt' => 'Build a 4WD robot car',
                'session_key' => $sessionKey,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('session_key');
    }

    private function apiUser(string $email): array
    {
        $token = bin2hex(random_bytes(32));
        $user = User::forceCreate([
            'name' => 'Legacy AI Customer',
            'email' => $email,
            'password' => bcrypt('password123'),
            'api_token_hash' => hash('sha256', $token),
        ]);

        return [$user, $token];
    }

    private function ensureCommerceAiTables(): void
    {
        if (! Schema::hasTable('commerce_ai_sessions')) {
            Schema::create('commerce_ai_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('session_key')->unique();
                $table->string('intent', 80)->nullable()->index();
                $table->string('status', 40)->default('active')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commerce_ai_messages')) {
            Schema::create('commerce_ai_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('commerce_ai_session_id')->nullable()->index();
                $table->string('role', 40);
                $table->text('message');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commerce_ai_bom_requests')) {
            Schema::create('commerce_ai_bom_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('commerce_ai_session_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->text('prompt');
                $table->string('intent', 80)->nullable()->index();
                $table->string('status', 40)->default('completed')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commerce_ai_bom_results')) {
            Schema::create('commerce_ai_bom_results', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('commerce_ai_bom_request_id')->index();
                $table->string('title');
                $table->string('estimated_total')->nullable();
                $table->json('payload');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commerce_ai_recommendation_items')) {
            Schema::create('commerce_ai_recommendation_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('commerce_ai_bom_result_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('name');
                $table->decimal('quantity', 12, 3)->default(1);
                $table->text('reason')->nullable();
                $table->string('availability_status', 80)->default('catalog_match_not_verified');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }
}
