<?php

namespace App\Models\Ai;

use App\Models\BaseModel;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiBomItem extends BaseModel
{
    protected $table = 'ai_bom_items';

    protected $fillable = [
        'ai_bom_build_id',
        'product_id',
        'quantity',
        'estimated_price',
        'reason',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'estimated_price' => 'decimal:2',
        'sort_order' => 'integer',
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
