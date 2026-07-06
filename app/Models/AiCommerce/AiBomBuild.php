<?php

namespace App\Models\AiCommerce;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiBomBuild extends BaseModel
{
    protected $table = 'ai_bom_builds';

    protected $fillable = [
        'ai_session_id',
        'goal_description',
        'total_estimated_price',
        'currency_code',
        'status', // draft, completed, converted_to_cart
    ];

    protected $casts = [
        'total_estimated_price' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AiBomItem::class);
    }
}
