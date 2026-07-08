<?php

namespace App\Models\CommerceAi;

use Illuminate\Database\Eloquent\Model;

class CommerceAiSession extends Model
{
    protected $fillable = ['user_id', 'session_key', 'intent', 'status', 'metadata'];

    protected $casts = ['metadata' => 'array'];
}
