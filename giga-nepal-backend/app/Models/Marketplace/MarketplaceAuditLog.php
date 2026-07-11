<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAuditLog extends Model
{
    protected $fillable = [
        'marketplace_id', 'user_id', 'action',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    /**
     * Record an audit entry. Request-derived fields (user, IP, UA) are optional
     * so this is callable from CLI/seeders as well as HTTP.
     */
    public static function record(
        ?int $marketplaceId,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        ?int $userId = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): self {
        return self::create([
            'marketplace_id' => $marketplaceId,
            'user_id' => $userId,
            'action' => $action,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
