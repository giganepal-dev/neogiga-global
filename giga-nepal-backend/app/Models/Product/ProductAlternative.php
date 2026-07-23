<?php

namespace App\Models\Product;

use App\Models\Marketplace\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAlternative extends Model
{
    protected $fillable = [
        'product_id',
        'alternative_product_id',
        'alternative_type',
        'compatibility_level',
        'notes',
        'comparison_data',
        'is_verified',
        'verified_by',
        'verified_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'comparison_data' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function alternativeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'alternative_product_id');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope to active alternatives.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to verified alternatives.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope by alternative type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('alternative_type', $type);
    }

    /**
     * Scope by compatibility level.
     */
    public function scopeOfCompatibility($query, string $level)
    {
        return $query->where('compatibility_level', $level);
    }

    /**
     * Get compatibility badge text.
     */
    public function getCompatibilityBadgeAttribute(): string
    {
        return match ($this->compatibility_level) {
            'drop_in_verified' => 'Drop-in verified',
            'pin_compatible' => 'Pin-compatible',
            'functionally_similar' => 'Functionally similar',
            'parametric_match' => 'Parametric match',
            'engineering_review_required' => 'Engineering review required',
            default => 'Unverified',
        };
    }
}
