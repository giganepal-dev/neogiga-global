<?php

namespace App\Models\Ai;

use App\Models\BaseModel;
use App\Models\User;
use App\Models\Marketplace\Marketplace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiSession extends BaseModel
{
    protected $table = 'ai_sessions';

    protected $fillable = [
        'user_id',
        'marketplace_id',
        'session_id',
        'status',
        'goal',
        'context',
        'meta_data',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'context' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
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
}
