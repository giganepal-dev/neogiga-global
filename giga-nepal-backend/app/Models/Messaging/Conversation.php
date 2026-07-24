<?php

namespace App\Models\Messaging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(ConversationMessage::class)->latestOfMany();
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }
}
