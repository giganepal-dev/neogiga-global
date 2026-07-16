<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupplierQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'rfq_request_id',
        'supplier_user_id',
        'supplier_name',
        'supplier_country',
        'supplier_sku',
        'product_id',
        'mpn',
        'manufacturer',
        'offered_quantity',
        'unit_cost',
        'currency',
        'moq',
        'lead_time_days',
        'date_code',
        'packaging',
        'condition',
        'warranty',
        'compliance',
        'shipping_cost',
        'quote_validity',
        'supplier_notes',
        'internal_risk_score',
        'status',
    ];

    protected $casts = [
        'compliance' => 'array',
        'quote_validity' => 'date',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($quote) {
            if (empty($quote->public_id)) {
                $quote->public_id = \Illuminate\Support\Str::uuid()->toString();
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

    public function supplierUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierQuoteItem::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(QuoteActivityLog::class, 'activitable');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function getTotalCostAttribute(): float
    {
        $itemsTotal = $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
        
        return $itemsTotal + $this->shipping_cost;
    }

    public function isExpired(): bool
    {
        return $this->quote_validity && $this->quote_validity < now();
    }
}
