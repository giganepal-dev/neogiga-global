<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_id',
        'key',
        'value',
        'type',
        'group',
        'is_public',
        'metadata',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'metadata' => 'array',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'boolean' => (bool) $this->value,
            'json', 'array' => json_decode($this->value ?? '[]', true),
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            default => $this->value,
        };
    }
}
