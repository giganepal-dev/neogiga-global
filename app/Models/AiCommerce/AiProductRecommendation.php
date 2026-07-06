<?php

namespace App\Models\AiCommerce;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product\Product;

class AiProductRecommendation extends BaseModel
{
    protected $table = 'ai_product_recommendations';

    protected $fillable = [
        'ai_session_id',
        'product_id',
        'reason',
        'relevance_score',
        'is_added_to_cart',
    ];

    protected $casts = [
        'relevance_score' => 'decimal:2',
        'is_added_to_cart' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
