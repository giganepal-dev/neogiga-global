<?php

namespace App\Services\Messaging;

use App\Models\Messaging\Conversation;
use App\Models\Messaging\ConversationMessage;
use App\Models\Messaging\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Core messaging service — creates conversations, sends messages,
 * enforces privacy masking, and scopes reads to each participant.
 *
 * Every send() call stores BOTH the original body and a masked version.
 * The caller chooses which body to display based on the reader's role.
 */
class MessagingService
{
    public function __construct(
        private readonly PrivacyMaskingService $masker = new PrivacyMaskingService(),
    ) {}

    /**
     * Start a conversation between a customer and a seller (or any two morphables).
     *
     * @return Conversation
     */
    public function startConversation(
        string $subject,
        Model $initiator,
        Model $recipient,
        ?Model $context = null,
        ?string $contextType = null,
        ?int $contextId = null,
    ): Conversation {
        return DB::transaction(function () use ($subject, $initiator, $recipient, $context, $contextType, $contextId) {
            $conversation = Conversation::create([
                'subject' => $subject,
                'context_type' => $contextType ?? ($context ? get_class($context) : null),
                'context_id' => $contextId ?? ($context ? $context->id : null),
                'status' => 'open',
            ]);

            // Initiator = owner, recipient = member (seller gets full masking)
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'participant_type' => get_class($initiator),
                'participant_id' => $initiator->id,
                'role' => 'owner',
                'mask_level' => 'none',      // customer sees their own words unmasked
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'participant_type' => get_class($recipient),
                'participant_id' => $recipient->id,
                'role' => 'member',
                'mask_level' => 'full',       // seller sees masked PII
            ]);

            return $conversation;
        });
    }

    /**
     * Send a message. Masks body for non-admin receivers and stores both versions.
     */
    public function send(
        Conversation $conversation,
        Model $sender,
        string $body,
        string $type = 'text',
        ?array $metadata = null,
    ): ConversationMessage {
        $bodyMasked = $this->masker->mask($body, 'full');

        $message = $conversation->messages()->create([
            'sender_type' => get_class($sender),
            'sender_id' => $sender->id,
            'body' => $body,
            'body_masked' => $bodyMasked,
            'type' => $type,
            'metadata' => $metadata,
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Update unread tracking for all participants EXCEPT sender
        $conversation->participants()
            ->where(function ($q) use ($sender) {
                $q->where('participant_type', '!=', get_class($sender))
                  ->orWhere('participant_id', '!=', $sender->id);
            })
            ->update(['last_read_at' => null]);

        return $message;
    }

    /**
     * Add a participant (e.g., admin joins an existing conversation).
     */
    public function addParticipant(
        Conversation $conversation,
        Model $participant,
        string $role = 'observer',
        string $maskLevel = 'none',  // admins see everything
    ): ConversationParticipant {
        return $conversation->participants()->create([
            'participant_type' => get_class($participant),
            'participant_id' => $participant->id,
            'role' => $role,
            'mask_level' => $maskLevel,
        ]);
    }

    /**
     * List conversations for a participant, newest first.
     */
    public function listFor(Model $participant, int $limit = 20): array
    {
        $participations = ConversationParticipant::where('participant_type', get_class($participant))
            ->where('participant_id', $participant->id)
            ->with(['conversation.latestMessage', 'conversation.participants'])
            ->orderByDesc('last_read_at')
            ->limit($limit)
            ->get();

        return $participations->map(function (ConversationParticipant $p) {
            $conv = $p->conversation;
            $latestMsg = $conv->latestMessage;

            return [
                'id' => $conv->id,
                'subject' => $conv->subject,
                'status' => $conv->status,
                'context_type' => $conv->context_type,
                'context_id' => $conv->context_id,
                'last_message_at' => $conv->last_message_at,
                'last_message_preview' => $latestMsg
                    ? mb_substr($this->bodyFor($p, $latestMsg), 0, 100)
                    : null,
                'unread' => $p->last_read_at === null || ($latestMsg && $latestMsg->created_at > $p->last_read_at),
                'participant_count' => $conv->participants->count(),
            ];
        })->all();
    }

    /**
     * Get messages for a participant, applying the appropriate masking level.
     */
    public function messagesFor(Conversation $conversation, Model $reader, int $limit = 50): array
    {
        $participation = $conversation->participants()
            ->where('participant_type', get_class($reader))
            ->where('participant_id', $reader->id)
            ->first();

        if (! $participation) {
            return [];  // reader is not a participant
        }

        $messages = $conversation->messages()
            ->with(['sender', 'attachments'])
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        return $messages->map(function (ConversationMessage $msg) use ($participation) {
            return [
                'id' => $msg->id,
                'body' => $this->bodyFor($participation, $msg),
                'type' => $msg->type,
                'sender_name' => $this->senderNameFor($participation, $msg),
                'sender_is_self' => $this->isSelf($participation, $msg),
                'metadata' => $msg->metadata,
                'attachments' => $msg->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->original_name,
                    'size' => $a->size_bytes,
                    'mime' => $a->mime_type,
                ])->all(),
                'created_at' => $msg->created_at->toISOString(),
            ];
        })->all();
    }

    /**
     * Mark all messages in a conversation as read for a participant.
     */
    public function markRead(Conversation $conversation, Model $reader): void
    {
        $conversation->participants()
            ->where('participant_type', get_class($reader))
            ->where('participant_id', $reader->id)
            ->update(['last_read_at' => now()]);
    }

    /**
     * Close or archive a conversation.
     */
    public function updateStatus(Conversation $conversation, string $status): void
    {
        $conversation->update(['status' => $status]);
    }

    // ─── Internal helpers ────────────────────────────────────────────

    private function bodyFor(ConversationParticipant $participation, ConversationMessage $msg): string
    {
        // Admin/owner sees original; masked participants see masked version.
        if ($participation->mask_level === 'none') {
            return $msg->body;
        }
        // If reader is sender, show original (they wrote it).
        if ($this->isSelf($participation, $msg)) {
            return $msg->body;
        }
        return $msg->body_masked ?? $msg->body;
    }

    private function senderNameFor(ConversationParticipant $participation, ConversationMessage $msg): string
    {
        if (! $msg->sender) {
            return 'System';
        }

        // Sellers see masked identity of customers.
        if ($participation->mask_level === 'full' && ! $this->isSelf($participation, $msg)) {
            return 'Customer';
        }

        return match (true) {
            $msg->sender instanceof User => $msg->sender->name ?? 'User',
            method_exists($msg->sender, 'getName') => $msg->sender->getName(),
            default => class_basename($msg->sender),
        };
    }

    private function isSelf(ConversationParticipant $participation, ConversationMessage $msg): bool
    {
        return $msg->sender_type === $participation->participant_type
            && (string) $msg->sender_id === (string) $participation->participant_id;
    }
}
