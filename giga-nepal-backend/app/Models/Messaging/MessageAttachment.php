<?php

namespace App\Models\Messaging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $guarded = [];

    public function message(): BelongsTo
    {
        return $this->belongsTo(ConversationMessage::class, 'message_id');
    }
}
