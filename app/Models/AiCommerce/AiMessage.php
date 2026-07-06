<?php

namespace App\Models\AiCommerce;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends BaseModel
{
    protected $table = 'ai_messages';

    protected $fillable = [
        'ai_session_id',
        'role', // user, assistant, system
        'content',
        'tokens_used',
        'model_used',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }
}
