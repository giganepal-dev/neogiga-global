<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSession extends Model
{
    protected $fillable = [
        'user_id',
        'marketplace_id',
        'session_id',
        'status',
        'context',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class, 'marketplace_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class);
    }

    public function bomBuilds(): HasMany
    {
        return $this->hasMany(AiBomBuild::class);
    }

    public function cartActions(): HasMany
    {
        return $this->hasMany(AiCartAction::class);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId)->orderBy('created_at', 'desc');
    }
}
