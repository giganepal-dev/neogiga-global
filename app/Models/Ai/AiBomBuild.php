<?php

namespace App\Models\Ai;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiBomBuild extends BaseModel
{
    protected $table = 'ai_bom_builds';

    protected $fillable = [
        'ai_session_id',
        'goal',
        'status',
        'total_estimated_price',
        'currency_code',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'total_estimated_price' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AiBomItem::class, 'ai_bom_build_id');
    }
}
