<?php

namespace App\Models\AiCommerce;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Cart\Cart;

class AiCartAction extends BaseModel
{
    protected $table = 'ai_cart_actions';

    protected $fillable = [
        'ai_session_id',
        'cart_id',
        'action_type', // add_bom, add_recommendation, add_single
        'products_added',
        'total_value',
        'currency_code',
    ];

    protected $casts = [
        'products_added' => 'array',
        'total_value' => 'decimal:2',
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
