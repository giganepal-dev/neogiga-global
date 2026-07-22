<?php

namespace App\Policies;

use App\Models\ChatConversation;
use App\Models\User;

class ChatConversationPolicy
{
    /**
     * Determine if the user can view any conversations.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can access chat
    }

    /**
     * Determine if the user can view a specific conversation.
     */
    public function view(User $user, ChatConversation $conversation): bool
    {
        // Admins can view all conversations
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if user is a participant
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Determine if the user can send messages in the conversation.
     */
    public function sendMessage(User $user, ChatConversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }

    /**
     * Determine if the user can archive the conversation.
     */
    public function archive(User $user, ChatConversation $conversation): bool
    {
        // Only creator or admin can archive
        if ($user->hasRole('admin')) {
            return true;
        }

        return $conversation->created_by === $user->id;
    }

    /**
     * Determine if the user can assign the conversation.
     */
    public function assign(User $user, ChatConversation $conversation): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete the conversation.
     */
    public function delete(User $user, ChatConversation $conversation): bool
    {
        return $user->hasRole('admin') || $conversation->created_by === $user->id;
    }

    /**
     * Determine if the user can add participants to the conversation.
     */
    public function addParticipant(User $user, ChatConversation $conversation): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Group admins can add participants
        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        return $participant && $participant->role === 'admin';
    }

    /**
     * Determine if the user can remove participants from the conversation.
     */
    public function removeParticipant(User $user, ChatConversation $conversation): bool
    {
        return $this->addParticipant($user, $conversation);
    }
}
