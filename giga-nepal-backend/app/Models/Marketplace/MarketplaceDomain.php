<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_id',
        'domain',
        'is_primary',
        'is_active',
        'ssl_certificate_path',
        'ssl_expires_at',
        'redirect_rules',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'ssl_expires_at' => 'datetime',
        'redirect_rules' => 'array',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }
}
