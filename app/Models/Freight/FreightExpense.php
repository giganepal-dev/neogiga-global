<?php

namespace App\Models\Freight;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreightExpense extends Model
{
    protected $fillable = [
        'freight_shipment_id',
        'expense_type',
        'description',
        'amount',
        'currency',
        'expense_date',
        'invoice_number',
        'vendor_id',
        'is_paid',
        'paid_date',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'expense_date' => 'date',
        'is_paid' => 'boolean',
        'paid_date' => 'date',
    ];

    public function freightShipment(): BelongsTo
    {
        return $this->belongsTo(FreightShipment::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Purchasing\Supplier::class, 'vendor_id');
    }
}
