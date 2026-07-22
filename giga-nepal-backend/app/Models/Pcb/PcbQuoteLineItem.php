<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcbQuoteLineItem extends Model
{
    protected $fillable = [
        'quote_id', 'item_type', 'description',
        'unit_price', 'quantity', 'total_price',
        'currency', 'is_optional', 'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'total_price' => 'decimal:2',
        'is_optional' => 'boolean',
        'metadata' => 'array',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(PcbQuoteConfiguration::class, 'quote_id');
    }
}
