<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfqRequest extends Model
{
    protected $fillable = [
        'rfq_number', 'user_id', 'company_name', 'contact_name', 'contact_email',
        'contact_phone', 'marketplace_id', 'currency', 'status', 'notes', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }
}
