<?php

namespace App\Models\AiCommerce;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AiSession extends BaseModel
{
    protected $table = 'ai_sessions';

    protected $fillable = [
        'user_id',
        'session_uuid',
        'goal_description',
        'status', // active, completed, abandoned
        'context_data',
    ];

    protected $casts = [
        'context_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class);
    }

    public function bomBuilds(): HasMany
    {
        return $this->hasMany(AiBomBuild::class);
    }

    public function productRecommendations(): HasMany
    {
        return $this->hasMany(AiProductRecommendation::class);
    }
}
