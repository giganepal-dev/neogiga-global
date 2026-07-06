<?php

namespace App\Models\AiCommerce;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product\Product;

class AiBomItem extends BaseModel
{
    protected $table = 'ai_bom_items';

    protected $fillable = [
        'ai_bom_build_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'reason',
        'is_required',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_required' => 'boolean',
    ];

    public function bomBuild(): BelongsTo
    {
        return $this->belongsTo(AiBomBuild::class, 'ai_bom_build_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
