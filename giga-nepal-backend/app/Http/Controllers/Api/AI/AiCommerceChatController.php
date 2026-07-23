<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\CommerceAi\CommerceAiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiCommerceChatController extends Controller
{
    public function __construct(
        private CommerceAiChatService $chatService,
    ) {}

    /**
     * Create a new AI Commerce session.
     *
     * POST /api/v1/ai-commerce/session
     */
    public function createSession(Request $request): JsonResponse
    {
        $sessionKey = (string) Str::uuid();

        return response()->json([
            'success' => true,
            'data' => [
                'session_key' => $sessionKey,
                'status' => 'active',
                'created_at' => now()->toISOString(),
            ],
        ], 201);
    }

    /**
     * Process a chat message.
     *
     * POST /api/v1/ai-commerce/chat
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'session_key' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->chatService->processMessage(
            $validated['message'],
            $request->user()->id,
            $validated['session_key'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'reply' => $result['reply'],
                'structured' => $result['structured'],
                'actions' => $result['actions'],
                'confidence' => $result['confidence'],
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }
}
