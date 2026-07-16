<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerQuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_quote_id',
        'rfq_item_id',
        'product_id',
        'supplier_quote_id',
        'mpn',
        'manufacturer',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'lead_time_days',
        'is_alternative',
        'original_rfq_item_id',
        'pricing_breakdown',
    ];

    protected $casts = [
        'is_alternative' => 'boolean',
        'pricing_breakdown' => 'array',
    ];

    public function customerQuote(): BelongsTo
    {
        return $this->belongsTo(CustomerQuote::class);
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RfqItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplierQuote(): BelongsTo
    {
        return $this->belongsTo(SupplierQuote::class);
    }

    public function originalRfqItem(): BelongsTo
    {
        return $this->belongsTo(RfqItem::class, 'original_rfq_item_id');
    }
}
