<?php

namespace App\Models\CommerceAi;

use Illuminate\Database\Eloquent\Model;

class CommerceAiBomRequest extends Model
{
    protected $fillable = ['commerce_ai_session_id', 'user_id', 'prompt', 'intent', 'status', 'metadata'];

    protected $casts = ['metadata' => 'array'];
}
