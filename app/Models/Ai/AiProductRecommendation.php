<?php

namespace App\Models\Ai;

use App\Models\BaseModel;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProductRecommendation extends BaseModel
{
    protected $table = 'ai_product_recommendations';

    protected $fillable = [
        'ai_session_id',
        'product_id',
        'score',
        'reason',
        'context',
        'meta_data',
    ];

    protected $casts = [
        'score' => 'decimal:4',
        'context' => 'array',
        'meta_data' => 'array',
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
