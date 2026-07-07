<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $fillable = [
        'quote_number', 'rfq_request_id', 'user_id', 'currency', 'status',
        'subtotal', 'tax_total', 'shipping_total', 'grand_total',
        'valid_until', 'sent_at', 'accepted_at', 'created_by', 'notes', 'meta',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function rfqRequest(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class);
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }
}
