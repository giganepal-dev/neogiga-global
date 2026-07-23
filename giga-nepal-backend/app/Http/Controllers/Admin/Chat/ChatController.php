<?php

namespace App\Http\Controllers\Admin\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
        // $this->middleware('auth'); // handled by route middleware
    }

    /**
     * Display dashboard with conversations list
     */
    public function index(Request $request)
    {
        $type = $request->get('type');
        $status = $request->get('status', 'active');

        $conversations = $this->chatService->getUserConversations(
            Auth::user(),
            $type,
            $status,
            20
        );

        $unreadCount = $this->chatService->getUnreadCount(Auth::user());

        return view('admin.chat.index', compact('conversations', 'unreadCount'));
    }

    /**
     * Show a specific conversation
     */
    public function show(Request $request, ChatConversation $conversation)
    {
        // Gate check for access
        if (!$this->canAccessConversation(Auth::user(), $conversation)) {
            abort(403, 'Unauthorized access to this conversation');
        }

        $messages = $this->chatService->getConversationMessages(
            $conversation,
            50,
            $request->get('before_id')
        );

        // Mark as read
        $this->chatService->markConversationAsRead($conversation, Auth::user());

        return view('admin.chat.show', compact('conversation', 'messages'));
    }

    /**
     * Send a message in a conversation
     */
    public function sendMessage(Request $request, ChatConversation $conversation)
    {
        $request->validate([
            'body' => 'required|string|max:10000',
            'type' => 'sometimes|in:text,file,image',
            'attachments' => 'sometimes|array',
            'parent_id' => 'sometimes|exists:chat_messages,id',
        ]);

        $message = $this->chatService->sendMessage(
            $conversation,
            Auth::user(),
            $request->body,
            $request->type ?? 'text',
            $request->attachments ?? [],
            $request->parent_id
        );

        return response()->json([
            'success' => true,
            'message' => $message->load(['sender', 'reads']),
        ]);
    }

    /**
     * Create a new direct conversation
     */
    public function createDirect(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
        ]);

        $recipient = User::findOrFail($request->recipient_id);

        $conversation = $this->chatService->createDirectConversation(
            Auth::user(),
            $recipient
        );

        return redirect()->route('admin.chat.show', $conversation->uuid)
            ->with('success', 'Conversation started successfully');
    }

    /**
     * Create a support ticket/conversation
     */
    public function createSupport(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string|in:support,sales,technical,billing,general',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'message' => 'required|string|max:10000',
        ]);

        $conversation = $this->chatService->createSupportConversation(
            Auth::user(),
            $request->subject,
            $request->category,
            $request->priority ?? 'normal'
        );

        // Send initial message
        $this->chatService->sendMessage(
            $conversation,
            Auth::user(),
            $request->message
        );

        return redirect()->route('admin.chat.show', $conversation->uuid)
            ->with('success', 'Support ticket created successfully');
    }

    /**
     * Archive a conversation
     */
    public function archive(ChatConversation $conversation)
    {
        $this->chatService->archiveConversation($conversation, Auth::user());

        return back()->with('success', 'Conversation archived');
    }

    /**
     * Assign conversation to a user (admin only)
     */
    public function assign(Request $request, ChatConversation $conversation)
    {
        Gate::authorize('assign-chat-conversation');

        $request->validate([
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $assignee = $request->assignee_id ? User::find($request->assignee_id) : null;

        $this->chatService->assignConversation($conversation, $assignee);

        return back()->with('success', 'Conversation assigned successfully');
    }

    /**
     * Search conversations
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['conversations' => []]);
        }

        $conversations = $this->chatService->searchConversations(
            Auth::user(),
            $query,
            20
        );

        return response()->json([
            'conversations' => $conversations->map(fn($c) => [
                'uuid' => $c->uuid,
                'subject' => $c->subject,
                'type' => $c->type,
                'last_message' => $c->latestMessage?->body,
                'last_message_at' => $c->last_message_at,
            ]),
        ]);
    }

    /**
     * Get unread count
     */
    public function unreadCount()
    {
        $count = $this->chatService->getUnreadCount(Auth::user());

        return response()->json(['count' => $count]);
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        $user->chatParticipants->each(function ($participant) {
            $participant->markAsRead();
        });

        return response()->json(['success' => true]);
    }

    /**
     * Check if user can access conversation
     */
    private function canAccessConversation(User $user, ChatConversation $conversation): bool
    {
        // Admins can access all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if user is a participant
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }
}
