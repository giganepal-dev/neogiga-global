<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Musonza\Chat\Models\Conversation as ChatConversation;

class ChatConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chat_conversations';

    protected $fillable = [
        'uuid',
        'subject',
        'type',
        'user_id',
        'created_by',
        'is_private',
        'is_archived',
        'archived_at',
        'archived_by',
        'status',
        'priority',
        'category',
        'assigned_to',
        'parent_id',
        'message_count',
        'last_message_at',
        'last_message_id',
        'metadata',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'last_message_at' => 'datetime',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function archiver()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function parent()
    {
        return $this->belongsTo(ChatConversation::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChatConversation::class, 'parent_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function participants()
    {
        return $this->hasMany(ChatParticipant::class, 'conversation_id');
    }

    public function latestMessage()
    {
        return $this->belongsTo(ChatMessage::class, 'last_message_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false)
            ->whereNull('archived_at')
            ->where('status', 'active');
    }

    public function scopeDirect($query)
    {
        return $query->where('type', 'direct');
    }

    public function scopeGroup($query)
    {
        return $query->where('type', 'group');
    }

    public function scopeSupport($query)
    {
        return $query->where('type', 'support');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
