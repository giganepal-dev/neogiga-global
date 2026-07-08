<?php

namespace App\Models\CommerceAi;

use Illuminate\Database\Eloquent\Model;

class CommerceAiMessage extends Model
{
    protected $fillable = ['commerce_ai_session_id', 'role', 'message', 'metadata'];

    protected $casts = ['metadata' => 'array'];
}
