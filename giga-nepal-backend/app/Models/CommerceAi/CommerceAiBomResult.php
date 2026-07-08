<?php

namespace App\Models\CommerceAi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommerceAiBomResult extends Model
{
    protected $fillable = ['commerce_ai_bom_request_id', 'title', 'estimated_total', 'payload'];

    protected $casts = ['payload' => 'array'];

    public function items(): HasMany
    {
        return $this->hasMany(CommerceAiRecommendationItem::class);
    }
}
