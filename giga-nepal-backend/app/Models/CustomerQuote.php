<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'rfq_request_id',
        'quote_number',
        'created_by_id',
        'subtotal',
        'margin_percentage',
        'tax_amount',
        'shipping_cost',
        'insurance_cost',
        'total_amount',
        'currency',
        'payment_terms',
        'delivery_terms',
        'validity_date',
        'estimated_dispatch_days',
        'estimated_delivery_days',
        'warehouse_id',
        'commercial_notes',
        'technical_notes',
        'status',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'rejected_at',
        'converted_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'converted_at' => 'datetime',
        'validity_date' => 'date',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($quote) {
            if (empty($quote->public_id)) {
                $quote->public_id = \Illuminate\Support\Str::uuid()->toString();
            }
            
            if (empty($quote->quote_number)) {
                $quote->quote_number = self::whereYear('created_at', date('Y'))->count() + 1;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function rfqRequest(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerQuoteItem::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(QuoteVersion::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(QuoteApproval::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(QuoteActivityLog::class);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                     ->orWhere('validity_date', '<', now());
    }

    public function isViewableByCustomer(): bool
    {
        return in_array($this->status, ['sent', 'viewed', 'revised', 'accepted']);
    }

    public function canBeConvertedToOrder(): bool
    {
        return $this->status === 'accepted' && !$this->converted_at;
    }

    public function isValid(): bool
    {
        return $this->validity_date >= now() && !in_array($this->status, ['expired', 'rejected']);
    }

    public function markAsViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update([
                'viewed_at' => now(),
                'status' => $this->status === 'sent' ? 'viewed' : $this->status,
            ]);
        }
    }
}
