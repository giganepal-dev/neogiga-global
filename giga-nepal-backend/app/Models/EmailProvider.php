<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider_type', // resend, ses, smtp
        'is_enabled',
        'is_primary',
        'priority',
        'config', // JSON: API keys, hosts, etc (encrypted)
        'from_email',
        'from_name',
        'reply_to_email',
        'region',
        'webhook_secret',
        'last_test_status',
        'last_test_at',
        'last_success_at',
        'last_failure_at',
        'failure_count',
        'status', // active, inactive, failed
    ];

    protected $casts = [
        'config' => 'array',
        'is_enabled' => 'boolean',
        'is_primary' => 'boolean',
        'priority' => 'integer',
        'last_test_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
    ];

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(EmailDeliveryLog::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class, 'provider_id');
    }
}
