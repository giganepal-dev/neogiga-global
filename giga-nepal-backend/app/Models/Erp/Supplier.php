<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'code', 'name', 'email', 'phone', 'contact_name', 'country_id',
        'currency', 'tax_number', 'address', 'payment_terms', 'status', 'notes', 'meta',
    ];

    protected $casts = [
        'address' => 'array',
        'meta' => 'array',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
