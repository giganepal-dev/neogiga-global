<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class B2BQuotation extends Model
{
    protected $table = 'b2b_quotations';

    protected $fillable = ['b2b_account_id', 'b2b_quote_request_id', 'quotation_number', 'status', 'payment_status', 'order_id', 'currency_code', 'subtotal', 'tax_total', 'shipping_total', 'grand_total', 'valid_until', 'accepted_at', 'sent_at', 'created_by', 'price_snapshot', 'metadata'];

    protected $casts = ['valid_until' => 'date', 'accepted_at' => 'datetime', 'sent_at' => 'datetime', 'price_snapshot' => 'array', 'metadata' => 'array'];

    public function items(): HasMany
    {
        return $this->hasMany(B2BQuotationItem::class);
    }
}
