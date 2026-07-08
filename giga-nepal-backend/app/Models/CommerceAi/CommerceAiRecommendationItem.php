<?php

namespace App\Models\CommerceAi;

use Illuminate\Database\Eloquent\Model;

class CommerceAiRecommendationItem extends Model
{
    protected $fillable = ['commerce_ai_bom_result_id', 'product_id', 'name', 'quantity', 'reason', 'availability_status', 'metadata'];

    protected $casts = ['metadata' => 'array'];
}
