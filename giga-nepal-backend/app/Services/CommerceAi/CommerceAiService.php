<?php

namespace App\Services\CommerceAi;

use App\Models\CommerceAi\CommerceAiBomRequest;
use App\Models\CommerceAi\CommerceAiBomResult;
use App\Models\CommerceAi\CommerceAiMessage;
use App\Models\CommerceAi\CommerceAiRecommendationItem;
use App\Models\CommerceAi\CommerceAiSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommerceAiService
{
    public function __construct(
        private CommerceAiIntentDetector $intentDetector,
        private CommerceAiBomService $bomService,
        private CommerceAiRecommendationService $recommendations
    ) {}

    public function examples(): array
    {
        return [
            'I want to build a 4WD robot car',
            'Suggest parts for a smart irrigation system',
            'Build a school electronics lab kit',
            'Recommend solar backup parts for a small home',
            'Create a CCTV and access control starter kit',
            'Compare ESP32 and Arduino for my project',
        ];
    }

    public function createSession(?int $userId = null): array
    {
        $sessionKey = (string) Str::uuid();

        if (Schema::hasTable('commerce_ai_sessions')) {
            CommerceAiSession::create([
                'user_id' => $userId,
                'session_key' => $sessionKey,
                'status' => 'active',
                'metadata' => ['engine' => 'local_rule_engine'],
            ]);
        }

        return ['session_key' => $sessionKey, 'engine' => 'local_rule_engine', 'status' => 'active'];
    }

    public function respond(string $prompt, ?string $sessionKey = null, ?int $userId = null): array
    {
        $result = $this->buildBom($prompt, $sessionKey, $userId);
        $reply = 'I created a local-rule BOM suggestion. Review compatibility, stock, and seller terms before buying.';

        if ($sessionKey && Schema::hasTable('commerce_ai_sessions') && Schema::hasTable('commerce_ai_messages')) {
            $session = $this->sessionFor($sessionKey, $userId, $result['intent'] ?? null);
            CommerceAiMessage::create(['commerce_ai_session_id' => $session->id, 'role' => 'user', 'message' => $prompt]);
            CommerceAiMessage::create(['commerce_ai_session_id' => $session->id, 'role' => 'assistant', 'message' => $reply, 'metadata' => ['bom_title' => $result['title']]]);
        }

        return [
            'reply' => $reply,
            'bom' => $result,
        ];
    }

    public function buildBom(string $prompt, ?string $sessionKey = null, ?int $userId = null): array
    {
        $intent = $this->intentDetector->detect($prompt);
        $template = $this->bomService->template($intent);
        $items = $this->recommendations->enrich($template['items']);
        $payload = [
            'title' => $template['title'],
            'intent' => $intent,
            'prompt' => $prompt,
            'items' => $items,
            'output_cards' => [
                'Recommended components',
                'Required and optional parts',
                'Quantity and reason',
                'Datasheet and warranty links',
                'Compatible alternatives',
                'Region-wise stock',
                'Estimated total',
                'Add BOM to cart',
                'Request B2B quote',
                'Link LMS tutorial',
                'Sample code placeholder',
            ],
            'lms_tutorial_placeholder' => true,
            'sample_code_placeholder' => true,
            'source_notes' => 'Local NeoGiga rule engine. Product catalog matching is attempted when products exist; unavailable items remain generic suggestions.',
            'confidence_level' => 'medium',
            'last_updated' => now()->toISOString(),
            'disclaimer' => 'Advisory only. No order, payment, or live stock reservation has been created.',
        ];

        if (Schema::hasTable('commerce_ai_bom_requests')) {
            DB::transaction(function () use ($prompt, $intent, $payload, $items, $sessionKey, $userId) {
                $session = $sessionKey && Schema::hasTable('commerce_ai_sessions')
                    ? $this->sessionFor($sessionKey, $userId, $intent)
                    : null;
                $request = CommerceAiBomRequest::create([
                    'commerce_ai_session_id' => $session?->id,
                    'user_id' => $userId,
                    'prompt' => $prompt,
                    'intent' => $intent,
                    'status' => 'completed',
                    'metadata' => ['engine' => 'local_rule_engine'],
                ]);
                $result = CommerceAiBomResult::create([
                    'commerce_ai_bom_request_id' => $request->id,
                    'title' => $payload['title'],
                    'estimated_total' => null,
                    'payload' => $payload,
                ]);
                foreach ($items as $item) {
                    CommerceAiRecommendationItem::create([
                        'commerce_ai_bom_result_id' => $result->id,
                        'product_id' => $item['product_id'] ?? null,
                        'name' => $item['name'],
                        'quantity' => $item['quantity'] ?? 1,
                        'reason' => $item['reason'] ?? null,
                        'availability_status' => $item['availability_status'] ?? 'generic_suggestion_product_not_matched',
                        'metadata' => $item,
                    ]);
                }
            });
        }

        return $payload;
    }

    private function sessionFor(string $sessionKey, ?int $userId, ?string $intent = null): CommerceAiSession
    {
        $session = CommerceAiSession::firstOrCreate(
            ['session_key' => $sessionKey],
            ['user_id' => $userId, 'status' => 'active', 'intent' => $intent]
        );

        if ($session->user_id !== null && $session->user_id !== $userId) {
            throw ValidationException::withMessages([
                'session_key' => 'This AI session belongs to another account.',
            ]);
        }

        return $session;
    }
}
