<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'event_key',
        'subject',
        'html_body',
        'text_body',
        'marketplace_id',
        'language',
        'country_code',
        'customer_type',
        'seller_type',
        'is_enabled',
        'variables',
        'version',
        'approval_status', // draft, pending, approved, rejected
        'approved_by',
        'approved_at',
        'provider_id',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_enabled' => 'boolean',
        'version' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace\Marketplace::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(EmailProvider::class);
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(EmailDeliveryLog::class);
    }
}
