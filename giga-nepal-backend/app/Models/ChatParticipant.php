<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatParticipant extends Model
{
    use HasFactory;

    protected $table = 'chat_participants';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'is_active',
        'last_read_at',
        'last_read_message_id',
        'unread_count',
        'is_muted',
        'muted_until',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_read_at' => 'datetime',
        'muted_until' => 'datetime',
        'settings' => 'array',
        'is_muted' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lastReadMessage()
    {
        return $this->belongsTo(ChatMessage::class, 'last_read_message_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeModerators($query)
    {
        return $query->where('role', 'moderator');
    }

    public function isMuted()
    {
        if (!$this->is_muted) {
            return false;
        }

        return $this->muted_until === null || $this->muted_until->isFuture();
    }

    public function updateUnreadCount()
    {
        $unreadCount = $this->conversation->messages()
            ->where('created_at', '>', $this->last_read_at ?? $this->created_at)
            ->where('user_id', '!=', $this->user_id)
            ->count();

        $this->update(['unread_count' => $unreadCount]);
    }

    public function markAsRead()
    {
        $latestMessage = $this->conversation->latestMessage;
        
        $this->update([
            'last_read_at' => now(),
            'last_read_message_id' => $latestMessage?->id,
            'unread_count' => 0,
        ]);
    }
}
