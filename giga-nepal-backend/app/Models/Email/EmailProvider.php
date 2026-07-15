<?php

namespace App\Models\Email;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailProvider extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'provider_type',
        'config',
        'from_email',
        'from_name',
        'reply_to_email',
        'is_active',
        'priority',
        'is_primary',
        'is_fallback',
        'max_retries',
        'retry_delay_seconds',
        'timeout_seconds',
        'supports_attachments',
        'supports_tracking',
        'last_successful_send_at',
        'last_failed_send_at',
        'consecutive_failures',
        'total_sent_count',
        'total_failed_count',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'supports_attachments' => 'boolean',
        'supports_tracking' => 'boolean',
        'is_primary' => 'boolean',
        'is_fallback' => 'boolean',
        'last_successful_send_at' => 'datetime',
        'last_failed_send_at' => 'datetime',
    ];

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(EmailDeliveryLog::class);
    }

    public function marketplaceSettings(): HasMany
    {
        return $this->hasMany(\App\Models\Marketplace\MarketplaceCurrencySetting::class, 'exchange_rate_provider_id');
    }
}
