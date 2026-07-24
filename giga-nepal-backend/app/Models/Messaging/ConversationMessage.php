<?php

namespace App\Models\Messaging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ConversationMessage extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class, 'message_id');
    }
}
