<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FirebaseDeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'platform', // web, android, ios
        'device_type',
        'browser',
        'marketplace_id',
        'language',
        'is_enabled',
        'last_seen_at',
        'registered_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'last_seen_at' => 'datetime',
        'registered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace\Marketplace::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationDeliveryLog::class, 'device_token_id');
    }
}
