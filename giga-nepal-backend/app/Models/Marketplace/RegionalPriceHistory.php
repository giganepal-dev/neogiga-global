<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class RegionalPriceHistory extends Model
{
    protected $table = 'regional_price_history';

    protected $fillable = [
        'marketplace_product_price_id',
        'product_id',
        'marketplace_id',
        'old_base_price',
        'new_base_price',
        'old_sale_price',
        'new_sale_price',
        'currency_code',
        'changed_by',
        'reason',
    ];

    protected $casts = [
        'old_base_price' => 'decimal:4',
        'new_base_price' => 'decimal:4',
        'old_sale_price' => 'decimal:4',
        'new_sale_price' => 'decimal:4',
    ];
}
