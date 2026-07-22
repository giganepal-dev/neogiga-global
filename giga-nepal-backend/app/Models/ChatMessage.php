<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ChatMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chat_messages';

    protected $fillable = [
        'uuid',
        'conversation_id',
        'user_id',
        'body',
        'type',
        'attachments',
        'is_edited',
        'edited_at',
        'is_deleted',
        'deleted_at',
        'deleted_by',
        'is_read',
        'read_at',
        'reactions',
        'parent_id',
        'depth',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'attachments' => 'array',
        'reactions' => 'array',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'sender_name',
        'sender_avatar',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::created(function ($model) {
            // Update conversation message count and last message
            $model->conversation()->update([
                'message_count' => $model->conversation->message_count + 1,
                'last_message_at' => now(),
                'last_message_id' => $model->id,
            ]);
        });
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function parent()
    {
        return $this->belongsTo(ChatMessage::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(ChatMessage::class, 'parent_id');
    }

    public function reads()
    {
        return $this->hasMany(ChatMessageRead::class, 'message_id');
    }

    public function getSenderNameAttribute()
    {
        return $this->sender?->name ?? 'System';
    }

    public function getSenderAvatarAttribute()
    {
        return $this->sender?->profile_photo_url ?? null;
    }

    public function scopeInConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeUnread($query, $userId)
    {
        return $query->whereHas('reads', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        }, '=', 0);
    }

    public function isReadBy($userId)
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    public function markAsReadBy($userId)
    {
        if (!$this->isReadBy($userId)) {
            ChatMessageRead::create([
                'message_id' => $this->id,
                'user_id' => $userId,
            ]);

            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    public function addReaction($userId, $emoji)
    {
        $reactions = $this->reactions ?? [];
        
        // Remove existing reaction from this user
        $reactions = collect($reactions)->filter(fn($r) => $r['user_id'] !== $userId)->toArray();
        
        // Add new reaction
        $reactions[] = ['user_id' => $userId, 'emoji' => $emoji];
        
        $this->update(['reactions' => $reactions]);
    }

    public function removeReaction($userId)
    {
        $reactions = collect($this->reactions ?? [])
            ->filter(fn($r) => $r['user_id'] !== $userId)
            ->toArray();
        
        $this->update(['reactions' => $reactions]);
    }
}
