<?php

namespace App\Models\Product;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Marketplace\Marketplace;

class ProductPriceHistory extends BaseModel
{
    protected $table = 'product_price_history';

    protected $fillable = [
        'product_id',
        'marketplace_id',
        'old_price',
        'new_price',
        'currency_code',
        'price_type', // marketplace, vendor, bulk
        'changed_by',
        'reason',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }
}
