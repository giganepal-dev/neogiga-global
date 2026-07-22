<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatService
{
    /**
     * Create a new direct conversation between two users
     */
    public function createDirectConversation(User $sender, User $recipient): ChatConversation
    {
        return DB::transaction(function () use ($sender, $recipient) {
            // Check if conversation already exists
            $existing = ChatConversation::where('type', 'direct')
                ->whereHas('participants', function ($q) use ($sender) {
                    $q->where('user_id', $sender->id);
                })
                ->whereHas('participants', function ($q) use ($recipient) {
                    $q->where('user_id', $recipient->id);
                })
                ->first();

            if ($existing) {
                return $existing;
            }

            $conversation = ChatConversation::create([
                'uuid' => Str::uuid()->toString(),
                'subject' => null,
                'type' => 'direct',
                'created_by' => $sender->id,
                'is_private' => false,
            ]);

            // Add participants
            ChatParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $sender->id,
                'role' => 'member',
            ]);

            ChatParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $recipient->id,
                'role' => 'member',
            ]);

            return $conversation;
        });
    }

    /**
     * Create a group conversation
     */
    public function createGroupConversation(
        User $creator,
        array $participantIds,
        string $subject,
        bool $isPrivate = false
    ): ChatConversation {
        return DB::transaction(function () use ($creator, $participantIds, $subject, $isPrivate) {
            $conversation = ChatConversation::create([
                'uuid' => Str::uuid()->toString(),
                'subject' => $subject,
                'type' => 'group',
                'created_by' => $creator->id,
                'is_private' => $isPrivate,
            ]);

            // Add creator as admin
            ChatParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $creator->id,
                'role' => 'admin',
            ]);

            // Add other participants
            foreach ($participantIds as $userId) {
                if ($userId !== $creator->id) {
                    ChatParticipant::create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $userId,
                        'role' => 'member',
                    ]);
                }
            }

            return $conversation;
        });
    }

    /**
     * Create a support conversation
     */
    public function createSupportConversation(
        User $customer,
        string $subject,
        string $category,
        string $priority = 'normal'
    ): ChatConversation {
        return DB::transaction(function () use ($customer, $subject, $category, $priority) {
            $conversation = ChatConversation::create([
                'uuid' => Str::uuid()->toString(),
                'subject' => $subject,
                'type' => 'support',
                'category' => $category,
                'priority' => $priority,
                'created_by' => $customer->id,
                'user_id' => $customer->id,
                'status' => 'active',
            ]);

            // Add customer as participant
            ChatParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $customer->id,
                'role' => 'member',
            ]);

            return $conversation;
        });
    }

    /**
     * Send a message in a conversation
     */
    public function sendMessage(
        ChatConversation $conversation,
        User $sender,
        string $body,
        string $type = 'text',
        array $attachments = [],
        ?int $parentId = null
    ): ChatMessage {
        return DB::transaction(function () use ($conversation, $sender, $body, $type, $attachments, $parentId) {
            // Ensure sender is a participant
            $participant = ChatParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', $sender->id)
                ->firstOrFail();

            $parentDepth = 0;
            if ($parentId) {
                $parent = ChatMessage::findOrFail($parentId);
                $parentDepth = $parent->depth + 1;
            }

            $message = ChatMessage::create([
                'uuid' => Str::uuid()->toString(),
                'conversation_id' => $conversation->id,
                'user_id' => $sender->id,
                'body' => $body,
                'type' => $type,
                'attachments' => $attachments,
                'parent_id' => $parentId,
                'depth' => $parentDepth,
                'ip_address' => request()?->ip(),
            ]);

            // Mark message as read by sender
            $message->markAsReadBy($sender->id);

            // Update unread counts for other participants
            $conversation->participants->each(function ($participant) use ($message, $sender) {
                if ($participant->user_id !== $sender->id && !$participant->isMuted()) {
                    $participant->updateUnreadCount();
                }
            });

            return $message;
        });
    }

    /**
     * Get conversations for a user
     */
    public function getUserConversations(
        User $user,
        string $type = null,
        string $status = 'active',
        int $perPage = 20
    ) {
        $query = ChatParticipant::where('user_id', $user->id)
            ->where('is_active', true)
            ->with(['conversation' => function ($q) use ($type, $status) {
                $q->with(['latestMessage.sender', 'creator', 'assignee'])
                    ->withCount('participants');
                
                if ($type) {
                    $q->where('type', $type);
                }
                
                if ($status) {
                    $q->where('status', $status);
                }
            }, 'user'])
            ->orderByDesc('conversation.last_message_at');

        return $query->paginate($perPage);
    }

    /**
     * Get messages in a conversation
     */
    public function getConversationMessages(
        ChatConversation $conversation,
        int $perPage = 50,
        ?int $beforeId = null
    ) {
        $query = $conversation->messages()
            ->with(['sender', 'replies', 'reads'])
            ->notDeleted()
            ->orderByDesc('created_at');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        return $query->paginate($perPage);
    }

    /**
     * Mark all messages in conversation as read
     */
    public function markConversationAsRead(ChatConversation $conversation, User $user): void
    {
        $participant = ChatParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $participant->markAsRead();
    }

    /**
     * Add participant to conversation
     */
    public function addParticipant(ChatConversation $conversation, User $user, string $role = 'member'): void
    {
        ChatParticipant::firstOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ],
            ['role' => $role]
        );
    }

    /**
     * Remove participant from conversation
     */
    public function removeParticipant(ChatConversation $conversation, User $user): void
    {
        ChatParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Archive a conversation
     */
    public function archiveConversation(ChatConversation $conversation, User $user): void
    {
        $conversation->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $user->id,
            'status' => 'closed',
        ]);
    }

    /**
     * Assign conversation to a user
     */
    public function assignConversation(ChatConversation $conversation, ?User $assignee): void
    {
        $conversation->update(['assigned_to' => $assignee?->id]);

        if ($assignee) {
            $this->addParticipant($conversation, $assignee);
        }
    }

    /**
     * Get unread message count for user
     */
    public function getUnreadCount(User $user): int
    {
        return ChatParticipant::where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('unread_count');
    }

    /**
     * Search conversations
     */
    public function searchConversations(User $user, string $query, int $limit = 20)
    {
        return ChatParticipant::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('conversation', function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                    ->orWhereHas('messages', function ($mq) use ($query) {
                        $mq->where('body', 'like', "%{$query}%");
                    });
            })
            ->with(['conversation' => function ($q) {
                $q->with(['latestMessage.sender']);
            }])
            ->limit($limit)
            ->get()
            ->pluck('conversation');
    }
}
