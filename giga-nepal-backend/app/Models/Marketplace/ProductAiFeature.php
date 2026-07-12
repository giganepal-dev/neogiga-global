<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAiFeature extends Model
{
    protected $fillable = [
        'product_id',
        'ai_summary',
        'ai_bom_suggestions',
        'ai_compatible_alternatives',
        'ai_cross_sell_recommendations',
        'ai_project_ideas',
        'ai_pinout_diagrams',
        'ai_wiring_examples',
        'ai_engineering_notes',
        'ai_datasheet_qa',
        'ai_model_version',
        'ai_generated_at',
        'is_verified',
        'verified_by',
    ];

    protected $casts = [
        'ai_bom_suggestions' => 'array',
        'ai_compatible_alternatives' => 'array',
        'ai_cross_sell_recommendations' => 'array',
        'ai_project_ideas' => 'array',
        'ai_pinout_diagrams' => 'array',
        'ai_wiring_examples' => 'array',
        'ai_datasheet_qa' => 'array',
        'ai_generated_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
