<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCartAction extends Model
{
    protected $fillable = [
        'ai_session_id',
        'cart_id',
        'action_type',
        'product_ids',
        'status',
    ];

    protected $casts = [
        'product_ids' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Cart::class, 'cart_id');
    }
}
