<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RfqItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfq_request_id',
        'product_id',
        'customer_part_number',
        'mpn',
        'manufacturer',
        'description',
        'quantity',
        'target_unit_price',
        'currency',
        'required_delivery_date',
        'preferred_warehouse_id',
        'preferred_country_of_origin',
        'accept_alternatives',
        'exact_match_required',
        'technical_notes',
        'customer_notes',
        'package_type',
        'lifecycle_status',
        'match_data',
        'status',
    ];

    protected $casts = [
        'match_data' => 'array',
        'accept_alternatives' => 'boolean',
        'exact_match_required' => 'boolean',
        'required_delivery_date' => 'date',
    ];

    public function rfqRequest(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function preferredWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'preferred_warehouse_id');
    }

    public function supplierQuotes(): HasMany
    {
        return $this->hasMany(SupplierQuoteItem::class);
    }

    public function customerQuoteItems(): HasMany
    {
        return $this->hasMany(CustomerQuoteItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RfqAttachment::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(QuoteActivityLog::class, 'activitable');
    }

    public function getMatchedProductAttribute()
    {
        if ($this->product_id) {
            return $this->product;
        }
        
        if (!empty($this->match_data['matched_product_id'])) {
            return Product::find($this->match_data['matched_product_id']);
        }
        
        return null;
    }

    public function hasExactMatch(): bool
    {
        return $this->exact_match_required || 
               ($this->product_id && $this->match_data['match_type'] === 'exact' ?? false);
    }

    public function allowsAlternatives(): bool
    {
        return $this->accept_alternatives && !$this->exact_match_required;
    }
}
