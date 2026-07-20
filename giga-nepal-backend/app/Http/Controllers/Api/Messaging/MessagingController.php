<?php

namespace App\Http\Controllers\Api\Messaging;

use App\Http\Controllers\Controller;
use App\Models\Messaging\Conversation;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Messaging\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Customer ↔ Seller messaging API.
 *
 * All responses go through the masking layer — customers see sellers'
 * real names; sellers see "Customer" and masked PII; admins see everything.
 */
class MessagingController extends Controller
{
    public function __construct(
        private readonly MessagingService $messaging,
    ) {}

    /**
     * List the authenticated user's conversations.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user() ?? Auth::guard('vendor')->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $conversations = $this->messaging->listFor($user, (int) ($request->input('limit', 20)));

        return response()->json(['data' => $conversations]);
    }

    /**
     * Start a new conversation.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user() ?? Auth::guard('vendor')->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'recipient_type' => 'required|string|in:vendor,user',
            'recipient_id' => 'required|integer',
            'context_type' => 'nullable|string',
            'context_id' => 'nullable|integer',
        ]);

        // Resolve recipient
        $recipient = match ($validated['recipient_type']) {
            'vendor' => Vendor::findOrFail($validated['recipient_id']),
            'user' => User::findOrFail($validated['recipient_id']),
            default => throw new \InvalidArgumentException('Unknown recipient type'),
        };

        $conversation = $this->messaging->startConversation(
            subject: $validated['subject'],
            initiator: $user,
            recipient: $recipient,
            contextType: $validated['context_type'] ?? null,
            contextId: $validated['context_id'] ?? null,
        );

        $message = $this->messaging->send($conversation, $user, $validated['body']);

        return response()->json([
            'data' => [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
            ],
        ], 201);
    }

    /**
     * Get messages for a conversation.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $user = Auth::user() ?? Auth::guard('vendor')->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $conversation = Conversation::findOrFail($id);
        $messages = $this->messaging->messagesFor(
            $conversation,
            $user,
            (int) ($request->input('limit', 50)),
        );

        // Mark as read
        $this->messaging->markRead($conversation, $user);

        return response()->json(['data' => $messages]);
    }

    /**
     * Send a message in an existing conversation.
     */
    public function reply(int $id, Request $request): JsonResponse
    {
        $user = Auth::user() ?? Auth::guard('vendor')->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'type' => 'nullable|string|in:text,system',
        ]);

        $conversation = Conversation::findOrFail($id);

        $message = $this->messaging->send(
            $conversation,
            $user,
            $validated['body'],
            $validated['type'] ?? 'text',
        );

        return response()->json([
            'data' => [
                'id' => $message->id,
                'body' => $message->body,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Close or archive a conversation.
     */
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:open,closed,archived',
        ]);

        $conversation = Conversation::findOrFail($id);
        $this->messaging->updateStatus($conversation, $validated['status']);

        return response()->json(['data' => ['status' => $validated['status']]]);
    }

    /**
     * Admin: list all conversations (no masking).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $conversations = Conversation::with(['participants', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->paginate((int) ($request->input('per_page', 20)));

        return response()->json(['data' => $conversations]);
    }

    /**
     * Admin: view a conversation with unmasked messages.
     */
    public function adminShow(int $id): JsonResponse
    {
        $conversation = Conversation::with(['messages.sender', 'messages.attachments', 'participants'])
            ->findOrFail($id);

        return response()->json(['data' => $conversation]);
    }

    /**
     * Admin: send a message as support.
     */
    public function adminReply(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $conversation = Conversation::findOrFail($id);

        // Admin messages are sent as the admin user
        $admin = Auth::user();
        $message = $this->messaging->send($conversation, $admin, $validated['body']);

        // Ensure admin is a participant with unmasked access
        $this->messaging->addParticipant($conversation, $admin, 'observer', 'none');

        return response()->json([
            'data' => ['id' => $message->id],
        ], 201);
    }
}
