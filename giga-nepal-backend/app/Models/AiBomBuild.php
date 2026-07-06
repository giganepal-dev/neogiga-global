<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiBomBuild extends Model
{
    protected $fillable = [
        'ai_session_id',
        'user_id',
        'marketplace_id',
        'goal_description',
        'total_estimated_price',
        'currency_code',
        'status',
    ];

    protected $casts = [
        'total_estimated_price' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class, 'marketplace_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AiBomItem::class);
    }
}
