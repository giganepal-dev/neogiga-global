<?php

namespace App\Models\Ai;

use App\Models\BaseModel;
use App\Models\Cart\Cart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCartAction extends BaseModel
{
    protected $table = 'ai_cart_actions';

    protected $fillable = [
        'ai_session_id',
        'cart_id',
        'action_type', // add_bom, add_item, create_order
        'status',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
}
