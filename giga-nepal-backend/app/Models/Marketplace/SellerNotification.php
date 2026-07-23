<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerNotification extends Model
{
    protected $table = 'seller_notifications';

    protected $fillable = [
        'vendor_id',
        'type',
        'event',
        'title',
        'message',
        'data',
        'action_url',
        'is_read',
        'read_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return false;
        }

        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return true;
    }

    public function markAsDelivered(): bool
    {
        $this->update([
            'delivered_at' => now(),
            'failed_at' => null,
            'failure_reason' => null,
        ]);

        return true;
    }

    public function markAsFailed(string $reason): bool
    {
        $this->update([
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);

        return true;
    }
}
