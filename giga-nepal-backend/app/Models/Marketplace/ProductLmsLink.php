<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NOTE: the product_lms_links migration is currently an empty shell
 * (audit finding DB-02). Model exists so relations resolve; do not
 * write to it until the schema reconciliation pass (Phase 1).
 */
class ProductLmsLink extends Model
{
    protected $fillable = [
        'product_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
